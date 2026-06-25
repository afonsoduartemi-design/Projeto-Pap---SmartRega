<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

function parseScheduleDate($value) {
    $value = str_replace('T', ' ', $value);
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i', $value);
    }
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('d/m/Y H:i:s', $value);
        if (!$dateTime) {
            $dateTime = DateTime::createFromFormat('d/m/Y H:i', $value);
        }
    }
    return $dateTime ?: false;
}

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count_col FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($row['count_col']);
}

function scheduleConflictExists($conn, $bomba_id, DateTime $start, DateTime $end, $excludeId = null) {
    $sql = "SELECT id, data_hora, duracao FROM programacoes WHERE bomba_id = ? AND executado = 0";
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
    }

    $stmt = $conn->prepare($sql);
    if ($excludeId !== null) {
        $stmt->bind_param("ii", $bomba_id, $excludeId);
    } else {
        $stmt->bind_param("i", $bomba_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $existingStart = new DateTime($row['data_hora']);
        $existingEnd = clone $existingStart;
        $existingEnd->modify('+' . max(0, (int)$row['duracao']) . ' seconds');

        if ($existingStart < $end && $existingEnd > $start) {
            return true;
        }
    }

    return false;
}

$has_user_id = columnExists($conn, 'programacoes', 'id_utilizador');

$rega_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensagem = '';
$rega = null;

// Carregar lista de regas do utilizador
if ($has_user_id) {
    $res_regas = $conn->query("SELECT * FROM programacoes WHERE executado = 0 AND id_utilizador = " . intval($_SESSION['user_id']) . " ORDER BY data_hora ASC");
} else {
    $res_regas = $conn->query("SELECT * FROM programacoes WHERE executado = 0 ORDER BY data_hora ASC");
}

