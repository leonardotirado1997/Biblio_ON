<?php
require_once __DIR__ . "/funcoes.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bibli-ON | Sua biblioteca online</title>

    <link rel="icon" href="assets/foto%201.png">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="topo">

    <a href="index.php" class="logo">
        <img src="assets/foto%201.png" alt="Biblioteca Online">
    </a>

    <nav>
        <a href="index.php">Início</a>
        <a href="index.php#catalogo">Catálogo</a>

        <?php if (usuarioLogado()): ?>

            <a href="meus-pedidos.php">Meus pedidos</a>

            <?php if (podeCadastrarLivros()): ?>
                <a href="admin.php">Cadastrar livros</a>
            <?php endif; ?>

            <span class="usuario">
                Olá, <?= proteger($_SESSION['nome']); ?>
                <?php if (podeCadastrarLivros()): ?>
                    <small class="selo"><?= ($_SESSION['tipo'] ?? '') === 'admin' ? 'Administrador' : 'Funcionário'; ?></small>
                <?php endif; ?>
            </span>

            <a href="logout.php">Sair</a>

        <?php else: ?>

            <a href="login.php">Entrar</a>
            <a href="cadastro.php" class="botao-menu">Cadastrar</a>

        <?php endif; ?>
    </nav>

</header>

<main class="container"></main>