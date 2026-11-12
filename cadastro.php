<?php
require_once "funcoes.php";

$erro = "";
$nome = "";
$email = "";
$tipo = "usuario";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $tipo = $_POST['tipo'] ?? 'usuario';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (!in_array($tipo, ['usuario', 'funcionario'], true)) {
        $tipo = 'usuario';
    }

    if ($nome === '' || $email === '' || $senha === '') {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Informe um e-mail válido.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $erro = "Este e-mail já está cadastrado.";
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    "INSERT INTO usuarios (nome, email, senha, tipo)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([$nome, $email, $senha_hash, $tipo]);

                header("Location: login.php?cadastro=sucesso");
                exit;
            }
        } catch (PDOException $e) {
            $erro = "Não foi possível concluir o cadastro. Tente novamente.";
        }
    }
}

require "header.php";
?>

<section class="formulario">

    <h1>Crie sua conta</h1>
    <p>Leitores pesquisam, emprestam e compram. Funcionários cadastram os livros.</p>

    <?php if ($erro): ?>
        <p class="erro"><?= proteger($erro); ?></p>
    <?php endif; ?>

    <form method="POST">

        <label for="tipo">Tipo de conta</label>
        <select id="tipo" name="tipo" required>
            <option value="usuario" <?= $tipo !== 'funcionario' ? 'selected' : ''; ?>>
                Leitor
            </option>
            <option value="funcionario" <?= $tipo === 'funcionario' ? 'selected' : ''; ?>>
                Funcionário da biblioteca
            </option>
        </select>

        <label for="nome">Nome completo</label>
        <input type="text" id="nome" name="nome" placeholder="Digite seu nome completo"
            value="<?= proteger($nome); ?>" required>

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="seu@email.com"
            value="<?= proteger($email); ?>" required>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Crie uma senha" required>

        <label for="confirmar_senha">Confirmar senha</label>
        <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Digite a senha novamente" required>

        <button type="submit" class="botao">Cadastrar</button>

    </form>

    <p class="link-conta">
        Já possui uma conta? <a href="login.php">Entrar</a>
    </p>

</section>

<?php require "footer.php"; ?>
