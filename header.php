<?php
require_once __DIR__ . "/funcoes.php";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bibli-ON | Sua biblioteca online</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header class="topo">

    <a href="index.php" class="logo">
        Bibli-<span>ON</span>
    </a>

    <nav>
        <a href="index.php">Início</a>

        <?php if (usuarioLogado()): ?>

            <?php if (($_SESSION['tipo'] ?? '') === 'admin'): ?>
                <a href="admin.php">Painel administrativo</a>
            <?php endif; ?>

            <span class="usuario">
                Olá, <?= proteger($_SESSION['nome']); ?>
            </span>

            <a href="logout.php">Sair</a>

        <?php else: ?>

            <a href="login.php">Entrar</a>
            <a href="cadastro.php" class="botao-menu">Cadastrar</a>

        <?php endif; ?>
    </nav>

</header>

<main class="container"></main>