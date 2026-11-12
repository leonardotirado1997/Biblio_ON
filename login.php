<?php
require_once "funcoes.php";

$erro = "";
$email = "";
$perfil = "usuario";
$setor = "";
$matricula = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $perfil = $_POST['perfil'] ?? 'usuario';
    $setor = trim($_POST['setor'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');

    if (!in_array($perfil, ['usuario', 'funcionario'], true)) {
        $perfil = 'usuario';
    }

    if ($email === '' || $senha === '') {
        $erro = "Preencha e-mail e senha.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $tipo = $usuario['tipo'] ?? 'usuario';
                $ehEquipe = in_array($tipo, ['funcionario', 'admin'], true);

                if ($ehEquipe && ($setor === '' || $matricula === '')) {
                    $erro = "Contas de funcionário precisam informar setor e matrícula.";
                    $perfil = 'funcionario';
                } elseif ($ehEquipe && ($usuario['setor'] !== $setor || $usuario['matricula'] !== $matricula)) {
                    $erro = "O setor ou a matrícula não conferem com o cadastro.";
                    $perfil = 'funcionario';
                } else {
                    session_regenerate_id(true);

                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nome'] = $usuario['nome'];
                    $_SESSION['tipo'] = $tipo;

                    header("Location: " . ($ehEquipe ? "admin.php" : "index.php"));
                    exit;
                }
            } else {
                $erro = "E-mail ou senha incorretos.";
            }
        } catch (PDOException $e) {
            $erro = "Não foi possível entrar. Tente novamente.";
        }
    }
}

require "header.php";
?>

<section class="formulario">

    <h1>Bem-vindo ao Bibli-ON!</h1>
    <p>Leitores entram para adquirir livros. Funcionários também informam setor e matrícula.</p>

    <?php if (isset($_GET['cadastro'])): ?>
        <p class="sucesso">Cadastro realizado! Faça seu login.</p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p class="erro"><?= proteger($erro); ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <label for="perfil">Entrar como</label>
        <select id="perfil" name="perfil" required>
            <option value="usuario" <?= $perfil !== 'funcionario' ? 'selected' : ''; ?>>
                Leitor
            </option>
            <option value="funcionario" <?= $perfil === 'funcionario' ? 'selected' : ''; ?>>
                Funcionário da biblioteca
            </option>
        </select>

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="seu@email.com"
            value="<?= proteger($email); ?>" required>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>

        <div id="campos-equipe" <?= $perfil === 'funcionario' ? '' : 'hidden'; ?>>
            <label for="setor">Setor</label>
            <select id="setor" name="setor">
                <option value="" <?= $setor === '' ? 'selected' : ''; ?> disabled>
                    Selecione o setor da biblioteca...
                </option>
                <?= opcoesSetor($setor); ?>
            </select>

            <label for="matricula">Matrícula</label>
            <input type="text" id="matricula" name="matricula" placeholder="Ex: 12345"
                value="<?= proteger($matricula); ?>">
        </div>

        <button type="submit" class="botao">Entrar</button>
    </form>

    <p class="link-conta">
        Não possui uma conta? <a href="cadastro.php">Cadastre-se</a>
    </p>

</section>

<script>
(function () {
    var perfil = document.getElementById('perfil');
    var extra = document.getElementById('campos-equipe');
    if (!perfil || !extra) return;

    function atualizar() {
        extra.hidden = perfil.value !== 'funcionario';
    }

    perfil.addEventListener('change', atualizar);
    atualizar();
})();
</script>

<?php require "footer.php"; ?>
