<?php
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] != 'admin') {
    header('Location: index.php');
    exit;
}

$mensagem = '';
$erro = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // CADASTRAR NOVO USUÁRIO
    if (isset($_POST['acao']) && $_POST['acao'] == 'cadastrar') {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $senha = md5($_POST['senha']);
        $nivel = $_POST['nivel'];
        
        // Verificar se e-mail já existe
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $erro = "❌ Este e-mail já está cadastrado!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $senha, $nivel]);
            $mensagem = "✅ Usuário cadastrado com sucesso!";
        }
    }
    
    // EDITAR USUÁRIO (nível, nome, etc)
    elseif (isset($_POST['acao']) && $_POST['acao'] == 'editar') {
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $nivel = $_POST['nivel'];
        
        // Não permitir editar o próprio admin principal ou rebaixar si mesmo
        if ($id == $_SESSION['usuario_id']) {
            $erro = "❌ Você não pode alterar seu próprio nível de acesso!";
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, nivel = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $nivel, $id]);
            $mensagem = "✅ Usuário atualizado com sucesso!";
        }
    }
    
    // ALTERAR SENHA
    elseif (isset($_POST['acao']) && $_POST['acao'] == 'alterar_senha') {
        $id = $_POST['id'];
        $nova_senha = md5($_POST['nova_senha']);
        
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([$nova_senha, $id]);
        $mensagem = "✅ Senha alterada com sucesso!";
    }
    
    // EXCLUIR USUÁRIO
    elseif (isset($_POST['acao']) && $_POST['acao'] == 'excluir') {
        $id = $_POST['id'];
        
        // Não permitir excluir a si mesmo
        if ($id == $_SESSION['usuario_id']) {
            $erro = "❌ Você não pode excluir seu próprio usuário!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $mensagem = "✅ Usuário excluído com sucesso!";
        }
    }
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY 
    CASE nivel 
        WHEN 'admin' THEN 1 
        WHEN 'editor' THEN 2 
        ELSE 3 
    END, nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - RABE Flow</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="/logo_rabe_branca.png" alt="RABE" height="35">
                <span>RABE Flow - Admin</span>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">📋 Voltar ao Quadro</a>
                <a href="logout.php" class="nav-link logout">🚪 Sair</a>
            </div>
        </div>
    </nav>

    <main class="admin-container">
        <div class="admin-header animate-slide-down">
            <h1>👑 Painel Administrativo</h1>
            <p>Gerencie usuários, níveis de acesso e permissões</p>
        </div>

        <?php if ($mensagem): ?>
            <div class="alert success animate-slide-up"><?php echo $mensagem; ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
            <div class="alert error animate-slide-up"><?php echo $erro; ?></div>
        <?php endif; ?>

        <div class="admin-grid">
            <!-- Card de Cadastro -->
            <div class="admin-card animate-slide-up">
                <h2>➕ Cadastrar Novo Usuário</h2>
                <form method="POST" class="admin-form" onsubmit="return validarSenha(this)">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="form-group">
                        <label>👤 Nome completo</label>
                        <input type="text" name="nome" required placeholder="Ex: João Silva">
                    </div>
                    <div class="form-group">
                        <label>📧 E-mail</label>
                        <input type="email" name="email" required placeholder="joao@exemplo.com">
                    </div>
                    <div class="form-group">
                        <label>🔒 Senha</label>
                        <input type="password" name="senha" id="senha" required placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label>🔐 Confirmar Senha</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" required placeholder="Digite novamente">
                    </div>
                    <div class="form-group">
                        <label>👑 Nível de acesso</label>
                        <select name="nivel">
                            <option value="normal">👤 Usuário Normal (apenas visualizar)</option>
                            <option value="editor">✏️ Editor (criar/editar tarefas)</option>
                            <option value="admin">👑 Administrador (acesso total + gerenciar usuários)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Cadastrar Usuário</button>
                </form>
            </div>

            <!-- Lista de Usuários -->
            <div class="admin-card animate-slide-up" style="animation-delay: 0.1s">
                <h2>📋 Usuários do Sistema</h2>
                <div class="usuarios-table">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>👤 Usuário</th>
                                    <th>📧 E-mail</th>
                                    <th>👑 Nível</th>
                                    <th>📅 Criado em</th>
                                    <th>⚙️ Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                        <?php if ($usuario['id'] == $_SESSION['usuario_id']): ?>
                                            <span class="current-user-badge">(Você)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <span class="level-badge level-<?php echo $usuario['nivel']; ?>">
                                            <?php 
                                            if ($usuario['nivel'] == 'admin') echo '👑 Administrador';
                                            elseif ($usuario['nivel'] == 'editor') echo '✏️ Editor';
                                            else echo '👤 Usuário Normal';
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                                    <td class="actions-cell">
                                        <!-- Botão Editar -->
                                        <button class="btn-icon edit-user" 
                                                data-id="<?php echo $usuario['id']; ?>"
                                                data-nome="<?php echo htmlspecialchars($usuario['nome']); ?>"
                                                data-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                data-nivel="<?php echo $usuario['nivel']; ?>"
                                                title="Editar usuário">
                                            ✏️
                                        </button>
                                        
                                        <!-- Botão Alterar Senha -->
                                        <button class="btn-icon change-password" 
                                                data-id="<?php echo $usuario['id']; ?>"
                                                data-nome="<?php echo htmlspecialchars($usuario['nome']); ?>"
                                                title="Alterar senha">
                                            🔒
                                        </button>
                                        
                                        <!-- Botão Excluir (não aparece para o próprio usuário) -->
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Tem certeza que deseja excluir o usuário <?php echo addslashes($usuario['nome']); ?>? Esta ação não pode ser desfeita.')">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" class="btn-icon" title="Excluir usuário">🗑️</button>
                                        </form>
                                        <?php else: ?>
                                            <span class="protected" title="Você não pode excluir seu próprio usuário">🔒</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Editar Usuário -->
    <div id="editUserModal" class="modal">
        <div class="modal-content animate-slide-up">
            <span class="close">&times;</span>
            <h2>✏️ Editar Usuário</h2>
            <form method="POST" id="editUserForm">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>👤 Nome completo</label>
                    <input type="text" name="nome" id="edit_nome" required>
                </div>
                <div class="form-group">
                    <label>📧 E-mail</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>👑 Nível de acesso</label>
                    <select name="nivel" id="edit_nivel">
                        <option value="normal">👤 Usuário Normal (apenas visualizar)</option>
                        <option value="editor">✏️ Editor (criar/editar tarefas)</option>
                        <option value="admin">👑 Administrador (acesso total)</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Salvar Alterações</button>
            </form>
        </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content animate-slide-up">
            <span class="close">&times;</span>
            <h2>🔒 Alterar Senha</h2>
            <form method="POST" id="changePasswordForm" onsubmit="return validarNovaSenha(this)">
                <input type="hidden" name="acao" value="alterar_senha">
                <input type="hidden" name="id" id="pass_id">
                <div class="form-group">
                    <label>👤 Usuário</label>
                    <input type="text" id="pass_nome" disabled style="opacity:0.7">
                </div>
                <div class="form-group">
                    <label>🔒 Nova Senha</label>
                    <input type="password" name="nova_senha" id="nova_senha" required placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label>🔐 Confirmar Nova Senha</label>
                    <input type="password" id="confirmar_nova_senha" required placeholder="Digite novamente">
                </div>
                <button type="submit" class="btn-primary">Alterar Senha</button>
            </form>
        </div>
    </div>

    <script>
        // Validação de senha no cadastro
        function validarSenha(form) {
            var senha = form.senha.value;
            var confirmar = form.confirmar_senha.value;
            
            if (senha.length < 6) {
                alert('A senha deve ter no mínimo 6 caracteres!');
                return false;
            }
            
            if (senha !== confirmar) {
                alert('As senhas não coincidem!');
                return false;
            }
            return true;
        }
        
        function validarNovaSenha(form) {
            var senha = form.nova_senha.value;
            var confirmar = document.getElementById('confirmar_nova_senha').value;
            
            if (senha.length < 6) {
                alert('A senha deve ter no mínimo 6 caracteres!');
                return false;
            }
            
            if (senha !== confirmar) {
                alert('As senhas não coincidem!');
                return false;
            }
            return true;
        }
        
        // Modal Editar Usuário
        const editModal = document.getElementById('editUserModal');
        const editClose = editModal.querySelector('.close');
        
        document.querySelectorAll('.edit-user').forEach(btn => {
            btn.onclick = function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_nome').value = this.dataset.nome;
                document.getElementById('edit_email').value = this.dataset.email;
                document.getElementById('edit_nivel').value = this.dataset.nivel;
                editModal.style.display = 'block';
            }
        });
        
        editClose.onclick = function() {
            editModal.style.display = 'none';
        }
        
        // Modal Alterar Senha
        const passModal = document.getElementById('changePasswordModal');
        const passClose = passModal.querySelector('.close');
        
        document.querySelectorAll('.change-password').forEach(btn => {
            btn.onclick = function() {
                document.getElementById('pass_id').value = this.dataset.id;
                document.getElementById('pass_nome').value = this.dataset.nome;
                passModal.style.display = 'block';
            }
        });
        
        passClose.onclick = function() {
            passModal.style.display = 'none';
        }
        
        // Fechar modais clicando fora
        window.onclick = function(event) {
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == passModal) {
                passModal.style.display = 'none';
            }
        }
    </script>
</body>
</html>