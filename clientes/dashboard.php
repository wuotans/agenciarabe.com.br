<?php
require_once 'config/database.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: index.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$cliente_nome = $_SESSION['cliente_nome'];

// Buscar eventos disponíveis
$stmt = $pdo->prepare("SELECT DISTINCT evento FROM fotos_eventos WHERE cliente_id = ?");
$stmt->execute([$cliente_id]);
$eventos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $cliente_nome; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-logo">
                <img src="/logo_rabe_branca.png" alt="RABE" height="40">
            </a>
            <div class="nav-menu">
                <span class="cliente-nome">Olá, <?php echo $cliente_nome; ?></span>
                <a href="logout.php" class="btn-logout">Sair</a>
            </div>
        </div>
    </nav>

    <main class="dashboard-container">
        <h1>Portal do Cliente</h1>
        
        <div class="cards-grid">
            <!-- Card Fotos -->
            <div class="card">
                <div class="card-icon">📸</div>
                <h3>Fotos do Evento</h3>
                <p>Acesse e baixe as fotos dos seus eventos</p>
                <a href="fotos.php" class="btn-card">Acessar Fotos</a>
            </div>

            <!-- Card Certificados -->
            <div class="card">
                <div class="card-icon">📜</div>
                <h3>Gerar Certificados</h3>
                <p>Crie certificados personalizados para participantes</p>
                <a href="certificado.php" class="btn-card">Gerar Certificado</a>
            </div>
        </div>

        <?php if (count($eventos) > 0): ?>
        <div class="eventos-section">
            <h2>Eventos Disponíveis</h2>
            <div class="eventos-grid">
                <?php foreach ($eventos as $evento): ?>
                <div class="evento-card">
                    <h4><?php echo htmlspecialchars($evento['evento']); ?></h4>
                    <a href="fotos.php?evento=<?php echo urlencode($evento['evento']); ?>" class="btn-evento">
                        Ver Fotos
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>