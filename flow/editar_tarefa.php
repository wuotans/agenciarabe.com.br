<?php
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_nivel'] == 'normal') {
    header('Location: dashboard.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $prioridade = $_POST['prioridade'];
    $prazo = $_POST['prazo'] ?: null;
    
    $stmt = $pdo->prepare("UPDATE tarefas SET titulo = ?, descricao = ?, prioridade = ?, prazo = ? WHERE id = ?");
    $stmt->execute([$titulo, $descricao, $prioridade, $prazo, $id]);
    
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tarefas WHERE id = ?");
$stmt->execute([$id]);
$tarefa = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tarefa</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="modal" style="display: block;">
        <div class="modal-content" style="max-width: 500px;">
            <h2>Editar Tarefa</h2>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="titulo" value="<?php echo htmlspecialchars($tarefa['titulo']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea name="descricao" rows="3"><?php echo htmlspecialchars($tarefa['descricao']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Prioridade</label>
                    <select name="prioridade">
                        <option value="baixa" <?php echo $tarefa['prioridade'] == 'baixa' ? 'selected' : ''; ?>>Baixa</option>
                        <option value="media" <?php echo $tarefa['prioridade'] == 'media' ? 'selected' : ''; ?>>Média</option>
                        <option value="alta" <?php echo $tarefa['prioridade'] == 'alta' ? 'selected' : ''; ?>>Alta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prazo</label>
                    <input type="date" name="prazo" value="<?php echo $tarefa['prazo']; ?>">
                </div>
                <button type="submit" class="btn-primary">Salvar</button>
                <a href="dashboard.php" class="btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</body>
</html>