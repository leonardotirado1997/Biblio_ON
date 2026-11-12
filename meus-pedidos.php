<?php
require_once "funcoes.php";

exigirLogin();

$stmt = $pdo->prepare(
    "SELECT pedidos.*, livros.titulo, livros.autor
     FROM pedidos
     INNER JOIN livros ON livros.id = pedidos.livro_id
     WHERE pedidos.usuario_id = ?
     ORDER BY pedidos.data_pedido DESC"
);
$stmt->execute([$_SESSION['usuario_id']]);
$pedidos = $stmt->fetchAll();

require "header.php";
?>

<section class="painel-usuario">

    <h1>Meus pedidos</h1>
    <p class="intro">Empréstimos e compras, com retirada em <?= proteger(enderecoRetirada()); ?>.</p>

    <?php if (count($pedidos) === 0): ?>

        <p class="vazio">Você ainda não emprestou nem comprou nenhum livro.</p>
        <a href="index.php#catalogo" class="botao">Explorar catálogo</a>

    <?php else: ?>

        <div class="tabela-container">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Livro</th>
                        <th>Tipo</th>
                        <th>Situação</th>
                        <th>Retirada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td data-label="Livro">
                                <a href="livro.php?id=<?= (int) $pedido['livro_id']; ?>">
                                    <?= proteger($pedido['titulo']); ?>
                                </a>
                                <span class="categoria"><?= proteger($pedido['autor']); ?></span>
                            </td>
                            <td data-label="Tipo">
                                <?= $pedido['tipo'] === 'emprestimo' ? 'Empréstimo' : 'Compra'; ?>
                            </td>
                            <td data-label="Situação">
                                <?php if ($pedido['tipo'] === 'emprestimo'): ?>
                                    <?php if ($pedido['data_fim'] > date('Y-m-d')): ?>
                                        Emprestado. Disponível em <?= proteger(formatarData($pedido['data_fim'])); ?>
                                    <?php else: ?>
                                        Devolvido em <?= proteger(formatarData($pedido['data_fim'])); ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Compra confirmada
                                <?php endif; ?>
                            </td>
                            <td data-label="Retirada">
                                <?= proteger($pedido['retirada'] ?: enderecoRetirada()); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

</section>

<?php require "footer.php"; ?>
