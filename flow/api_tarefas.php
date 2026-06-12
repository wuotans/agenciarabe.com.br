<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autorizado']);
    exit;
}

$usuario_nivel = $_SESSION['usuario_nivel'];
$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];
$response = ['success' => false, 'message' => ''];

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

switch ($acao) {
    
    // ========== CRIAR TAREFA ==========
    case 'criar':
        if ($usuario_nivel == 'normal') {
            $response['message'] = 'Sem permissão';
            break;
        }
        
        $coluna_id = $_POST['coluna_id'];
        $titulo = $_POST['titulo'];
        $descricao = $_POST['descricao'];
        $prioridade = $_POST['prioridade'];
        $prazo = $_POST['prazo'] ?: null;
        $responsavel_id = $_POST['responsavel_id'] ?: null;
        
        // Buscar quadro_id para o log
        $stmt = $pdo->prepare("SELECT quadro_id FROM colunas WHERE id = ?");
        $stmt->execute([$coluna_id]);
        $quadro_id = $stmt->fetch()['quadro_id'];
        
        // Buscar maior ordem
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(ordem), -1) + 1 as nova_ordem FROM tarefas WHERE coluna_id = ?");
        $stmt->execute([$coluna_id]);
        $ordem = $stmt->fetch()['nova_ordem'];
        
        // Inserir tarefa
        $stmt = $pdo->prepare("INSERT INTO tarefas (coluna_id, titulo, descricao, prioridade, prazo, responsavel_id, ordem) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$coluna_id, $titulo, $descricao, $prioridade, $prazo, $responsavel_id, $ordem]);
        $tarefa_id = $pdo->lastInsertId();
        
        // Registrar log
        registrarLogAtividade($pdo, $usuario_id, $usuario_nome, 'criar_tarefa', "Criou tarefa: $titulo", $quadro_id, $tarefa_id);
        
        // Buscar nome do responsável
        $responsavel_nome = '';
        if ($responsavel_id) {
            $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
            $stmt->execute([$responsavel_id]);
            $responsavel_nome = $stmt->fetch()['nome'];
        }
        
        $response['success'] = true;
        $response['message'] = 'Tarefa criada com sucesso!';
        $response['tarefa'] = [
            'id' => $tarefa_id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'prioridade' => $prioridade,
            'prazo' => $prazo,
            'responsavel_id' => $responsavel_id,
            'responsavel_nome' => $responsavel_nome
        ];
        break;
    
    // ========== EDITAR TAREFA ==========
    case 'editar':
        if ($usuario_nivel == 'normal') {
            $response['message'] = 'Sem permissão';
            break;
        }
        
        $id = $_POST['id'];
        $titulo = $_POST['titulo'];
        $descricao = $_POST['descricao'];
        $prioridade = $_POST['prioridade'];
        $prazo = $_POST['prazo'] ?: null;
        $responsavel_id = $_POST['responsavel_id'] ?: null;
        
        // Buscar dados antigos e quadro_id para o log
        $stmt = $pdo->prepare("
            SELECT t.*, c.quadro_id 
            FROM tarefas t 
            JOIN colunas c ON t.coluna_id = c.id 
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $tarefa_antiga = $stmt->fetch();
        $quadro_id = $tarefa_antiga['quadro_id'];
        
        // Atualizar tarefa
        $stmt = $pdo->prepare("UPDATE tarefas SET titulo = ?, descricao = ?, prioridade = ?, prazo = ?, responsavel_id = ? WHERE id = ?");
        $stmt->execute([$titulo, $descricao, $prioridade, $prazo, $responsavel_id, $id]);
        
        // Registrar log
        $alteracoes = [];
        if ($tarefa_antiga['titulo'] != $titulo) $alteracoes[] = "título";
        if ($tarefa_antiga['prioridade'] != $prioridade) $alteracoes[] = "prioridade";
        if (!empty($alteracoes)) {
            registrarLogAtividade($pdo, $usuario_id, $usuario_nome, 'editar_tarefa', 
                "Editou tarefa: $titulo (alterou: " . implode(', ', $alteracoes) . ")", 
                $quadro_id, $id);
        }
        
        $response['success'] = true;
        $response['message'] = 'Tarefa atualizada!';
        break;
    
    // ========== EXCLUIR TAREFA ==========
    case 'excluir':
        if ($usuario_nivel == 'normal') {
            $response['message'] = 'Sem permissão';
            break;
        }
        
        $id = $_POST['id'];
        
        // Buscar dados antes de excluir
        $stmt = $pdo->prepare("
            SELECT t.titulo, t.coluna_id, c.quadro_id 
            FROM tarefas t 
            JOIN colunas c ON t.coluna_id = c.id 
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $tarefa = $stmt->fetch();
        
        if ($tarefa) {
            $quadro_id = $tarefa['quadro_id'];
            $titulo = $tarefa['titulo'];
            
            // Excluir tarefa
            $stmt = $pdo->prepare("DELETE FROM tarefas WHERE id = ?");
            $stmt->execute([$id]);
            
            // Registrar log
            registrarLogAtividade($pdo, $usuario_id, $usuario_nome, 'excluir_tarefa', 
                "Excluiu tarefa: $titulo", $quadro_id, $id);
            
            $response['success'] = true;
            $response['message'] = 'Tarefa excluída!';
        } else {
            $response['message'] = 'Tarefa não encontrada';
        }
        break;
    
    // ========== MOVER TAREFA (DRAG & DROP) ==========
    case 'mover':
        if ($usuario_nivel == 'normal') {
            $response['message'] = 'Sem permissão';
            break;
        }
        
        $id = $_POST['id'];
        $nova_coluna_id = $_POST['coluna_id'];
        $nova_ordem = $_POST['ordem'];
        
        // Buscar dados da tarefa e colunas
        $stmt = $pdo->prepare("
            SELECT t.titulo, t.coluna_id as coluna_antiga, 
                   c_antiga.nome as nome_coluna_antiga,
                   c_antiga.quadro_id,
                   c_nova.nome as nome_coluna_nova
            FROM tarefas t
            JOIN colunas c_antiga ON t.coluna_id = c_antiga.id
            JOIN colunas c_nova ON c_nova.id = ?
            WHERE t.id = ?
        ");
        $stmt->execute([$nova_coluna_id, $id]);
        $tarefa = $stmt->fetch();
        
        if ($tarefa) {
            $quadro_id = $tarefa['quadro_id'];
            
            // Atualizar
            $stmt = $pdo->prepare("UPDATE tarefas SET coluna_id = ?, ordem = ? WHERE id = ?");
            $stmt->execute([$nova_coluna_id, $nova_ordem, $id]);
            
            // Registrar log
            $descricao = "Moveu tarefa '{$tarefa['titulo']}' de '{$tarefa['nome_coluna_antiga']}' para '{$tarefa['nome_coluna_nova']}'";
            registrarLogAtividade($pdo, $usuario_id, $usuario_nome, 'mover_tarefa', $descricao, $quadro_id, $id);
            
            $response['success'] = true;
            $response['message'] = 'Tarefa movida!';
        } else {
            $response['message'] = 'Tarefa não encontrada';
        }
        break;
    
    // ========== REORDENAR TAREFAS ==========
    case 'reordenar':
        if ($usuario_nivel == 'normal') {
            $response['message'] = 'Sem permissão';
            break;
        }
        
        $tarefas = json_decode($_POST['tarefas'], true);
        $total = 0;
        
        foreach ($tarefas as $tarefa) {
            $stmt = $pdo->prepare("UPDATE tarefas SET ordem = ? WHERE id = ?");
            $stmt->execute([$tarefa['ordem'], $tarefa['id']]);
            $total++;
        }
        
        if ($total > 0) {
            $response['success'] = true;
            $response['message'] = "$total tarefa(s) reordenada(s)!";
        } else {
            $response['message'] = 'Nenhuma tarefa para reordenar';
        }
        break;
    
    // ========== BUSCAR DADOS DO DASHBOARD ==========
    case 'get_dados':
        $quadro_id = $_GET['quadro_id'] ?? 0;
        
        if ($quadro_id) {
            $colunas = $pdo->prepare("SELECT * FROM colunas WHERE quadro_id = ? ORDER BY ordem");
            $colunas->execute([$quadro_id]);
            $colunas = $colunas->fetchAll();
        } else {
            $colunas = $pdo->query("SELECT * FROM colunas ORDER BY ordem")->fetchAll();
        }
        
        foreach ($colunas as &$coluna) {
            $stmt = $pdo->prepare("
                SELECT t.*, u.nome as responsavel_nome 
                FROM tarefas t 
                LEFT JOIN usuarios u ON t.responsavel_id = u.id 
                WHERE t.coluna_id = ? 
                ORDER BY t.ordem
            ");
            $stmt->execute([$coluna['id']]);
            $coluna['tarefas'] = $stmt->fetchAll();
        }
        
        $response['success'] = true;
        $response['colunas'] = $colunas;
        break;
    
    // ========== BUSCAR LOGS DO QUADRO ==========
    case 'get_logs':
        $quadro_id = $_GET['quadro_id'] ?? 0;
        $limite = $_GET['limite'] ?? 30;
        
        if ($quadro_id) {
            $logs = getLogsQuadro($pdo, $quadro_id, $limite);
        } else {
            $logs = getLogsRecentes($pdo, $limite);
        }
        
        $response['success'] = true;
        $response['logs'] = $logs;
        break;
    
    // ========== ESTATÍSTICAS DO QUADRO ==========
    case 'get_estatisticas':
        $quadro_id = $_GET['quadro_id'] ?? 0;
        
        if ($quadro_id) {
            // Total de tarefas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total FROM tarefas t
                JOIN colunas c ON t.coluna_id = c.id
                WHERE c.quadro_id = ?
            ");
            $stmt->execute([$quadro_id]);
            $total_tarefas = $stmt->fetch()['total'];
            
            // Tarefas por prioridade
            $stmt = $pdo->prepare("
                SELECT prioridade, COUNT(*) as total
                FROM tarefas t
                JOIN colunas c ON t.coluna_id = c.id
                WHERE c.quadro_id = ?
                GROUP BY prioridade
            ");
            $stmt->execute([$quadro_id]);
            $por_prioridade = $stmt->fetchAll();
            
            // Tarefas por status
            $stmt = $pdo->prepare("
                SELECT c.nome as status, COUNT(t.id) as total
                FROM colunas c
                LEFT JOIN tarefas t ON t.coluna_id = c.id
                WHERE c.quadro_id = ?
                GROUP BY c.id
            ");
            $stmt->execute([$quadro_id]);
            $por_status = $stmt->fetchAll();
            
            // Atividades hoje
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM logs_atividades
                WHERE quadro_id = ? AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$quadro_id]);
            $atividades_hoje = $stmt->fetch()['total'];
            
            $response['success'] = true;
            $response['estatisticas'] = [
                'total_tarefas' => $total_tarefas,
                'por_prioridade' => $por_prioridade,
                'por_status' => $por_status,
                'atividades_hoje' => $atividades_hoje
            ];
        } else {
            $response['message'] = 'Quadro não informado';
        }
        break;
    
    // ========== AÇÃO NÃO ENCONTRADA ==========
    default:
        $response['message'] = 'Ação não reconhecida';
        break;
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>