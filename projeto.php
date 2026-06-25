<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>O Projeto — SmartRega</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_pap.css">
</head>
<body class="projeto">

<header>
    <a href="index.php" class="logo"><img src="img/logo.png" alt="SmartRega"></a>
    <nav>
        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="dashboard.php">Painel</a>
            <a href="projeto.php" class="nav-active">O Projeto</a>
            <?php if (isset($_SESSION["tipo"]) && $_SESSION["tipo"] == "admin"): ?>
                <a href="gestoes.php"><i class="fas fa-sliders-h"></i> Gestões</a>
            <?php endif; ?>
            <span class="user-menu">
                <button type="button" class="user-menu-trigger">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION["nome"]);
                        if (isset($_SESSION["tipo"]) && $_SESSION["tipo"] == "admin"): ?>
                            <span class="badge-admin">ADMIN</span>
                        <?php elseif (isset($_SESSION["tipo"]) && $_SESSION["tipo"] == "cliente_atual"): ?>
                            <span class="badge-cliente-atual">CLIENTE ATUAL</span>
                        <?php elseif (isset($_SESSION["tipo"]) && $_SESSION["tipo"] == "cliente"): ?>
                            <span class="badge-cliente">CLIENTE</span>
                        <?php endif; ?>
                </button>
                <div class="user-menu-dropdown">
                    <a href="minha_conta.php"><i class="fas fa-calendar-check"></i> Minhas Regas</a>
                    <hr style="margin: 8px 0; border: none; border-top: 1px solid rgba(0,0,0,0.1);">
                    <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a>
                </div>
            </span>
        <?php else: ?>
            <a href="index.php">Login</a>
            <a href="projeto.php" class="nav-active">O Projeto</a>
            <a href="register.php">Criar Conta</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container-projeto">

    <div class="header-projeto">
        <h1><i class="fas fa-leaf"></i> Sobre o Projeto SmartRega</h1>
        <p>Conhece o hardware e a arquitetura deste sistema inteligente de rega.</p>
    </div>

    <div class="section-hardware" id="esp32">
        <div class="text-box">
            <h2><i class="fas fa-microchip"></i> O Cérebro: ESP32</h2>
            <p>O coração de todo o sistema é o microcontrolador <strong>NodeMCU ESP32-C</strong>. Ele é o responsável por fazer a ponte entre o mundo físico e o mundo digital.</p>
            <p>A sua principal vantagem é ter Wi-Fi integrado. O ESP32 recolhe os dados dos sensores de humidade e envia-os via HTTP POST para a base de dados MySQL, lendo em simultâneo as ordens da plataforma web para controlar os relés das bombas.</p>
        </div>
        <div class="img-box">
            <img src="img/IMG_4960.jpeg" alt="Placa ESP32">
        </div>
    </div>

    <div class="section-hardware">
        <div class="text-box">
            <h2><i class="fas fa-droplet"></i> Ação: Bombas e Distribuição</h2>
            <p>Para atuar de forma independente em 4 zonas, utilizamos <strong>4 minibombas de água submersíveis</strong>.</p>
            <p>Quando o ESP32 deteta solo seco, ou quando o utilizador clica no Dashboard, a bomba é ativada e a água é distribuída através de <strong>tubos de vinil transparentes</strong> até à raiz das plantas.</p>
        </div>
        <div class="img-box">
            <img src="img/IMG_4953.jpeg" alt="Bombas de Água">
            <p style="font-size:0.8rem; color:#aaa; margin-top:8px;">Minibombas submersíveis usadas no projeto.</p>
        </div>
    </div>

    <div class="section-hardware">
        <div class="text-box">
            <h2><i class="fas fa-desktop"></i> Feedback Local: Display LCD</h2>
            <p>Apesar do controlo remoto via web, integrámos um <strong>Display LCD 16x2 com módulo I2C</strong>.</p>
            <p>O ecrã mostra em tempo real o IP da rede, o estado da ligação Wi-Fi e qual a bomba que está a ser acionada — fundamental para diagnóstico no local.</p>
        </div>
        <div class="img-box">
            <img src="img/IMG_4959.jpeg" alt="Módulo LCD I2C">
        </div>
    </div>

    <div class="flow-diagram">
        <h2><i class="fas fa-sync"></i> Como Tudo Comunica?</h2>
        <ul>
            <li><strong>1. Leitura:</strong> Os sensores medem a humidade e enviam valores analógicos para o ESP32.</li>
            <li><strong>2. Envio:</strong> O ESP32 conecta-se ao Wi-Fi e faz um POST para <code>api_enviar_sensores.php</code> a cada 30 segundos.</li>
            <li><strong>3. Armazenamento:</strong> O servidor guarda os dados no MySQL. O Dashboard lê-os e atualiza automaticamente a cada 5 segundos.</li>
            <li><strong>4. Controlo:</strong> Ao clicar num botão ou agendar uma rega, a BD muda o estado. O ESP32 lê essa mudança e liga/desliga a bomba físicamente.</li>
        </ul>
    </div>

    <div style="text-align:center;">
        <a href="index.php" class="btn-voltar"></i> Voltar à Página Inicial</a>
    </div>

</div>

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