<?php
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];
$usuario_nivel = $_SESSION['usuario_nivel'];
$usuario_grupo_id = $_SESSION['grupo_id'] ?? 1;

// Buscar grupos
if ($usuario_nivel == 'admin') {
    $grupos = $pdo->query("SELECT * FROM grupos ORDER BY id")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id = ?");
    $stmt->execute([$usuario_grupo_id]);
    $grupos = $stmt->fetchAll();
}

$grupo_id = $_GET['grupo'] ?? ($grupos[0]['id'] ?? 0);

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao == 'pegar_atividade') {
        $tarefa_id = $_POST['tarefa_id'];
        $pdo->prepare("UPDATE tarefas SET responsavel_id = ?, status = 'em_andamento' WHERE id = ?")->execute([$usuario_id, $tarefa_id]);
        header("Location: dashboard.php?grupo=$grupo_id");
        exit;
    }
    
    if ($acao == 'desvincular') {
        $tarefa_id = $_POST['tarefa_id'];
        $pdo->prepare("UPDATE tarefas SET responsavel_id = NULL, status = 'nao_iniciado' WHERE id = ?")->execute([$tarefa_id]);
        header("Location: dashboard.php?grupo=$grupo_id");
        exit;
    }
    
    if ($acao == 'completar') {
        $tarefa_id = $_POST['tarefa_id'];
        $descricao_conclusao = $_POST['descricao_conclusao'];
        $pdo->prepare("UPDATE tarefas SET status = 'completado', descricao_conclusao = ? WHERE id = ?")->execute([$descricao_conclusao, $tarefa_id]);
        header("Location: dashboard.php?grupo=$grupo_id");
        exit;
    }
}

// Buscar tarefas do grupo
$tarefas_nao_iniciado = [];
$tarefas_em_andamento = [];
$tarefas_completado = [];

