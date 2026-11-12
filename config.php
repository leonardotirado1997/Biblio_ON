<?php

$host = "localhost:3306";
$banco = "bibli_on";
$usuario = "root";
$senha = "root"; // Coloque aqui sua senha do MySQL, se tiver.

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $erro) {
    die("Erro ao conectar com o banco de dados: " . $erro->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}