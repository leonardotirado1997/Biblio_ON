<?php
require_once "funcoes.php";

$erro = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = "Preencha e-mail e senha.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['tipo'] = $usuario['tipo'] ?? 'usuario';

                header("Location: index.php");
                exit;
            }

            $erro = "E-mail ou senha incorretos.";
        } catch (PDOException $e) {
            $erro = "Não foi possível entrar. Tente novamente.";
        }
    }
}

require "header.php";
?>

<section class="formulario">

    <h1>Bem-vindo ao Bibli-ON</h1>
    <p>Entre para consultar o catálogo, emprestar ou comprar um livro.</p>

    <?php if (isset($_GET['cadastro'])): ?>
        <p class="sucesso">Cadastro realizado! Faça seu login.</p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p class="erro"><?= proteger($erro); ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="seu@email.com"
            value="<?= proteger($email); ?>" required>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>

        <button type="submit" class="botao">Entrar</button>
    </form>

    <p class="link-conta">
        Não possui uma conta? <a href="cadastro.php">Cadastre-se</a>
    </p>

</section>

<?php require "footer.php"; ?>
