<?php
require_once "funcoes.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM livros WHERE id = ?");
$stmt->execute([$id]);

$livro = $stmt->fetch();

if (!$livro) {
    header("Location: index.php");
    exit;
}

require "header.php";
?>

<section class="detalhes-livro">

    <?php $capa = urlCapa($livro['imagem'] ?? ''); ?>
    <?php if ($capa): ?>
        <img
            src="<?= proteger($capa); ?>"
            alt="<?= proteger($livro['titulo']); ?>"
        >
    <?php else: ?>
        <div class="sem-imagem">Sem capa</div>
    <?php endif; ?>

    <div>
        <h1><?= proteger($livro['titulo']); ?></h1>

        <p><strong>Autor:</strong> <?= proteger($livro['autor']); ?></p>

        <p><strong>Categoria:</strong> <?= proteger($livro['categoria']); ?></p>

        <p><?= nl2br(proteger($livro['descricao'])); ?></p>

        <h2 class="preco">
            R$ <?= number_format($livro['preco'], 2, ',', '.'); ?>
        </h2>

        <?php if (usuarioLogado()): ?>
            <form action="pedido.php" method="POST">
                <input type="hidden" name="livro_id" value="<?= $livro['id']; ?>">
                <button type="submit" class="botao">Adquirir livro</button>
            </form>
            <?php if (podeCadastrarLivros()): ?>
                <a href="admin.php?editar=<?= $livro['id']; ?>" class="botao-secundario">Editar cadastro</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="login.php" class="botao">Entre para adquirir este livro</a>
        <?php endif; ?>

    </div>

</section>

<?php require "footer.php"; ?>