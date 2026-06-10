<?php
require_once 'config/database.php';

if (isset($_SESSION['cliente_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = md5($_POST['senha'] ?? '');
    
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario = ? AND senha = ?");
    $stmt->execute([$usuario, $senha]);
    $cliente = $stmt->fetch();
    
    if ($cliente) {
        $_SESSION['cliente_id'] = $cliente['id'];
        $_SESSION['cliente_nome'] = $cliente['nome_cliente'];
        $_SESSION['cliente_pasta'] = $cliente['pasta'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = 'Usuário ou senha inválidos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Cliente - Agência RABE</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <img src="/logo_rabe_branca.png" alt="Agência RABE" class="login-logo">
            <h2>Área do Cliente</h2>
            <?php if ($erro): ?>
                <div class="alert error"><?php echo $erro; ?></div>
            <?php endif; ?>
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label>Usuário</label>
                    <input type="text" name="usuario" required>
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="senha" required>
                </div>
                <button type="submit" class="btn-primary">Entrar</button>
            </form>
            <p class="login-footer">Agência RABE - Endomarketing & Social</p>
        </div>
    </div>
</body>
</html>