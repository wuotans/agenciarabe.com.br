<?php
/**
 * Funções auxiliares do sistema RABE Flow
 */

// Prevenir acesso direto
if (!defined('ACCESS')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * Sanitizar entrada de dados
 */
function sanitizar($dado) {
    $dado = trim($dado);
    $dado = stripslashes($dado);
    $dado = htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    return $dado;
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Gerar senha segura
 */
function gerarSenha($tamanho = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    return substr(str_shuffle($chars), 0, $tamanho);
}

/**
 * Formatar data para exibição
 */
function formatarData($data, $formato = 'd/m/Y H:i') {
    if (!$data) return '-';
    $timestamp = strtotime($data);
    return date($formato, $timestamp);
}

/**
 * Formatar data para banco (padrão MySQL)
 */
function formatarDataBanco($data) {
    if (!$data) return null;
    $partes = explode('/', $data);
    if (count($partes) == 3) {
        return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
    }
    return $data;
}

/**
 * Truncar texto (limitar caracteres)
 */
function truncarTexto($texto, $limite = 100, $reticencias = '...') {
    if (strlen($texto) <= $limite) return $texto;
    return substr($texto, 0, $limite) . $reticencias;
}

/**
 * Obter cor da prioridade
 */
function getCorPrioridade($prioridade) {
    $cores = [
        'baixa' => '#4caf50',  // verde
        'media' => '#ffc107',  // amarelo
        'alta' => '#f44336'    // vermelho
    ];
    return $cores[$prioridade] ?? '#999';
}

/**
 * Obter ícone da prioridade
 */
function getIconePrioridade($prioridade) {
    $icones = [
        'baixa' => '🟢',
        'media' => '🟡',
        'alta' => '🔴'
    ];
    return $icones[$prioridade] ?? '⚪';
}

/**
 * Verificar se tarefa está atrasada
 */
function tarefaAtrasada($prazo) {
    if (!$prazo) return false;
    return strtotime($prazo) < strtotime(date('Y-m-d'));
}

/**
 * Registrar log de atividade
 */
function registrarLog($pdo, $usuario_id, $acao, $tabela, $registro_id) {
    $stmt = $pdo->prepare("
        INSERT INTO logs (usuario_id, acao, tabela, registro_id, ip, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    return $stmt->execute([$usuario_id, $acao, $tabela, $registro_id, $ip, $user_agent]);
}

/**
 * Verificar permissão do usuário
 */
function temPermissao($nivel_necessario, $usuario_nivel) {
    $niveis = ['normal' => 1, 'editor' => 2, 'admin' => 3];
    return $niveis[$usuario_nivel] >= $niveis[$nivel_necessario];
}

/**
 * Gerar slug amigável
 */
function gerarSlug($texto) {
    $texto = preg_replace('~[^\pL\d]+~u', '-', $texto);
    $texto = iconv('utf-8', 'us-ascii//TRANSLIT', $texto);
    $texto = preg_replace('~[^-\w]+~', '', $texto);
    $texto = trim($texto, '-');
    $texto = preg_replace('~-+~', '-', $texto);
    $texto = strtolower($texto);
    return $texto ?: 'n-a';
}

/**
 * Obter estatísticas do usuário
 */
function getEstatisticasUsuario($pdo, $usuario_id) {
    // Total de tarefas atribuídas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tarefas WHERE responsavel_id = ?");
    $stmt->execute([$usuario_id]);
    $total = $stmt->fetch()['total'];
    
    // Tarefas concluídas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as concluidas FROM tarefas t 
        JOIN colunas c ON t.coluna_id = c.id 
        WHERE t.responsavel_id = ? AND c.nome = 'Concluido'
    ");
    $stmt->execute([$usuario_id]);
    $concluidas = $stmt->fetch()['concluidas'];
    
    return [
        'total' => $total,
        'concluidas' => $concluidas,
        'percentual' => $total > 0 ? round(($concluidas / $total) * 100) : 0
    ];
}

/**
 * Criar tabela de logs (se não existir)
 */
function criarTabelaLogs($pdo) {
    $sql = "
        CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT,
            acao VARCHAR(100),
            tabela VARCHAR(50),
            registro_id INT,
            ip VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )
    ";
    return $pdo->exec($sql);
}

/**
 * Enviar notificação por email (simples)
 */
function enviarNotificacaoEmail($para, $assunto, $mensagem) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: RABE Flow <noreply@agenciarabe.com.br>" . "\r\n";
    
    $corpo = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 20px; }
                .header { background: #C9A26B; color: #fff; padding: 15px; text-align: center; border-radius: 8px; }
                .content { padding: 20px; }
                .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>RABE Flow</h2>
                </div>
                <div class='content'>
                    $mensagem
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Agência RABE - Endomarketing & Social</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    return mail($para, $assunto, $corpo, $headers);
}

/**
 * Backup do banco de dados (simples)
 */
function backupBanco($pdo) {
    $tabelas = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch()) {
        $tabelas[] = $row[0];
    }
    
    $backup = "-- Backup RABE Flow\n";
    $backup .= "-- Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tabelas as $tabela) {
        $backup .= "DROP TABLE IF EXISTS `$tabela`;\n";
        
        $stmt = $pdo->query("SHOW CREATE TABLE $tabela");
        $create = $stmt->fetch();
        $backup .= $create[1] . ";\n\n";
        
        $stmt = $pdo->query("SELECT * FROM $tabela");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $valores = array_map(function($v) use ($pdo) {
                return $pdo->quote($v);
            }, $row);
            $backup .= "INSERT INTO `$tabela` VALUES (" . implode(',', $valores) . ");\n";
        }
        $backup .= "\n";
    }
    
    $arquivo = "../backups/backup_" . date('Y-m-d_H-i-s') . ".sql";
    file_put_contents($arquivo, $backup);
    
    return $arquivo;
}

