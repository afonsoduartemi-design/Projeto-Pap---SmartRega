<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$user = null;
$stmt = $conn->prepare("SELECT id_utilizador, nome_utilizador FROM users WHERE id_utilizador = ?");
$stmt->bind_param("i", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: logout.php");
    exit;
}

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count_col FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($row['count_col']);
}

$has_user_id = columnExists($conn, 'programacoes', 'id_utilizador');
if (!$has_user_id) {
    $conn->query("ALTER TABLE programacoes ADD COLUMN id_utilizador INT NULL AFTER duracao");
    $has_user_id = columnExists($conn, 'programacoes', 'id_utilizador');
}

if ($has_user_id) {
    $stmt = $conn->prepare("SELECT * FROM programacoes WHERE id_utilizador = ? AND executado IN (0,1) ORDER BY data_hora ASC");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result_schedules = $stmt->get_result();
    $stmt->close();
} else {
    $result_schedules = $conn->query("SELECT * FROM programacoes WHERE executado IN (0,1) ORDER BY data_hora ASC");
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Regas — SmartRega</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_pap.css">
</head>
<body>

<header>
    <a href="dashboard.php" class="logo"><img src="img/logo.png" alt="SmartRega"></a>
    <nav>
        <a href="dashboard.php">Painel</a>
        <a href="projeto.php">O Projeto</a>
        <?php if ($_SESSION["tipo"] == "admin"): ?>
            <a href="gestoes.php"><i class="fas fa-sliders-h"></i> Gestões</a>
        <?php endif; ?>
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
                <a href="minha_conta.php" class="active"><i class="fas fa-calendar-check"></i> Minhas Regas</a>
                <hr style="margin: 8px 0; border: none; border-top: 1px solid rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </span>
    </nav>
</header>

<div class="admin-container">
    <div class="admin-header">
        <h2><i class="fas fa-calendar-check"></i> As Minhas Regas</h2>
        <p>As suas regas agendadas estão listadas aqui. Pode editar ou cancelar apenas as regas pendentes.</p>
    </div>

    <?php if ($result_schedules && $result_schedules->num_rows > 0): ?>
        <div class="table-wrapper schedule-table-wrapper">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Bomba</th>
                        <th>Horário</th>
                        <th>Duração</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result_schedules->fetch_assoc()): ?>
                    <tr>
                        <td><strong>Bomba <?php echo $row['bomba_id']; ?></strong></td>
                        <td><?php echo date("d/m/Y — H:i:s", strtotime($row['data_hora'])); ?></td>
                        <td><?php echo $row['duracao']; ?> seg</td>
                        <td>
                            <?php if ($row['executado'] == 1): ?>
                                <span class="status status-running"><i class="fas fa-play"></i> Em curso</span>
                            <?php else: ?>
                                <span class="status status-scheduled"><i class="fas fa-hourglass"></i> Agendado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons-cell">
                                <a href="editar_minha_rega.php?id=<?php echo $row['id']; ?>" class="action-link edit-link">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <?php if ($row['executado'] == 0): ?>
                                <button type="button" class="btn-cancel btn-cancel-trigger" data-id="<?php echo $row['id']; ?>" data-bomba="<?php echo $row['bomba_id']; ?>">
                                    <i class="fas fa-trash-alt"></i> Cancelar Rega
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-error" style="margin:24px 0;">
            <i class="fas fa-exclamation-circle"></i> Não tens nenhuma rega agendada.
        </div>
    <?php endif; ?>
</div>

<footer class="footer-simples">
    <div class="footer-linha-verde"></div>
    <div class="footer-conteudo">
        <p><strong>SmartRega</strong> &copy; <?php echo date("Y"); ?> — Todos os direitos reservados</p>
        <p class="footer-autor">Desenvolvido por Afonso Inácio | PAP TGPSI</p>
    </div>
</footer>

<div id="cancelModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon warning">
            <i class="fas fa-question-circle"></i>
        </div>
        <h3>Cancelar Rega?</h3>
        <p>Tens a certeza que queres cancelar esta rega agendada para a <strong>Bomba <span id="cancelBombaNum"></span></strong>? Esta ação não pode ser desfeita.</p>
        <div class="modal-buttons">
            <button type="button" class="modal-btn modal-btn-cancel" id="cancelCancelBtn">Manter Agendado</button>
            <button type="button" class="modal-btn modal-btn-confirm" id="confirmCancelBtn">Sim, Cancelar</button>
        </div>
    </div>
</div>

<script src="js/java-pap.js"></script>
</body>
</html>
