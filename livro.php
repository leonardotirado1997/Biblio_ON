<?php
require_once "funcoes.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT livros.*, (
        SELECT MIN(p.data_fim) FROM pedidos p
        WHERE p.livro_id = livros.id
          AND p.tipo = 'emprestimo'
          AND p.data_fim > CURDATE()
    ) AS disponivel_em
    FROM livros
    WHERE livros.id = ?"
);
$stmt->execute([$id]);

$livro = $stmt->fetch();

if (!$livro) {
    header("Location: index.php");
    exit;
}

$erro = $_SESSION['erro_livro'] ?? '';
unset($_SESSION['erro_livro']);

$limites = limitesEmprestimo();
$disponivel = empty($livro['disponivel_em']);

require "header.php";
?>

<section class="detalhes-livro">

    <?php $capa = urlCapa($livro['imagem'] ?? ''); ?>
    <?php if ($capa): ?>
        <img src="<?= proteger($capa); ?>" alt="<?= proteger($livro['titulo']); ?>">
    <?php else: ?>
        <div class="sem-imagem">Sem capa</div>
    <?php endif; ?>

    <div>
        <?php if ($disponivel): ?>
            <span class="selo-status disponivel">Disponível para empréstimo</span>
        <?php else: ?>
            <span class="selo-status emprestado">Emprestado até <?= proteger(formatarData($livro['disponivel_em'])); ?></span>
        <?php endif; ?>

        <h1><?= proteger($livro['titulo']); ?></h1>

        <p><strong>Autor:</strong> <?= proteger($livro['autor']); ?></p>
        <p><strong>Categoria:</strong> <?= proteger($livro['categoria']); ?></p>
        <p><?= nl2br(proteger($livro['descricao'])); ?></p>

        <p class="preco">R$ <?= number_format($livro['preco'], 2, ',', '.'); ?></p>

        <div class="endereco">
            <strong>Retirada presencial</strong>
            <p><?= proteger(enderecoRetirada()); ?></p>
        </div>

        <?php if ($erro): ?>
            <p class="erro"><?= proteger($erro); ?></p>
        <?php endif; ?>

        <div class="bloco-acao" id="emprestar">
            <h2>Emprestar</h2>

            <?php if ($disponivel && usuarioLogado()): ?>
                <p>Escolha o dia em que este exemplar volta a ficar disponível no catálogo.</p>
                <form action="pedido.php" method="POST">
                    <input type="hidden" name="acao" value="emprestimo">
                    <input type="hidden" name="livro_id" value="<?= (int) $livro['id']; ?>">
                    <label for="data_fim">Disponível novamente em</label>
                    <input type="date" id="data_fim" name="data_fim"
                        min="<?= proteger($limites['min']); ?>"
                        max="<?= proteger($limites['max']); ?>"
                        required>
                    <button type="submit" class="botao botao-claro">Confirmar empréstimo</button>
                </form>
            <?php elseif (!$disponivel): ?>
                <p>O exemplar da estante está emprestado e volta a ficar disponível em <?= proteger(formatarData($livro['disponivel_em'])); ?>.</p>
            <?php else: ?>
                <p>Escolha a data de devolução depois de entrar na sua conta. A retirada é no endereço acima.</p>
                <a href="login.php" class="botao botao-claro">Entrar para emprestar</a>
            <?php endif; ?>
        </div>

        <div class="bloco-acao" id="comprar">
            <h2>Comprar</h2>
            <p>A compra não bloqueia o exemplar de empréstimo. Retire o livro no endereço acima.</p>
            <?php if (usuarioLogado()): ?>
                <form action="pedido.php" method="POST">
                    <input type="hidden" name="acao" value="compra">
                    <input type="hidden" name="livro_id" value="<?= (int) $livro['id']; ?>">
                    <button type="submit" class="botao">Comprar por R$ <?= number_format($livro['preco'], 2, ',', '.'); ?></button>
                </form>
            <?php else: ?>
                <a href="login.php" class="botao">Entrar para comprar</a>
            <?php endif; ?>
        </div>

        <?php if (usuarioLogado() && podeCadastrarLivros()): ?>
            <a href="admin.php?editar=<?= (int) $livro['id']; ?>" class="botao-secundario">Editar cadastro</a>
        <?php endif; ?>
    </div>

</section>

<?php require "footer.php"; ?>
