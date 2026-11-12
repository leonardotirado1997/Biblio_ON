<?php
require_once "funcoes.php";

$pesquisa = trim($_GET['pesquisa'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$sql = "SELECT livros.*, (
            SELECT MIN(p.data_fim) FROM pedidos p
            WHERE p.livro_id = livros.id
              AND p.tipo = 'emprestimo'
              AND p.data_fim > CURDATE()
        ) AS disponivel_em
        FROM livros
        WHERE 1=1";
$params = [];

if ($pesquisa !== '') {
    $sql .= " AND (titulo LIKE ? OR autor LIKE ?)";
    $params[] = "%$pesquisa%";
    $params[] = "%$pesquisa%";
}

if ($categoria !== '') {
    $sql .= " AND categoria = ?";
    $params[] = $categoria;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$livros = $stmt->fetchAll();

$categorias = $pdo->query(
    "SELECT DISTINCT categoria FROM livros
     WHERE categoria IS NOT NULL AND categoria <> ''
     ORDER BY categoria"
)->fetchAll();

require "header.php";
?>

<section class="banner">
    <div>
        <h1>Descubra e leve seu próximo livro</h1>
        <p>Pesquise o catálogo, consulte os detalhes de cada obra e escolha: emprestar o exemplar ou comprar.</p>
        <a href="#catalogo" class="botao">Explorar livros</a>
    </div>
</section>

<section class="passos">
    <article>
        <h3>Explore</h3>
        <p>Busque por título, autor ou categoria e abra a ficha do livro.</p>
    </article>
    <article>
        <h3>Empreste ou compre</h3>
        <p>O empréstimo tem data para o exemplar voltar ao catálogo. A compra não tira o livro da estante.</p>
    </article>
    <article>
        <h3>Retire no endereço</h3>
        <p>A retirada é presencial, em <?= proteger(enderecoRetirada()); ?>.</p>
    </article>
</section>

<section class="busca">
    <h2>Encontre um livro</h2>

    <form method="GET" action="index.php">
        <input type="text" name="pesquisa" placeholder="Digite o título ou autor..."
            value="<?= proteger($pesquisa); ?>">

        <select name="categoria">
            <option value="">Todas as categorias</option>

            <?php foreach ($categorias as $cat): ?>
                <option value="<?= proteger($cat['categoria']); ?>" <?= $categoria === $cat['categoria'] ? 'selected' : ''; ?>>
                    <?= proteger($cat['categoria']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="botao">Pesquisar</button>
    </form>
</section>

<section id="catalogo" class="catalogo">

    <h2>Nosso catálogo</h2>

    <div class="livros">

        <?php if (count($livros) > 0): ?>

            <?php foreach ($livros as $livro): ?>

                <article class="card-livro">

                    <?php $capa = urlCapa($livro['imagem'] ?? ''); ?>
                    <?php if ($capa): ?>
                        <img src="<?= proteger($capa); ?>" alt="<?= proteger($livro['titulo']); ?>">
                    <?php else: ?>
                        <div class="sem-imagem">Sem capa</div>
                    <?php endif; ?>

                    <div class="card-conteudo">
                        <?php if (!empty($livro['disponivel_em'])): ?>
                            <span class="selo-status emprestado">Emprestado</span>
                        <?php else: ?>
                            <span class="selo-status disponivel">Disponível</span>
                        <?php endif; ?>

                        <h3><?= proteger($livro['titulo']); ?></h3>

                        <p>Autor: <?= proteger($livro['autor']); ?></p>

                        <p class="categoria">
                            <?= proteger($livro['categoria']); ?>
                        </p>

                        <?php if (!empty($livro['disponivel_em'])): ?>
                            <p class="categoria">Volta em <?= proteger(formatarData($livro['disponivel_em'])); ?></p>
                        <?php endif; ?>

                        <p class="preco">
                            R$ <?= number_format($livro['preco'], 2, ',', '.'); ?>
                        </p>

                        <a href="livro.php?id=<?= $livro['id']; ?>" class="botao">
                            Ver livro
                        </a>
                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <p>Nenhum livro encontrado.</p>

        <?php endif; ?>

    </div>

</section>

<?php require "footer.php"; ?>