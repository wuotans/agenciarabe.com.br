<?php
require_once 'config/database.php';

if (!isset($_SESSION['cliente_id'])) {
    header('Location: index.php');
    exit;
}

$cliente_id = $_SESSION['cliente_id'];
$cliente_nome = $_SESSION['cliente_nome'];
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_pessoa = $_POST['nome_pessoa'] ?? '';
    $motivo = $_POST['motivo'] ?? '';
    $data_certificado = $_POST['data_certificado'] ?? date('Y-m-d');
    
    // Salvar no banco
    $stmt = $pdo->prepare("INSERT INTO certificados (cliente_id, nome_pessoa, motivo, data_certificado) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$cliente_id, $nome_pessoa, $motivo, $data_certificado])) {
        $certificado_id = $pdo->lastInsertId();
        $mensagem = '<div class="alert success">✅ Certificado gerado com sucesso!</div>';
        
        // Aqui você pode chamar uma função para gerar PDF
        // gerarPDF($certificado_id, $nome_pessoa, $motivo, $data_certificado);
    } else {
        $mensagem = '<div class="alert error">❌ Erro ao gerar certificado.</div>';
    }
}

// Buscar certificados já gerados
$stmt = $pdo->prepare("SELECT * FROM certificados WHERE cliente_id = ? ORDER BY created_at DESC");
$stmt->execute([$cliente_id]);
$certificados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados - Agência RABE</title>
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

    <main class="certificado-container">
        <div class="certificado-grid">
            <!-- Formulário -->
            <div class="certificado-form-card">
                <h2>Gerar Novo Certificado</h2>
                <?php echo $mensagem; ?>
                <form method="POST" class="certificado-form">
                    <div class="form-group">
                        <label>Nome do Participante *</label>
                        <input type="text" name="nome_pessoa" required placeholder="Nome completo">
                    </div>
                    <div class="form-group">
                        <label>Motivo / Curso *</label>
                        <textarea name="motivo" rows="3" required placeholder="Ex: Participação no Curso de Endomarketing - Turma 2025"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Data do Certificado *</label>
                        <input type="date" name="data_certificado" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button type="submit" class="btn-primary">Gerar Certificado</button>
                </form>
            </div>

            <!-- Lista de Certificados -->
            <div class="certificados-list-card">
                <h2>Certificados Gerados</h2>
                <?php if (count($certificados) > 0): ?>
                    <div class="certificados-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Participante</th>
                                    <th>Motivo</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificados as $cert): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cert['nome_pessoa']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($cert['motivo'], 0, 50)); ?>...</td>
                                    <td><?php echo date('d/m/Y', strtotime($cert['data_certificado'])); ?></td>
                                    <td>
                                        <a href="#" class="btn-icon" title="Baixar PDF">📄</a>
                                        <a href="#" class="btn-icon" title="Visualizar">👁️</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-message">Nenhum certificado gerado ainda.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>