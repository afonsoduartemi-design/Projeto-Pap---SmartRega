<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["tipo"] != "admin") {
    header("Location: index.php");
    exit;
}

// Processar eliminação de histórico
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM programacoes WHERE id = ? AND executado != 0");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: gerir_regas.php");
    exit;
}

$current_page = 'gestoes';
$res_regas = $conn->query("SELECT p.*, COALESCE(u.nome_utilizador, 'Sistema') AS agendado_por FROM programacoes p LEFT JOIN users u ON p.user_id = u.id_utilizador WHERE p.executado != 0 ORDER BY p.data_hora DESC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Regas — SmartRega</title>
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

<div class="admin-container">
    <div class="admin-header">
        <h2><i class="fas fa-history"></i> Histórico de Regas</h2>
        <p>Visualiza o histórico de todas as regas executadas no sistema.</p>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Bomba</th>
                    <th>Agendado por</th>
                    <th>Data e Hora</th>
                    <th>Duração</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_regas->num_rows > 0): ?>
                    <?php while ($row = $res_regas->fetch_assoc()): ?>
                    <tr>
                        <td><strong>Bomba <?php echo $row['bomba_id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($row['agendado_por'] ?: 'Sistema'); ?></td>
                        <td><?php echo date("d/m/Y — H:i:s", strtotime($row['data_hora'])); ?></td>
                        <td><?php echo $row['duracao']; ?> seg</td>
                        <td>
                            <?php if ($row['executado'] == 1): ?>
                                <span class="status-pill active"><i class="fas fa-droplet"></i> A regar</span>
                            <?php elseif ($row['executado'] == 2): ?>
                                <span class="status-pill completed"><i class="fas fa-check-circle"></i> Concluído</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="action-link delete-link delete-history-btn" 
                                    data-id="<?php echo $row['id']; ?>"
                                    data-bomba="<?php echo $row['bomba_id']; ?>"
                                    title="Eliminar do histórico">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; color:#bbb; padding:32px;">
                            Nenhuma rega registada.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

  
</div>

<!-- MODAL PARA CANCELAR REGA -->
<div id="cancelModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon warning"></div>
        <h3>Cancelar Rega?</h3>
        <p>Tens a certeza que queres cancelar esta rega agendada para a <strong>Bomba <span id="cancelBombaNum"></span></strong>? Esta ação não pode ser desfeita.</p>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-btn-cancel" id="cancelCancelBtn">Não, manter</button>
            <button type="button" class="modal-btn modal-btn-confirm" id="cancelConfirmBtn">Sim, cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL PARA ELIMINAR HISTÓRICO -->
<div id="deleteHistoryModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon danger">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3>Eliminar do Histórico?</h3>
        <p>Tens a certeza que queres eliminar permanentemente este registo da rega da <strong>Bomba <span id="deleteHistoryBombaNum"></span></strong> da base de dados?</p>
        <div class="modal-buttons">
            <button type="button" class="modal-btn modal-btn-cancel" id="deleteHistoryCancelBtn">Não, manter</button>
            <button type="button" class="modal-btn modal-btn-danger" id="deleteHistoryConfirmBtn">Sim, eliminar</button>
        </div>
    </div>
</div>


<script>
let currentCancelId = null;
let currentDeleteHistoryId = null;
const cancelModal = document.getElementById('cancelModal');
const deleteHistoryModal = document.getElementById('deleteHistoryModal');
const cancelBombaNum = document.getElementById('cancelBombaNum');
const deleteHistoryBombaNum = document.getElementById('deleteHistoryBombaNum');
const cancelCancelBtn = document.getElementById('cancelCancelBtn');
const cancelConfirmBtn = document.getElementById('cancelConfirmBtn');
const deleteHistoryCancelBtn = document.getElementById('deleteHistoryCancelBtn');
const deleteHistoryConfirmBtn = document.getElementById('deleteHistoryConfirmBtn');

// CANCELAR REGA AGENDADA
document.querySelectorAll('.cancel-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        currentCancelId = this.getAttribute('data-id');
        cancelBombaNum.innerText = this.getAttribute('data-bomba');
        cancelModal.style.display = 'flex';
    });
});

cancelCancelBtn.addEventListener('click', function() {
    cancelModal.style.display = 'none';
    currentCancelId = null;
});

cancelConfirmBtn.addEventListener('click', function() {
    if (currentCancelId) {
        window.location.href = 'cancelar_agendamento.php?id=' + currentCancelId;
    }
});

cancelModal.addEventListener('click', function(e) {
    if (e.target === cancelModal) {
        cancelModal.style.display = 'none';
        currentCancelId = null;
    }
});

// ELIMINAR HISTÓRICO DE REGA
document.querySelectorAll('.delete-history-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        currentDeleteHistoryId = this.getAttribute('data-id');
        deleteHistoryBombaNum.innerText = this.getAttribute('data-bomba');
        deleteHistoryModal.style.display = 'flex';
    });
});

deleteHistoryCancelBtn.addEventListener('click', function() {
    deleteHistoryModal.style.display = 'none';
    currentDeleteHistoryId = null;
});

deleteHistoryConfirmBtn.addEventListener('click', function() {
    if (currentDeleteHistoryId) {
        window.location.href = 'gerir_regas.php?delete_id=' + currentDeleteHistoryId;
    }
});

deleteHistoryModal.addEventListener('click', function(e) {
    if (e.target === deleteHistoryModal) {
        deleteHistoryModal.style.display = 'none';
        currentDeleteHistoryId = null;
    }
});
</script>

<footer class="footer-simples">
    <div class="footer-linha-verde"></div>
    <div class="footer-conteudo">
        <p><strong>SmartRega</strong> &copy; <?php echo date("Y"); ?> — Todos os direitos reservados</p>
        <p class="footer-autor">Desenvolvido por Afonso Inácio | PAP TGPSI</p>
    </div>
</footer>

</body>
</html>