/**
 * Limpar cache (se houver)
 */
function limparCache() {
    $pastas = ['../cache', '../tmp'];
    foreach ($pastas as $pasta) {
        if (is_dir($pasta)) {
            $arquivos = glob($pasta . '/*');
            foreach ($arquivos as $arquivo) {
                if (is_file($arquivo)) {
                    unlink($arquivo);
                }
            }
        }
    }
    return true;
}

/**
 * Registrar log de atividade (versão melhorada para quadros)
 */
function registrarLogAtividade($pdo, $usuario_id, $usuario_nome, $acao, $descricao, $quadro_id = null, $tarefa_id = null, $dados_antigos = null, $dados_novos = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $pdo->prepare("
        INSERT INTO logs_atividades (usuario_id, usuario_nome, quadro_id, tarefa_id, acao, descricao, dados_antigos, dados_novos, ip, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([$usuario_id, $usuario_nome, $quadro_id, $tarefa_id, $acao, $descricao, $dados_antigos, $dados_novos, $ip, $user_agent]);
}

/**
 * Buscar logs de um quadro específico
 */
function getLogsQuadro($pdo, $quadro_id, $limite = 50) {
    $stmt = $pdo->prepare("
        SELECT l.*, 
               CASE 
                   WHEN l.acao = 'criar_tarefa' THEN '➕ Criou tarefa'
                   WHEN l.acao = 'editar_tarefa' THEN '✏️ Editou tarefa'
                   WHEN l.acao = 'excluir_tarefa' THEN '🗑️ Excluiu tarefa'
                   WHEN l.acao = 'mover_tarefa' THEN '🔄 Moveu tarefa'
                   WHEN l.acao = 'criar_quadro' THEN '📁 Criou quadro'
                   WHEN l.acao = 'editar_quadro' THEN '⚙️ Editou quadro'
                   WHEN l.acao = 'excluir_quadro' THEN '❌ Excluiu quadro'
                   ELSE l.acao
               END as acao_formatada
        FROM logs_atividades l
        WHERE l.quadro_id = ? OR l.quadro_id IS NULL
        ORDER BY l.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$quadro_id, $limite]);
    return $stmt->fetchAll();
}

/**
 * Buscar logs recentes de todos os quadros (para admin)
 */
function getLogsRecentes($pdo, $limite = 30) {
    $stmt = $pdo->prepare("
        SELECT l.*, 
               CASE 
                   WHEN l.acao = 'criar_tarefa' THEN '➕ Criou tarefa'
                   WHEN l.acao = 'editar_tarefa' THEN '✏️ Editou tarefa'
                   WHEN l.acao = 'excluir_tarefa' THEN '🗑️ Excluiu tarefa'
                   WHEN l.acao = 'mover_tarefa' THEN '🔄 Moveu tarefa'
                   WHEN l.acao = 'criar_quadro' THEN '📁 Criou quadro'
                   WHEN l.acao = 'editar_quadro' THEN '⚙️ Editou quadro'
                   ELSE l.acao
               END as acao_formatada,
               q.nome as quadro_nome
        FROM logs_atividades l
        LEFT JOIN quadros q ON l.quadro_id = q.id
        ORDER BY l.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$limite]);
    return $stmt->fetchAll();
}

/**
 * Formatar tempo relativo
 */
function timeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    
    if ($diff < 60) return 'agora pouco';
    if ($diff < 3600) return round($diff / 60) . ' min atrás';
    if ($diff < 86400) return round($diff / 3600) . 'h atrás';
    if ($diff < 604800) return round($diff / 86400) . 'd atrás';
    return date('d/m/Y H:i', strtotime($timestamp));
}

?>