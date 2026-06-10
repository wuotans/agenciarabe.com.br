<?php
$host = 'localhost';
$dbname = 'agenciarabe_clientes';
$username = 'SEU_USUARIO'; // Coloque o usuário do banco
$password = 'SUA_SENHA';   // Coloque a senha do banco

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

session_start();
?>