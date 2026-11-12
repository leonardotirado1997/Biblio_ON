<?php
require_once "funcoes.php";

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $setor = trim($_POST['setor'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email) || empty($setor) || empty($matricula) || empty($senha)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        try {
            // Verifica se o e-mail já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $erro = "Este e-mail já está cadastrado.";
            } else {
                // Criptografa a senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // Insere no banco (sem a coluna 'tipo' para evitar erros)
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, setor, matricula, senha) VALUES (?, ?, ?, ?, ?)");
                
                if ($stmt->execute([$nome, $email, $setor, $matricula, $senha_hash])) {
                    // Redireciona para o login após o sucesso
                    header("Location: login.php?cadastro=sucesso");
                    exit;
                } else {
                    $erro = "Erro ao realizar o cadastro. Tente novamente.";
                }
            }
        } catch (PDOException $e) {
            // ISSO VAI MOSTRAR O ERRO EXATO DO MYSQL NA TELA
            $erro = "Erro no Banco de Dados: " . $e->getMessage();
        }
    }
}

require "header.php";
?>

<section class="formulario">

    <h1>Crie sua conta</h1>
    <p>Cadastre-se para aproveitar o Bibi-ON.</p>

    <?php if ($erro): ?>
        <p class="erro"><?= proteger($erro); ?></p>
    <?php endif; ?>

    <form method="POST">
        
        <label for="nome">Nome completo</label>
        <input type="text" id="nome" name="nome" placeholder="Digite seu nome completo" required>

        <label for="email">E-mail corporativo</label>
        <input type="email" id="email" name="email" placeholder="nome@empresa.com" required>

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

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" placeholder="Crie uma senha forte" required>

        <label for="confirmar_senha">Confirmar Senha</label>
        <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Digite a senha novamente" required>

        <button type="submit" class="botao">Cadastrar</button>

    </form>

    <p style="margin-top: 15px; text-align: center; font-size: 14px;">
        Já possui uma conta? <a href="login.php" style="color: #9a7000; font-weight: bold;">Entrar</a>
    </p>

</section>

<?php require "footer.php"; ?>