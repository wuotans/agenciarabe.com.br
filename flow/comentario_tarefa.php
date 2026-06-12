<?php
require_once 'config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit;
}

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

if ($acao == 'adicionar_comentario') {
    $tarefa_id = $_POST['tarefa_id'];
    $comentario = $_POST['comentario'];
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_nome = $_SESSION['usuario_nome'];
    
    // Criar tabela se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS tarefa_comentarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tarefa_id INT NOT NULL,
        usuario_id INT NOT NULL,
        usuario_nome VARCHAR(100),
        comentario TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (tarefa_id) REFERENCES tarefas(id) ON DELETE CASCADE
    )");
    
    $stmt = $pdo->prepare("INSERT INTO tarefa_comentarios (tarefa_id, usuario_id, usuario_nome, comentario) VALUES (?, ?, ?, ?)");
    $stmt->execute([$tarefa_id, $usuario_id, $usuario_nome, $comentario]);
    
    echo json_encode(['success' => true]);
    exit;
}

if ($acao == 'buscar_comentarios') {
    $tarefa_id = $_GET['tarefa_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM tarefa_comentarios WHERE tarefa_id = ? ORDER BY created_at DESC");
    $stmt->execute([$tarefa_id]);
    $comentarios = $stmt->fetchAll();
    
    echo json_encode($comentarios);
    exit;
}

if ($acao == 'adicionar_anexo') {
    $tarefa_id = $_POST['tarefa_id'];
    
    // Criar pasta se não existir
    $upload_dir = "uploads/tarefas/$tarefa_id/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    
    $arquivo = $_FILES['anexo'];
    $nome_arquivo = time() . '_' . basename($arquivo['name']);
    $caminho = $upload_dir . $nome_arquivo;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminho)) {
        // Criar tabela se não existir
        $pdo->exec("CREATE TABLE IF NOT EXISTS tarefa_anexos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tarefa_id INT NOT NULL,
            usuario_id INT NOT NULL,
            arquivo VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (tarefa_id) REFERENCES tarefas(id) ON DELETE CASCADE
        )");
        
        $stmt = $pdo->prepare("INSERT INTO tarefa_anexos (tarefa_id, usuario_id, arquivo) VALUES (?, ?, ?)");
        $stmt->execute([$tarefa_id, $_SESSION['usuario_id'], $caminho]);
        
        echo json_encode(['success' => true, 'arquivo' => $caminho]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
?>