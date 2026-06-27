<?php
include "config/db.php";

$erro = "";
$nome = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome        = isset($_POST["nome"]) ? trim($_POST["nome"]) : '';
    $email       = isset($_POST["email"]) ? trim($_POST["email"]) : '';
    $passwordRaw = isset($_POST["password"]) ? $_POST["password"] : '';

    if (strlen($nome) < 3 || strlen($nome) > 30 || !preg_match('/^[\p{L}0-9 \-\.\']+$/u', $nome)) {
        $erro = "Nome inválido. Use 3 a 30 caracteres, apenas letras, números e espaços.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Por favor, introduza um email válido.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $passwordRaw)) {
        $erro = "Password fraca. Use pelo menos 8 caracteres com maiúscula, minúscula, número e símbolo.";
    } else {
        $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

        $stmtCheck = $conn->prepare("SELECT email_utilizador, google_id FROM users WHERE email_utilizador = ?");
        $stmtCheck->bind_param("s", $email);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $stmtCheck->close();

        if ($resultCheck->num_rows > 0) {
        $userExist = $resultCheck->fetch_assoc();
        $erro = !empty($userExist['google_id'])
            ? "Este email já está registado via Google. Por favor, faça login com a sua conta Google."
            : "O email já está associado a uma conta. Faça login ou escolha outro.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (nome_utilizador, email_utilizador, password_utilizador, tipo_utilizador) VALUES (?, ?, ?, 'cliente')");
        $stmt->bind_param("sss", $nome, $email, $password);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: index.php?sucesso=1");
            exit;
        } else {
            $erro = "Erro ao criar conta: " . $conn->error;
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta — SmartRega</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_pap.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="register">

<header>
    <a href="index.php" class="logo"><img src="img/logo.png" alt="SmartRega"></a>
    <nav>
        <a href="index.php">Login</a>
        <a href="projeto.php">O Projeto</a>
        <a href="register.php">Criar Conta</a>
    </nav>
</header>

<main class="hero">
    <section class="hero-text">
        <h1>Junte-se à<br><span>SmartRega</span></h1>
        <p>Crie a sua conta para começar a monitorizar e controlar os seus sistemas de rega.</p>

        <div class="hero-features">
            <div class="hero-feature-item">
                <span class="icon"><i class="fas fa-check"></i></span>
                <span>Controlo de 4 Bombas de Água</span>
            </div>
            <div class="hero-feature-item">
                <span class="icon"><i class="fas fa-check"></i></span>
                <span>Gráficos de Humidade em Tempo Real</span>
            </div>
            <div class="hero-feature-item">
                <span class="icon"><i class="fas fa-check"></i></span>
                <span>Sistema de Rega Inteligente</span>
            </div>
            <div class="hero-feature-item">
                <span class="icon"><i class="fas fa-check"></i></span>
                <span>Agendamento automático de regas</span>
            </div>
        </div>
    </section>

    <section class="login-container">
        <div class="login-box">
            <h2>Criar Nova Conta</h2>

            <?php if ($erro != ""): ?>
                <p class="erro"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>

            <form method="POST">
                <input type="text"     name="nome"     placeholder="Nome de Utilizador"        required maxlength="30" value="<?php echo htmlspecialchars($nome); ?>">
                <input type="email"    name="email"    placeholder="Escolha o seu Email"   required value="<?php echo htmlspecialchars($email); ?>">
                <input type="password" name="password" placeholder="Escolha uma Password" required>
                <div style="font-size:0.9rem; color:#bbb; margin:10px 0 0; line-height:1.4;">
                    Password: mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo.
                </div>
                <button type="submit" style="background: linear-gradient(135deg, var(--secondary-color), #0277bd); margin-top:12px;">
                    Criar Conta
                </button>
            </form>

            <div style="text-align:center; margin:20px 0; color:#bbb; font-size:0.8rem; display:flex; align-items:center; gap:10px;">
                <div style="flex:1; height:1px; background:#eee;"></div>
                OU
                <div style="flex:1; height:1px; background:#eee;"></div>
            </div>

            <div id="g_id_onload"
     			data-client_id="tapado_por_segurança.apps.googleusercontent.com"
     			data-context="signin"
     			data-ux_mode="popup"
     			data-login_uri="https://smartrega-afonso.fwh.is/google-callback.php"
     			data-auto_prompt="false">
			</div>
            <div class="g_id_signin"
                 data-type="standard" data-shape="rectangular"
                 data-theme="outline" data-text="signup_with"
                 data-size="large"    data-logo_alignment="left"
                 data-width="100%">
            </div>

            <div style="text-align:center; margin-top:20px;">
                <a href="index.php" style="color:#888; font-size:0.9rem; text-decoration:none;">
                    ← Já tenho conta
                </a>
            </div>
        </div>
    </section>
</main>

<footer class="footer-simples">
    <div class="footer-linha-verde"></div>
    <div class="footer-conteudo">
        <p><strong>SmartRega</strong> &copy; <?php echo date("Y"); ?> — Todos os direitos reservados</p>
        <p class="footer-autor">Desenvolvido por Afonso Inácio | PAP TGPSI</p>
    </div>
</footer>

<script src="js/java-pap.js"></script>
</body>
</html>
