<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["tipo"] != "admin") {
    header("Location: index.php");
    exit;
}
$current_page = 'gestoes';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestões — SmartRega</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_pap.css">
</head>
<body>

<header>
    <a href="dashboard.php" class="logo"><img src="img/logo.png" alt="SmartRega"></a>
    <nav>
        <a href="dashboard.php">Painel</a>
        <a href="projeto.php">O Projeto</a>
        <a href="gestoes.php" class="nav-active"><i class="fas fa-sliders-h"></i> Gestões</a>
        <span class="user-menu">
            <button type="button" class="user-menu-trigger">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION["nome"]); ?>
                <span class="badge-admin">ADMIN</span>
            </button>
            <div class="user-menu-dropdown">
                <a href="minha_conta.php"><i class="fas fa-calendar-check"></i> Minhas Regas</a>
                <hr style="margin: 8px 0; border: none; border-top: 1px solid rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </span>
    </nav>
</header>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-sliders-h"></i> Gestões do Sistema</h1>
        <p>Controla utilizadores, regas agendadas e configurações gerais.</p>
    </div>

    <div class="gestoes-grid">
        <!-- CARD UTILIZADORES -->
        <div class="gestao-card">
            <div class="gestao-icon usuarios">
                <i class="fas fa-users"></i>
            </div>
            <h2>Gestão de Utilizadores</h2>
            <p>Adiciona, edita ou remove utilizadores do sistema. Controla permissões.</p>
            <a href="admin_users.php" class="gestao-btn">
                <i class="fas fa-arrow-right"></i> Gerir
            </a>
        </div>
        
        <div class="gestao-card">
            <div class="gestao-icon editar">
                <i class="fas fa-edit"></i>
            </div>
            <h2>Gestão de Regas</h2>
            <p>Modifica ou cancela regas já agendadas.</p>
            <a href="editar_regas.php" class="gestao-btn">
                <i class="fas fa-arrow-right"></i> Editar
            </a>
        </div>
        <!-- CARD REGAS AGENDADAS -->
        <div class="gestao-card">
            <div class="gestao-icon regas">
                <i class="fas fa-droplet"></i>
            </div>
            <h2>Histórico de Regas</h2>
            <p>Visualiza o histórico de todas as regas executadas no sistema.</p>
            <a href="gerir_regas.php" class="gestao-btn">
                <i class="fas fa-arrow-right"></i> Gerir
            </a>
        </div>
    </div>
</div>

<footer class="footer-simples">
    <div class="footer-linha-verde"></div>
    <div class="footer-conteudo">
        <p><strong>SmartRega</strong> &copy; <?php echo date("Y"); ?> — Todos os direitos reservados</p>
        <p class="footer-autor">Desenvolvido por Afonso Inácio | PAP TGPSI</p>
    </div>
</footer>

</body>
</html>
