<?php
$host = 'localhost';
$dbname = 'edua3680_agenciarabe_clientes';
$username = 'edua3680_rabe '; // Coloque o usuário do banco
$password = 'Rabe@2026';   // Coloque a senha do banco

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

session_start();
?>