<?php
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.cookie_httponly', 1);
if ($secure) {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Lax');
session_start();
include "config/db.php";

$erro = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = isset($_POST["email"]) ? $_POST["email"] : '';
    $password = isset($_POST["password"]) ? $_POST["password"] : '';

    $sql  = "SELECT * FROM users WHERE email_utilizador = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $erro = "Email ou password inválidos.";
    } else {
        $user = $result->fetch_assoc();

        if (!empty($user['google_id']) && empty($user['password_utilizador'])) {
            $erro = "Este email está associado a uma conta Google. Use o botão de login com Google.";
        } elseif (!password_verify($password, $user["password_utilizador"])) {
            $erro = "Email ou password inválidos.";
        } else {
            session_regenerate_id(true);
            $_SESSION["user_id"] = $user["id_utilizador"];
            $_SESSION["nome"]    = $user["nome_utilizador"];
            $_SESSION["tipo"]    = $user["tipo_utilizador"];

            header("Location: " . ($_SESSION["tipo"] == "admin" ? "admin_users.php" : "dashboard.php"));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartRega — Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_pap.css">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="home">

<header id="main-header">
    <a href="index.php" class="logo"><img src="img/logo.png" alt="SmartRega"></a>
    <nav>
        <a href="index.php">Login</a>
        <a href="projeto.php">O Projeto</a>
        <a href="register.php">Criar Conta</a>
    </nav>
</header>

<main class="hero">
    <section class="hero-text">
        <h1>Rega Inteligente<br>com o <span>SmartRega</span></h1>
<p>Gere os teus recursos hídricos com sensores de precisão e controlo remoto total do sistema de rega via <a href="projeto.php" style="color:#0288d1; font-weight:600; text-decoration:none; border-bottom: 2px solid #0288d1; padding-bottom:1px; transition: opacity 0.2s;">ESP32</a></p>

        <div class="hero-features">
            <div class="hero-feature-item">
                <span class="icon"><i class="fas fa-droplet"></i></span>
                <span>Controlo de 4 bombas de água em tempo real</span>
            </div>
            <div class="hero-feature-item">
                <span class="icon"><i class="fas fa-chart-bar"></i></span>
                <span>Monitorização de humidade do solo por zona</span>
            </div>
            <div class="hero-feature-item">
                <span class="icon"><i class="fas fa-calendar-alt"></i></span>
                <span>Agendamento automático de regas</span>
            </div>
            <div class="hero-feature-item">
                <span class="icon"><i class="fas fa-lock"></i></span>
                <span>Acesso seguro com conta Google ou email</span>
            </div>
        </div>
    </section>

    <section class="login-container">
        <div class="login-box">
            <h2>Iniciar Sessão</h2>

            <?php if ($erro != ""): ?>
                <p class="erro"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?></p>
            <?php endif; ?>

            <?php if (isset($_GET['sucesso'])): ?>
                <p class="sucesso"><i class="fas fa-check"></i> Conta criada com sucesso! Faça login agora.</p>
            <?php endif; ?>

            <form method="POST">
                <input type="email"    name="email"    placeholder="O seu Email"    required value="<?php echo htmlspecialchars($email); ?>">
                <input type="password" name="password" placeholder="A sua Password" required>
                <button type="submit">Entrar no Sistema</button>
            </form>

            <div style="text-align:center; margin:20px 0; color:#bbb; font-size:0.8rem; display:flex; align-items:center; gap:10px;">
                <div style="flex:1; height:1px; background:#eee;"></div>
                OU
                <div style="flex:1; height:1px; background:#eee;"></div>
            </div>

            <div id="g_id_onload"
                 data-client_id="907180799619-ca0linjn5ollugsc3j2jga8q6hhje2qi.apps.googleusercontent.com"
                 data-context="signin"
                 data-ux_mode="popup"
                 data-login_uri="http://localhost/SITE_PAP/google-callback.php"
                 data-auto_prompt="false">
            </div>
            <div class="g_id_signin"
                 data-type="standard" data-shape="rectangular"
                 data-theme="outline" data-text="signin_with"
                 data-size="large"    data-logo_alignment="left"
                 data-width="100%">
            </div>

            <div style="text-align:center; margin-top:20px;">
                <a href="register.php" style="color:var(--secondary-color); font-size:0.9rem; text-decoration:none; font-weight:500;">
                    Ainda não tem conta? <strong>Crie uma aqui</strong>
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