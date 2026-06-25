<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["tipo"] != "admin") {
    header("Location: index.php");
    exit;
}

$mensagem = "";

if (isset($_GET['id'])) {
    $id   = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE id_utilizador = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) die("Utilizador não encontrado.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_edit    = intval($_POST["id_utilizador"]);
    $novo_nome  = isset($_POST["nome"]) ? trim($_POST["nome"]) : '';
    $novo_email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
    $novo_tipo  = isset($_POST["tipo"]) ? $_POST["tipo"] : 'cliente';

    // Validação básica
    if (strlen($novo_nome) < 3 || strlen($novo_nome) > 30 || !preg_match('/^[\p{L}0-9 \-\.\']+$/u', $novo_nome)) {
        $mensagem = "Nome inválido. Use 3 a 30 caracteres, apenas letras, números e espaços.";
    } elseif (!filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "Email inválido.";
    } elseif (!in_array($novo_tipo, ['cliente','cliente_atual','admin'])) {
        $mensagem = "Tipo de conta inválido.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET nome_utilizador=?, email_utilizador=?, tipo_utilizador=? WHERE id_utilizador=?");
        $stmt->bind_param("sssi", $novo_nome, $novo_email, $novo_tipo, $id_edit);

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: admin_users.php?edit_success=1");
            exit;
        } else {
            $mensagem = "Erro ao atualizar: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Utilizador — SmartRega</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_pap.css">
</head>
<body style="background-image: none;">

<header>
    <a href="dashboard.php" class="logo"><img src="img/logo.png" alt="SmartRega"></a>
    <nav>
        <a href="dashboard.php">Painel</a>
        <a href="projeto.php">O Projeto</a>
        <a href="gestoes.php" class="nav-active"><i class="fas fa-sliders-h"></i> Gestões</a>
        <span class="user-menu">
            <button type="button" class="user-menu-trigger">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION["nome"]);
                    if ($_SESSION["tipo"] == "admin"): ?>
                        <span class="badge-admin">ADMIN</span>
                    <?php elseif ($_SESSION["tipo"] == "cliente_atual"): ?>
                        <span class="badge-cliente-atual">CLIENTE ATUAL</span>
                    <?php elseif ($_SESSION["tipo"] == "cliente"): ?>
                        <span class="badge-cliente">CLIENTE</span>
                    <?php endif; ?>
            </button>
            <div class="user-menu-dropdown">
                <a href="minha_conta.php"><i class="fas fa-calendar-check"></i> Minhas Regas</a>
                <hr style="margin: 8px 0; border: none; border-top: 1px solid rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </span>
    </nav>
</header>

<div class="edit-container">
    <div class="edit-card">
        <h2><i class="fas fa-edit"></i> Editar Utilizador <span style="color:#bbb; font-weight:400; font-size:1rem;">#<?php echo $user['id_utilizador']; ?></span></h2>

        <?php if ($mensagem): ?>
            <p class="erro"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($mensagem); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id_utilizador" value="<?php echo $user['id_utilizador']; ?>">

            <label>Nome</label>
            <input type="text"  name="nome" maxlength="30" value="<?php echo htmlspecialchars($user['nome_utilizador']); ?>"  required>

            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email_utilizador']); ?>" required>

            <div class="form-group">
                <label>Tipo de Conta</label>
                <select name="tipo">
                    <option value="cliente" <?php if ($user['tipo_utilizador']=='cliente') echo 'selected'; ?>>Cliente</option>
                    <option value="cliente_atual" <?php if ($user['tipo_utilizador']=='cliente_atual') echo 'selected'; ?>>Cliente Atual</option>
                    <option value="admin"   <?php if ($user['tipo_utilizador']=='admin')   echo 'selected'; ?>>Administrador</option>
                </select>
            </div>

            <button type="submit" class="btn-save"><i class="fas fa-save"></i>  Guardar Alterações</button>
        </form>
        <a href="admin_users.php" class="btn-cancel-link">Cancelar</a>
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