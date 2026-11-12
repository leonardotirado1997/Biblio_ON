<?php
require_once "funcoes.php";

$pesquisa = trim($_GET['pesquisa'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$sql = "SELECT * FROM livros WHERE 1=1";
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
        <h1>Encontre sua próxima leitura!</h1>
        <p>Explore histórias, descubra autores e encontre livros incríveis.</p>
        <a href="#catalogo" class="botao">Explorar livros</a>
    </div>
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

                    <?php if (!empty($livro['imagem'])): ?>
                        <img src="imagens/<?= proteger(basename($livro['imagem'])); ?>" alt="<?= proteger($livro['titulo']); ?>">
                    <?php else: ?>
                        <div class="sem-imagem">Sem capa</div>
                    <?php endif; ?>

                    <div class="card-conteudo">
                        <h3><?= proteger($livro['titulo']); ?></h3>

                        <p>Autor: <?= proteger($livro['autor']); ?></p>

                        <p class="categoria">
                            <?= proteger($livro['categoria']); ?>
                        </p>

                        <p class="preco">
                            R$ <?= number_format($livro['preco'], 2, ',', '.'); ?>
                        </p>

                        <a href="livro.php?id=<?= $livro['id']; ?>" class="botao">
                            Ver detalhes
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