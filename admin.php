<?php
require_once "funcoes.php";

exigirAdmin();

$mensagem = "";
$erro = "";

function salvarImagem($arquivo)
{

    if (
        !isset($arquivo) ||
        $arquivo['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro ao enviar a imagem.");
    }

    if ($arquivo['size'] > 3 * 1024 * 1024) {
        throw new Exception("A imagem deve ter no máximo 3 MB.");
    }

    $tipos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];

    $tipo = mime_content_type($arquivo['tmp_name']);

    if (!isset($tipos[$tipo])) {
        throw new Exception("Envie uma imagem JPG, PNG ou WEBP.");
    }

    if (!is_dir(__DIR__ . "/imagens")) {
        mkdir(__DIR__ . "/imagens", 0755, true);
    }

    $nome = bin2hex(random_bytes(16)) . "." . $tipos[$tipo];

    $destino = __DIR__ . "/imagens/" . $nome;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new Exception("Não foi possível salvar a imagem.");
    }

    return $nome;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $acao = $_POST['acao'] ?? '';

        if ($acao === 'excluir') {

            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM livros WHERE id = ?");
                $stmt->execute([$id]);
                $mensagem = "Livro excluído com sucesso!";
            }

        } elseif ($acao === 'salvar') {

            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $titulo = trim($_POST['titulo'] ?? '');
            $autor = trim($_POST['autor'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $categoria = trim($_POST['categoria'] ?? '');
            $preco = filter_var($_POST['preco'] ?? '', FILTER_VALIDATE_FLOAT);

            if (
                $titulo === '' ||
                $autor === '' ||
                $categoria === '' ||
                $preco === false ||
                $preco < 0
            ) {
                throw new Exception("Preencha os campos corretamente.");
            }

            $imagem = salvarImagem($_FILES['imagem'] ?? null);

            if ($id) {

                if ($imagem) {

                    $stmt = $pdo->prepare(
                        "UPDATE livros
                         SET titulo=?, autor=?, descricao=?,
                             categoria=?, preco=?, imagem=?
                         WHERE id=?"
                    );

                    $stmt->execute([
                        $titulo,
                        $autor,
                        $descricao,
                        $categoria,
                        $preco,
                        $imagem,
                        $id
                    ]);

                } else {

                    $stmt = $pdo->prepare(
                        "UPDATE livros
                         SET titulo=?, autor=?, descricao=?,
                             categoria=?, preco=?
                         WHERE id=?"
                    );

                    $stmt->execute([
                        $titulo,
                        $autor,
                        $descricao,
                        $categoria,
                        $preco,
                        $id
                    ]);
                }

                $mensagem = "Livro atualizado com sucesso!";

            } else {

                $stmt = $pdo->prepare(
                    "INSERT INTO livros
                     (titulo, autor, descricao, categoria, preco, imagem)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                $stmt->execute([
                    $titulo,
                    $autor,
                    $descricao,
                    $categoria,
                    $preco,
                    $imagem
                ]);

                $mensagem = "Livro cadastrado com sucesso!";
            }
        }

    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

$editar = null;

if (isset($_GET['editar'])) {

    $idEditar = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);

    if ($idEditar) {
        $stmt = $pdo->prepare("SELECT * FROM livros WHERE id = ?");
        $stmt->execute([$idEditar]);
        $editar = $stmt->fetch();
    }
}

$livros = $pdo->query("SELECT * FROM livros ORDER BY id DESC")->fetchAll();

require "header.php";
?>

<section class="admin">

    <h1>Painel administrativo</h1>
    <p>Gerencie os livros disponíveis no Bibli-ON.</p>

    <?php if ($mensagem): ?>
        <p class="sucesso"><?= proteger($mensagem); ?></p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p class="erro"><?= proteger($erro); ?></p>
    <?php endif; ?>

    <div class="formulario admin-form">

        <h2><?= $editar ? 'Editar livro' : 'Cadastrar novo livro'; ?></h2>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="acao" value="salvar">

            <input type="hidden" name="id" value="<?= proteger($editar['id'] ?? ''); ?>">

            <label>Título</label>
            <input type="text" name="titulo" value="<?= proteger($editar['titulo'] ?? ''); ?>" required>

            <label>Autor</label>
            <input type="text" name="autor" value="<?= proteger($editar['autor'] ?? ''); ?>" required>

            <label>Descrição</label>
            <textarea name="descricao"><?= proteger($editar['descricao'] ?? ''); ?></textarea>

            <label>Categoria</label>
            <input type="text" name="categoria" value="<?= proteger($editar['categoria'] ?? ''); ?>" required>

            <label>Preço</label>
            <input type="number" name="preco" step="0.01" min="0" value="<?= proteger($editar['preco'] ?? '0.00'); ?>"
                required>

            <label>Capa do livro</label>
            <input type="file" name="imagem" accept="image/jpeg,image/png,image/webp">

            <button type="submit" class="botao">
                <?= $editar ? 'Salvar alterações' : 'Cadastrar livro'; ?>
            </button>

            <?php if ($editar): ?>
                <a href="admin.php" class="botao-secundario">Cancelar edição</a>
            <?php endif; ?>

        </form>

    </div>

    <h2>Livros cadastrados</h2>

    <div class="tabela-container">

        <table class="tabela">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($livros as $livro): ?>

                    <tr>
                        <td><?= $livro['id']; ?></td>
                        <td><?= proteger($livro['titulo']); ?></td>
                        <td><?= proteger($livro['autor']); ?></td>
                        <td><?= proteger($livro['categoria']); ?></td>
                        <td>R$ <?= number_format($livro['preco'], 2, ',', '.'); ?></td>

                        <td>
                            <a href="admin.php?editar=<?= $livro['id']; ?>">
                                Editar
                            </a>

                            <form method="POST" class="form-excluir"
                                onsubmit="return confirm('Deseja excluir este livro?');">

                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= $livro['id']; ?>">

                                <button type="submit" class="excluir">
                                    Excluir
                                </button>

                            </form>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>

<?php require "footer.php"; ?>