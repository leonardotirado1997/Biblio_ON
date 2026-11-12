<?php
require_once "funcoes.php";

exigirLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $livroId = filter_input(INPUT_POST, 'livro_id', FILTER_VALIDATE_INT);

    if (!$livroId || !in_array($acao, ['emprestimo', 'compra'], true)) {
        header("Location: index.php");
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM livros WHERE id = ?");
    $stmt->execute([$livroId]);
    $livro = $stmt->fetch();

    if (!$livro) {
        header("Location: index.php");
        exit;
    }

    try {
        if ($acao === 'emprestimo') {
            $dataFim = $_POST['data_fim'] ?? '';
            $erroData = validarDataDevolucao($dataFim);

            if ($erroData) {
                throw new Exception($erroData);
            }

            $pdo->beginTransaction();

            $trava = $pdo->prepare("SELECT id FROM livros WHERE id = ? FOR UPDATE");
            $trava->execute([$livroId]);

            $ativo = $pdo->prepare(
                "SELECT id FROM pedidos
                 WHERE livro_id = ? AND tipo = 'emprestimo' AND data_fim > CURDATE()
                 LIMIT 1
                 FOR UPDATE"
            );
            $ativo->execute([$livroId]);

            if ($ativo->fetch()) {
                throw new Exception("Este exemplar já está emprestado.");
            }

            $pedido = $pdo->prepare(
                "INSERT INTO pedidos (usuario_id, livro_id, tipo, data_fim, retirada, status)
                 VALUES (?, ?, 'emprestimo', ?, ?, 'Emprestado')"
            );
            $pedido->execute([
                $_SESSION['usuario_id'],
                $livroId,
                $dataFim,
                enderecoRetirada(),
            ]);

            $pdo->commit();

            $_SESSION['pedido_flash'] = [
                'tipo' => 'emprestimo',
                'titulo' => $livro['titulo'],
                'data_fim' => $dataFim,
            ];
        } else {
            $pedido = $pdo->prepare(
                "INSERT INTO pedidos (usuario_id, livro_id, tipo, retirada, status)
                 VALUES (?, ?, 'compra', ?, 'Compra confirmada')"
            );
            $pedido->execute([
                $_SESSION['usuario_id'],
                $livroId,
                enderecoRetirada(),
            ]);

            $_SESSION['pedido_flash'] = [
                'tipo' => 'compra',
                'titulo' => $livro['titulo'],
                'preco' => $livro['preco'],
            ];
        }

        header("Location: pedido.php");
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['erro_livro'] = "Não foi possível registrar o pedido. Tente novamente.";
        header("Location: livro.php?id=" . $livroId);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['erro_livro'] = $e->getMessage();
        header("Location: livro.php?id=" . $livroId);
        exit;
    }
}

$flash = $_SESSION['pedido_flash'] ?? null;
unset($_SESSION['pedido_flash']);

if (!$flash) {
    header("Location: index.php");
    exit;
}

require "header.php";
?>

<section class="formulario confirmacao">

    <?php if (($flash['tipo'] ?? '') === 'emprestimo'): ?>

        <h1>Empréstimo confirmado</h1>
        <p><strong><?= proteger($flash['titulo']); ?></strong> fica reservado para você até a devolução.</p>
        <p>Retire o exemplar em <strong><?= proteger(enderecoRetirada()); ?></strong>.</p>
        <p>Ele volta a ficar disponível no catálogo em <strong><?= proteger(formatarData($flash['data_fim'] ?? '')); ?></strong>.</p>

    <?php else: ?>

        <h1>Compra confirmada</h1>
        <p>Seu pedido de <strong><?= proteger($flash['titulo']); ?></strong> foi registrado.</p>
        <p class="preco">R$ <?= number_format((float) ($flash['preco'] ?? 0), 2, ',', '.'); ?></p>
        <p>Retire o livro em <strong><?= proteger(enderecoRetirada()); ?></strong>.</p>
        <p>Esta é uma demonstração acadêmica. Nenhum pagamento foi realizado.</p>

    <?php endif; ?>

    <a href="meus-pedidos.php" class="botao">Ver meus pedidos</a>
    <a href="index.php" class="botao-secundario">Voltar ao catálogo</a>

</section>

<?php require "footer.php"; ?>
