<?php
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit;
}

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT t.*, u.nome as responsavel_nome, c.nome as criador_nome
    FROM tarefas t
    LEFT JOIN usuarios u ON t.responsavel_id = u.id
    LEFT JOIN usuarios c ON t.criado_por = c.id
    WHERE t.id = ?
");
$stmt->execute([$id]);
$tarefa = $stmt->fetch();

header('Content-Type: application/json');
echo json_encode($tarefa);
?>