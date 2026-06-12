<?php
require_once 'config/database.php';

if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = md5($_POST['senha'] ?? '');
    
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND senha = ?");
    $stmt->execute([$email, $senha]);
    $usuario = $stmt->fetch();
    
    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_nivel'] = $usuario['nivel'];
        $_SESSION['usuario_email'] = $usuario['email'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = '❌ E-mail ou senha inválidos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RABE Flow</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-bg-animation">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>
    
    <div class="login-container">
        <div class="login-card animate-slide-up">
            <div class="login-header">
                <img src="/logo_rabe_branca.png" alt="RABE Flow" class="login-logo">
                <h1>RABE <span>Flow</span></h1>
                <p>Gerencie suas tarefas com fluidez</p>
            </div>
            
            <?php if ($erro): ?>
                <div class="alert error animate-shake"><?php echo $erro; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group animate-slide-up" style="animation-delay: 0.1s">
                    <label>📧 E-mail</label>
                    <input type="email" name="email" required placeholder="seu@email.com">
                </div>
                <div class="form-group animate-slide-up" style="animation-delay: 0.2s">
                    <label>🔒 Senha</label>
                    <input type="password" name="senha" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn-primary animate-slide-up" style="animation-delay: 0.3s">
                    Entrar no Flow →
                </button>
            </form>
            
            <div class="login-footer">
                <p>Agência RABE - Endomarketing & Social</p>
            </div>
        </div>
    </div>
</body>
</html>