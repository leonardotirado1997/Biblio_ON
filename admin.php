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
                $disponivelEm = emprestimoAtivo($id);

                if ($disponivelEm) {
                    throw new Exception(
                        "Este exemplar está emprestado e volta a ficar disponível em "
                        . formatarData($disponivelEm)
                        . ". A exclusão fica liberada depois dessa data."
                    );
                }

                $stmt = $pdo->prepare("SELECT imagem FROM livros WHERE id = ?");
                $stmt->execute([$id]);
                $livroAtual = $stmt->fetch();

                $stmt = $pdo->prepare("DELETE FROM pedidos WHERE livro_id = ?");
                $stmt->execute([$id]);

                $stmt = $pdo->prepare("DELETE FROM livros WHERE id = ?");
                $stmt->execute([$id]);

                if ($livroAtual && !empty($livroAtual['imagem'])) {
                    $caminho = __DIR__ . "/imagens/" . basename($livroAtual['imagem']);
                    if (is_file($caminho)) {
                        unlink($caminho);
                    }
                }

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

                    $stmt = $pdo->prepare("SELECT imagem FROM livros WHERE id = ?");
                    $stmt->execute([$id]);
                    $livroAtual = $stmt->fetch();

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

                    if ($livroAtual && !empty($livroAtual['imagem']) && $livroAtual['imagem'] !== $imagem) {
                        $caminho = __DIR__ . "/imagens/" . basename($livroAtual['imagem']);
                        if (is_file($caminho)) {
                            unlink($caminho);
                        }
                    }

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

                if (!$imagem) {
                    throw new Exception("Envie a capa do livro (JPG, PNG ou WEBP).");
                }

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

    } catch (PDOException $e) {
        $erro = ($acao ?? '') === 'excluir'
            ? "Não foi possível excluir. Um livro já adquirido permanece no acervo."
            : "Não foi possível salvar o livro.";
    } catch (Exception $e) {
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

$livros = $pdo->query(
    "SELECT livros.*, (
        SELECT MIN(p.data_fim) FROM pedidos p
        WHERE p.livro_id = livros.id
          AND p.tipo = 'emprestimo'
          AND p.data_fim > CURDATE()
    ) AS disponivel_em
    FROM livros
    ORDER BY livros.id DESC"
)->fetchAll();

$livroForm = $editar ?: [
    'id' => '',
    'titulo' => '',
    'autor' => '',
    'descricao' => '',
    'categoria' => '',
    'preco' => '0.00',
    'imagem' => '',
];

require "header.php";
?>

<section class="admin">

    <h1>Cadastro de livros</h1>
    <p class="intro">Inclua a obra com a capa. Enquanto houver empréstimo ativo, o exemplar não pode ser excluído.</p>

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

            <input type="hidden" name="id" value="<?= proteger($livroForm['id']); ?>">

            <label>Título</label>
            <input type="text" name="titulo" value="<?= proteger($livroForm['titulo']); ?>" required>

            <label>Autor</label>
            <input type="text" name="autor" value="<?= proteger($livroForm['autor']); ?>" required>

            <label>Descrição</label>
            <textarea name="descricao"><?= proteger($livroForm['descricao']); ?></textarea>

            <label>Categoria</label>
            <input type="text" name="categoria" value="<?= proteger($livroForm['categoria']); ?>" required>

            <label>Preço</label>
            <input type="number" name="preco" step="0.01" min="0" value="<?= proteger($livroForm['preco']); ?>"
                required>

            <label>Capa do livro</label>
            <?php $capaAtual = urlCapa($livroForm['imagem']); ?>
            <?php if ($capaAtual): ?>
                <img class="capa-preview" src="<?= proteger($capaAtual); ?>" alt="Capa atual de <?= proteger($livroForm['titulo']); ?>">
            <?php endif; ?>
            <input type="file" name="imagem" accept="image/jpeg,image/png,image/webp" <?= $editar ? '' : 'required'; ?>>
            <p class="ajuda">JPG, PNG ou WEBP, com no máximo 3 MB.<?= $editar ? ' Envie outra imagem apenas se quiser trocar a capa.' : ''; ?></p>

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
                    <th>Capa</th>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Empréstimo</th>
                    <th>Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($livros as $livro): ?>

                    <tr>
                        <td data-label="Capa">
                            <?php $capa = urlCapa($livro['imagem'] ?? ''); ?>
                            <?php if ($capa): ?>
                                <img class="capa-mini" src="<?= proteger($capa); ?>" alt="">
                            <?php else: ?>
                                <span class="sem-capa">Sem capa</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Título"><?= proteger($livro['titulo']); ?></td>
                        <td data-label="Autor"><?= proteger($livro['autor']); ?></td>
                        <td data-label="Categoria"><?= proteger($livro['categoria']); ?></td>
                        <td data-label="Preço">R$ <?= number_format($livro['preco'], 2, ',', '.'); ?></td>
                        <td data-label="Empréstimo">
                            <?php if (!empty($livro['disponivel_em'])): ?>
                                Disponível em <?= proteger(formatarData($livro['disponivel_em'])); ?>
                            <?php else: ?>
                                Disponível
                            <?php endif; ?>
                        </td>

                        <td data-label="Ações">
                            <div class="acoes">
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
                            </div>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>

<?php require "footer.php"; ?>