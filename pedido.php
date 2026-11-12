<?php
require_once "funcoes.php";

exigirLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$livroId = filter_input(INPUT_POST, 'livro_id', FILTER_VALIDATE_INT);

if (!$livroId) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM livros WHERE id = ?");
$stmt->execute([$livroId]);

if (!$stmt->fetch()) {
    header("Location: index.php");
    exit;
}

$pedido = $pdo->prepare(
    "INSERT INTO pedidos (usuario_id, livro_id)
     VALUES (?, ?)"
);

$pedido->execute([$_SESSION['usuario_id'], $livroId]);

require "header.php";
?>

<section class="formulario">

    <h1>Pedido confirmado!</h1>

    <p>Seu pedido foi registrado com sucesso no Bibli-ON.</p>

    <p>Esta é uma demonstração acadêmica. Nenhum pagamento foi realizado.</p>

    <a href="index.php" class="botao">Voltar ao catálogo</a>

</section>

<?php require "footer.php"; ?>