if ($grupo_id) {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nome as responsavel_nome, c.nome as criador_nome
        FROM tarefas t
        LEFT JOIN usuarios u ON t.responsavel_id = u.id
        LEFT JOIN usuarios c ON t.criado_por = c.id
        WHERE t.grupo_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$grupo_id]);
    $todas = $stmt->fetchAll();
    
    foreach ($todas as $t) {
        if ($t['status'] == 'nao_iniciado') $tarefas_nao_iniciado[] = $t;
        elseif ($t['status'] == 'em_andamento') $tarefas_em_andamento[] = $t;
        else $tarefas_completado[] = $t;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RABE Flow</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="/logo_rabe_branca.png" alt="RABE" height="35">
                <span>RABE Flow</span>
            </div>
            <div class="nav-menu">
                <div class="user-info">
                    <span class="user-avatar"><?php echo substr($usuario_nome, 0, 1); ?></span>
                    <span class="user-name"><?php echo $usuario_nome; ?></span>
                    <span class="user-badge <?php echo $usuario_nivel; ?>">
                        <?php echo $usuario_nivel == 'admin' ? 'Admin' : ($usuario_nivel == 'editor' ? 'Editor' : 'Usuario'); ?>
                    </span>
                </div>
                <a href="logout.php" class="nav-link logout">Sair</a>
            </div>
        </div>
    </nav>

    <main class="dashboard">
        <!-- Grupos -->
        <div class="boards-bar">
            <div class="boards-scroll">
                <?php foreach ($grupos as $g): ?>
                    <a href="dashboard.php?grupo=<?php echo $g['id']; ?>" 
                       class="board-tab <?php echo ($grupo_id == $g['id']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($g['nome']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dashboard-header">
            <h1>Quadro de Tarefas</h1>
            <button class="btn-add-tarefa" id="btnAddTarefa">Nova Tarefa</button>
        </div>

        <!-- Kanban Board -->
        <div class="board">
            <!-- Coluna A Fazer -->
            <div class="column">
                <div class="column-header">
                    <h2>A Fazer</h2>
                    <span class="task-count"><?php echo count($tarefas_nao_iniciado); ?></span>
                </div>
                <div class="task-list">
                    <?php foreach ($tarefas_nao_iniciado as $tarefa): ?>
                        <div class="task-card" onclick="verTarefa(<?php echo $tarefa['id']; ?>)">
                            <div class="task-priority priority-<?php echo $tarefa['prioridade']; ?>"></div>
                            <h3><?php echo htmlspecialchars($tarefa['titulo']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($tarefa['descricao'], 0, 80)); ?></p>
                            <div class="task-meta">
                                <span class="task-creator">Criado por: <?php echo $tarefa['criador_nome']; ?></span>
                                <?php if ($tarefa['prazo']): ?>
                                    <span class="task-date"><?php echo date('d/m', strtotime($tarefa['prazo'])); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <button class="btn-view" onclick="event.stopPropagation(); verTarefa(<?php echo $tarefa['id']; ?>)">Ver</button>
                                <?php if ($usuario_nivel != 'normal' && !$tarefa['responsavel_id']): ?>
                                    <form method="POST" style="display:inline" onsubmit="event.stopPropagation();">
                                        <input type="hidden" name="acao" value="pegar_atividade">
                                        <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                        <button type="submit" class="btn-pegar">Pegar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($usuario_nivel != 'normal'): ?>
                                    <a href="editar_tarefa.php?id=<?php echo $tarefa['id']; ?>" class="btn-editar" onclick="event.stopPropagation();">Editar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn-add-task-to-column" onclick="document.getElementById('taskModal').style.display='block'">
                    Nova Tarefa
                </button>
            </div>

            <!-- Coluna Em Andamento -->
            <div class="column">
                <div class="column-header">
                    <h2>Em Andamento</h2>
                    <span class="task-count"><?php echo count($tarefas_em_andamento); ?></span>
                </div>
                <div class="task-list">
                    <?php foreach ($tarefas_em_andamento as $tarefa): ?>
                        <div class="task-card" onclick="verTarefa(<?php echo $tarefa['id']; ?>)">
                            <div class="task-priority priority-<?php echo $tarefa['prioridade']; ?>"></div>
                            <h3><?php echo htmlspecialchars($tarefa['titulo']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($tarefa['descricao'], 0, 80)); ?></p>
                            <div class="task-meta">
                                <span class="task-assignee">Responsavel: <?php echo $tarefa['responsavel_nome']; ?></span>
                                <?php if ($tarefa['prazo']): ?>
                                    <span class="task-date"><?php echo date('d/m', strtotime($tarefa['prazo'])); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <button class="btn-view" onclick="event.stopPropagation(); verTarefa(<?php echo $tarefa['id']; ?>)">Ver</button>
                                <?php if ($usuario_id == $tarefa['responsavel_id'] || $usuario_nivel == 'admin'): ?>
                                    <form method="POST" style="display:inline" onsubmit="event.stopPropagation();">
                                        <input type="hidden" name="acao" value="desvincular">
                                        <input type="hidden" name="tarefa_id" value="<?php echo $tarefa['id']; ?>">
                                        <button type="submit" class="btn-desvincular">Desvincular</button>
                                    </form>
                                    <button class="btn-completar" onclick="event.stopPropagation(); abrirModalCompletar(<?php echo $tarefa['id']; ?>)">Completar</button>
                                <?php endif; ?>
                                <?php if ($usuario_nivel != 'normal'): ?>
                                    <a href="editar_tarefa.php?id=<?php echo $tarefa['id']; ?>" class="btn-editar" onclick="event.stopPropagation();">Editar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn-add-task-to-column" onclick="document.getElementById('taskModal').style.display='block'">
                    Nova Tarefa
                </button>
            </div>

            <!-- Coluna Concluido -->
            <div class="column">
                <div class="column-header">
                    <h2>Concluido</h2>
                    <span class="task-count"><?php echo count($tarefas_completado); ?></span>
                </div>
                <div class="task-list">
                    <?php foreach ($tarefas_completado as $tarefa): ?>
                        <div class="task-card completed" onclick="verTarefa(<?php echo $tarefa['id']; ?>)">
                            <div class="task-priority priority-<?php echo $tarefa['prioridade']; ?>"></div>
                            <h3><?php echo htmlspecialchars($tarefa['titulo']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($tarefa['descricao'], 0, 80)); ?></p>
                            <div class="task-meta">
                                <span class="task-assignee">Concluido por: <?php echo $tarefa['responsavel_nome']; ?></span>
                            </div>
                            <div class="task-actions">
                                <button class="btn-view" onclick="event.stopPropagation(); verTarefa(<?php echo $tarefa['id']; ?>)">Ver</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Nova Tarefa -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Nova Tarefa</h2>
            <form method="POST" action="criar_tarefa.php">
                <input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>">
                <div class="form-group">
                    <label>Titulo</label>
                    <input type="text" name="titulo" required>
                </div>
                <div class="form-group">
                    <label>Descricao</label>
                    <textarea name="descricao" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Prioridade</label>
                        <select name="prioridade">
                            <option value="baixa">Baixa</option>
                            <option value="media">Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Prazo</label>
                        <input type="date" name="prazo">
                    </div>
                </div>
                <button type="submit" class="btn-primary">Criar Tarefa</button>
            </form>
        </div>
    </div>

    <!-- Modal Completar Tarefa -->
    <div id="completarModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Completar Tarefa</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="completar">
                <input type="hidden" name="tarefa_id" id="completar_tarefa_id">
                <div class="form-group">
                    <label>Descricao do que foi feito</label>
                    <textarea name="descricao_conclusao" rows="4" required placeholder="Descreva o que foi realizado..."></textarea>
                </div>
                <div class="form-group">
                    <label>Anexar comprovantes</label>
                    <input type="file" name="anexo[]" multiple accept="image/*,application/pdf">
                    <small>Pode anexar multiplos arquivos (fotos, PDFs)</small>
                </div>
                <button type="submit" class="btn-primary">Completar Tarefa</button>
            </form>
        </div>
    </div>

    <!-- Modal Ver Tarefa -->
    <div id="viewModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close">&times;</span>
            <h2 id="viewTitle"></h2>
            <div id="viewContent"></div>
            <hr>
            <h3>Comentarios</h3>
            <div id="comentariosList"></div>
            <div class="comment-form">
                <textarea id="novoComentario" rows="2" placeholder="Adicionar comentario..."></textarea>
                <button class="btn-submit" onclick="adicionarComentario()">Enviar Comentario</button>
            </div>
        </div>
    </div>

    <script>
        let tarefaAtualId = null;
        
        function verTarefa(id) {
            tarefaAtualId = id;
            fetch(`detalhes_tarefa.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('viewTitle').innerHTML = data.titulo;
                    document.getElementById('viewContent').innerHTML = `
                        <p><strong>Descricao:</strong><br>${data.descricao || 'Sem descricao'}</p>
                        <p><strong>Prioridade:</strong> ${data.prioridade}</p>
                        ${data.prazo ? `<p><strong>Prazo:</strong> ${new Date(data.prazo).toLocaleDateString('pt-BR')}</p>` : ''}
                        <p><strong>Criado por:</strong> ${data.criador_nome}</p>
                        ${data.responsavel_nome ? `<p><strong>Responsavel:</strong> ${data.responsavel_nome}</p>` : ''}
                        ${data.descricao_conclusao ? `<p><strong>O que foi feito:</strong> ${data.descricao_conclusao}</p>` : ''}
                    `;
                    document.getElementById('viewModal').style.display = 'block';
                    carregarComentarios(id);
                });
        }
        
        function carregarComentarios(tarefaId) {
            fetch(`comentario_tarefa.php?acao=buscar_comentarios&tarefa_id=${tarefaId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('comentariosList');
                    if (data.length === 0) {
                        container.innerHTML = '<p class="text-muted">Nenhum comentario ainda.</p>';
                        return;
                    }
                    container.innerHTML = data.map(c => `
                        <div class="comment-item">
                            <div class="comment-author">${c.usuario_nome} - ${new Date(c.created_at).toLocaleString()}</div>
                            <div class="comment-text">${c.comentario}</div>
                        </div>
                    `).join('');
                });
        }
        
        function adicionarComentario() {
            const comentario = document.getElementById('novoComentario').value;
            if (!comentario.trim()) return;
            
            const formData = new FormData();
            formData.append('acao', 'adicionar_comentario');
            formData.append('tarefa_id', tarefaAtualId);
            formData.append('comentario', comentario);
            
            fetch('comentario_tarefa.php', { method: 'POST', body: formData })
                .then(() => {
                    document.getElementById('novoComentario').value = '';
                    carregarComentarios(tarefaAtualId);
                });
        }
        
        function abrirModalCompletar(tarefaId) {
            document.getElementById('completar_tarefa_id').value = tarefaId;
            document.getElementById('completarModal').style.display = 'block';
        }
        
        // Modais
        const taskModal = document.getElementById('taskModal');
        const completarModal = document.getElementById('completarModal');
        const viewModal = document.getElementById('viewModal');
        
        document.getElementById('btnAddTarefa').onclick = () => taskModal.style.display = 'block';
        
        document.querySelectorAll('.close').forEach(btn => {
            btn.onclick = () => {
                taskModal.style.display = 'none';
                completarModal.style.display = 'none';
                viewModal.style.display = 'none';
            }
        });
        
        window.onclick = (event) => {
            if (event.target == taskModal) taskModal.style.display = 'none';
            if (event.target == completarModal) completarModal.style.display = 'none';
            if (event.target == viewModal) viewModal.style.display = 'none';
        }
    </script>
</body>
</html>