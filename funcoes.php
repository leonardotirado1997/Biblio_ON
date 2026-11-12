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

function setoresBiblioteca() {
    return [
        'acervo' => 'Acervo',
        'catalogacao' => 'Catalogação',
        'atendimento' => 'Atendimento ao leitor',
        'referencia' => 'Referência e pesquisa',
        'administrativo' => 'Administração da biblioteca',
    ];
}

function opcoesSetor($atual = '') {
    $html = '';

    foreach (setoresBiblioteca() as $valor => $rotulo) {
        $selected = ((string) $atual === (string) $valor) ? ' selected' : '';
        $html .= '<option value="' . proteger($valor) . '"' . $selected . '>'
            . proteger($rotulo) . '</option>';
    }

    return $html;
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