if ($rega_id) {
    if ($has_user_id) {
        $stmt = $conn->prepare("SELECT * FROM programacoes WHERE id = ? AND executado = 0 AND id_utilizador = ?");
        $stmt->bind_param("ii", $rega_id, $_SESSION['user_id']);
    } else {
        $stmt = $conn->prepare("SELECT * FROM programacoes WHERE id = ? AND executado = 0");
        $stmt->bind_param("i", $rega_id);
    }
    $stmt->execute();
    $rega = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $rega_id) {
    $bomba_id = (int)$_POST["bomba_id"];
    $data_hora = $_POST["data_hora"];
    $duracao = (int)$_POST["duracao"];

    $scheduleStart = parseScheduleDate($data_hora);
    if (!$scheduleStart) {
        $mensagem = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Data e hora inválidas. Por favor, escolha um horário correto.</div>';
    } else {
        $now = new DateTime();
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $data_hora)) {
            if ($scheduleStart < $now) {
                $scheduleStart->modify('+1 minute');
            }
        }

        $scheduleEnd = clone $scheduleStart;
        $scheduleEnd->modify('+' . max(1, $duracao) . ' seconds');

        if ($scheduleStart < $now) {
            $mensagem = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Não é possível agendar uma rega no passado. Escolha um horário futuro.</div>';
        } elseif (scheduleConflictExists($conn, $bomba_id, $scheduleStart, $scheduleEnd, $rega_id)) {
            $mensagem = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Já existe um agendamento para esta bomba no mesmo período. Escolha outro horário ou outra bomba.</div>';
        } else {
            if ($has_user_id) {
                $stmt = $conn->prepare("UPDATE programacoes SET bomba_id = ?, data_hora = ?, duracao = ? WHERE id = ? AND executado = 0 AND id_utilizador = ?");
                $stmt->bind_param("isiii", $bomba_id, $data_hora, $duracao, $rega_id, $_SESSION['user_id']);
            } else {
                $stmt = $conn->prepare("UPDATE programacoes SET bomba_id = ?, data_hora = ?, duracao = ? WHERE id = ? AND executado = 0");
                $stmt->bind_param("isii", $bomba_id, $data_hora, $duracao, $rega_id);
            }

            if ($stmt->execute()) {
                $mensagem = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Rega atualizada com sucesso!</div>';
                $rega['bomba_id'] = $bomba_id;
                $rega['data_hora'] = $data_hora;
                $rega['duracao'] = $duracao;
            } else {
                $mensagem = '<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Erro ao atualizar: ' . $conn->error . '</div>';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Minha Rega — SmartRega</title>
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
                <a href="minha_conta.php"><i class="fas fa-calendar-check"></i> Minhas Regas</a>
                <hr style="margin: 8px 0; border: none; border-top: 1px solid rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </span>
    </nav>
</header>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-edit"></i> Editar Rega</h1>
        <p>Atualize o horário e a duração da sua rega agendada.</p>
    </div>

    <?php echo $mensagem; ?>

    <?php if ($rega): ?>
        <!-- FORMULÁRIO DE EDIÇÃO -->
        <div class="account-card">
            <form method="POST" class="form-account">
                <div class="form-group">
                    <label>Bomba / Zona</label>
                    <select name="bomba_id" required>
                        <option value="1" <?php echo $rega['bomba_id'] == 1 ? 'selected' : ''; ?>>Bomba 1 — Zona 1</option>
                        <option value="2" <?php echo $rega['bomba_id'] == 2 ? 'selected' : ''; ?>>Bomba 2 — Zona 2</option>
                        <option value="3" <?php echo $rega['bomba_id'] == 3 ? 'selected' : ''; ?>>Bomba 3 — Zona 3</option>
                        <option value="4" <?php echo $rega['bomba_id'] == 4 ? 'selected' : ''; ?>>Bomba 4 — Zona 4</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Data e Hora</label>
                    <input type="datetime-local" name="data_hora" value="<?php echo date('Y-m-d\TH:i:s', strtotime($rega['data_hora'])); ?>" required>
                </div>

                <div class="form-group">
                    <label>Duração</label>
                    <select name="duracao" required>
                        <option value="5" <?php echo $rega['duracao'] == 5 ? 'selected' : ''; ?>>5 segundos</option>
                        <option value="10" <?php echo $rega['duracao'] == 10 ? 'selected' : ''; ?>>10 segundos</option>
                        <option value="15" <?php echo $rega['duracao'] == 15 ? 'selected' : ''; ?>>15 segundos</option>
                        <option value="30" <?php echo $rega['duracao'] == 30 ? 'selected' : ''; ?>>30 segundos</option>
                        <option value="45" <?php echo $rega['duracao'] == 45 ? 'selected' : ''; ?>>45 segundos</option>
                        <option value="60" <?php echo $rega['duracao'] == 60 ? 'selected' : ''; ?>>1 minuto</option>
                    </select>
                </div>

                <button type="submit" class="btn-schedule"><i class="fas fa-save"></i> Guardar Alterações</button>
                <a href="minha_conta.php" class="btn-cancel-link"><i class="fas fa-arrow-left"></i> Voltar à Lista</a>
            </form>
        </div>
    <?php else: ?>
        <!-- LISTA DE REGAS PENDENTES DO UTILIZADOR -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Bomba</th>
                        <th>Data e Hora</th>
                        <th>Duração</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res_regas && $res_regas->num_rows > 0): ?>
                        <?php while ($row = $res_regas->fetch_assoc()): ?>
                        <tr>
                            <td><strong>Bomba <?php echo $row['bomba_id']; ?></strong></td>
                            <td><?php echo date("d/m/Y — H:i:s", strtotime($row['data_hora'])); ?></td>
                            <td><?php echo $row['duracao']; ?> seg</td>
                            <td>
                                <div class="action-buttons-cell">
                                    <a href="editar_minha_rega.php?id=<?php echo $row['id']; ?>" class="action-link edit-link">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <button type="button" class="btn-cancel btn-cancel-trigger" data-id="<?php echo $row['id']; ?>" data-bomba="<?php echo $row['bomba_id']; ?>">
                                        <i class="fas fa-trash-alt"></i> Cancelar Rega
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#bbb; padding:32px;">
                                Nenhuma rega agendada pendente.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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
