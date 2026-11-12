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

function exigirAdmin() {
    exigirLogin();

    if (($_SESSION['tipo'] ?? '') !== 'admin') {
        header("Location: index.php");
        exit;
    }
}