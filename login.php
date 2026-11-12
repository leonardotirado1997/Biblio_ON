<?php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "funcoes.php";

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $setor = trim($_POST['setor'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');

    if (empty($email) || empty($senha) || empty($setor) || empty($matricula)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        try {
            // Busca o usuário pelo E-mail
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            // Verifica se achou o usuário, se a senha bate, o setor e a matrícula conferem
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                
                // Verifica se o setor e a matrícula digitados são iguais aos do banco
                if ($usuario['setor'] === $setor && $usuario['matricula'] === $matricula) {
                    
                    session_regenerate_id(true);

                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['nome'] = $usuario['nome'];

                    // Redireciona para a página inicial
                    header("Location: index.php");
                    exit;

                } else {
                    $erro = "O Setor ou a Matrícula não conferem com o cadastro.";
                }

            } else {
                $erro = "E-mail ou senha incorretos.";
            }
        } catch (PDOException $e) {
            $erro = "Erro no Banco de Dados: " . $e->getMessage();
        }
    }
}

require "header.php";
?>

<section class="formulario">

    <h1>Bem-vindo ao Bibli-ON!</h1>
    <p>Entre na sua conta para continuar.</p>

    <?php if (isset($_GET['cadastro'])): ?>
        <p class="sucesso">Cadastro realizado! Faça seu login.</p>
    <?php endif; ?>

    <?php if ($erro): ?>
        <p class="erro"><?= proteger($erro); ?></p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <label for="email">E-mail corporativo</label>
        <input type="email" id="email" name="email" placeholder="nome@empresa.com" required>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>

        <!-- AQUI ESTÃO OS CAMPOS QUE VOCÊ QUERIA MANTER -->
        <label for="setor">Setor</label>
        <select id="setor" name="setor" required>
            <option value="" disabled selected>Selecione o seu setor...</option>
            <option value="administrativo">Administrativo</option>
            <option value="rh">Recursos Humanos</option>
            <option value="ti">TI / Tecnologia</option>
            <option value="vendas">Vendas</option>
            <option value="estoque">Estoque</option>
        </select>

        <label for="matricula">Matrícula do Funcionário</label>
        <input type="text" id="matricula" name="matricula" placeholder="Ex: 12345" required>

        <button type="submit" class="botao">Entrar</button>
    </form>

    <p style="margin-top: 15px; text-align: center; font-size: 14px;">
        Não possui uma conta? <a href="cadastro.php" style="color: #9a7000; font-weight: bold;">Cadastre-se</a>
    </p>

</section>

<?php require "footer.php"; ?>