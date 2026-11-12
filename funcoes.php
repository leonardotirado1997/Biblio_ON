<?php

require_once __DIR__ . "/config.php";

function proteger($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function usuarioLogado() {
    return isset($_SESSION['usuario_id']);
}

function exigirLogin() {
    if (!usuarioLogado()) {
        header("Location: login.php");
        exit;
    }
}

function podeCadastrarLivros() {
    return in_array($_SESSION['tipo'] ?? '', ['funcionario', 'admin'], true);
}

function exigirAdmin() {
    exigirLogin();

    if (!podeCadastrarLivros()) {
        header("Location: index.php");
        exit;
    }
}

function enderecoRetirada() {
    return 'Avenida Rocha Pombo, 3028';
}

function formatarData($data) {
    $data = substr((string) $data, 0, 10);
    $dt = DateTime::createFromFormat('Y-m-d', $data);

    return $dt ? $dt->format('d/m/Y') : '';
}

function limitesEmprestimo() {
    return [
        'min' => (new DateTime('tomorrow'))->format('Y-m-d'),
        'max' => (new DateTime('+30 days'))->format('Y-m-d'),
    ];
}

function validarDataDevolucao($data) {
    $limites = limitesEmprestimo();
    $dt = DateTime::createFromFormat('Y-m-d', (string) $data);

    if (!$dt || $dt->format('Y-m-d') !== $data) {
        return 'Informe a data em que o livro volta a ficar disponível.';
    }

    if ($data < $limites['min'] || $data > $limites['max']) {
        return 'Escolha uma data entre amanhã e os próximos 30 dias.';
    }

    return null;
}

function emprestimoAtivo($livroId) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT data_fim FROM pedidos
         WHERE livro_id = ? AND tipo = 'emprestimo' AND data_fim > CURDATE()
         ORDER BY data_fim ASC
         LIMIT 1"
    );
    $stmt->execute([$livroId]);
    $emprestimo = $stmt->fetch();

    return $emprestimo['data_fim'] ?? null;
}

function arquivoCapa($imagem) {
    $nome = basename((string) $imagem);

    if ($nome === '' || $nome === '.' || $nome === '..') {
        return null;
    }

    $caminho = __DIR__ . '/imagens/' . $nome;

    return is_file($caminho) ? $nome : null;
}

function urlCapa($imagem) {
    $nome = arquivoCapa($imagem);

    return $nome ? 'imagens/' . rawurlencode($nome) : null;
}