<?php
require_once 'config/database.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: index.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$cliente_pasta = $_SESSION['cliente_pasta'];
$evento = $_GET['evento'] ?? '';

$fotos = [];
if ($evento) {
    $stmt = $pdo->prepare("SELECT * FROM fotos_eventos WHERE cliente_id = ? AND evento = ? ORDER BY data_upload DESC");
    $stmt->execute([$cliente_id, $evento]);
    $fotos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fotos - <?php echo htmlspecialchars($evento); ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-logo">
                <img src="/logo_rabe_branca.png" alt="RABE" height="40">
            </a>
            <a href="dashboard.php" class="btn-back">← Voltar</a>
        </div>
    </nav>

    <main class="fotos-container">
        <h1><?php echo htmlspecialchars($evento); ?></h1>
        
        <?php if (count($fotos) > 0): ?>
            <div class="fotos-grid">
                <?php foreach ($fotos as $foto): ?>
                <div class="foto-card">
                    <img src="uploads/fotos/<?php echo $cliente_pasta; ?>/<?php echo $foto['nome_arquivo']; ?>" 
                         alt="Foto do evento">
                    <div class="foto-actions">
                        <a href="uploads/fotos/<?php echo $cliente_pasta; ?>/<?php echo $foto['nome_arquivo']; ?>" 
                           download class="btn-download">
                            📥 Baixar
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert info">
                Nenhuma foto disponível para este evento ainda.
            </div>
        <?php endif; ?>
    </main>
</body>
</html>