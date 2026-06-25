<?php
session_start();
include "config/db.php";

function parseScheduleDate($value) {
    $value = str_replace('T', ' ', $value);
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('Y-m-d H:i', $value);
    }
    // Aceitar também formatos com dia/mês/ano caso o utilizador cole a data
    // tal como é mostrada na UI (ex.: 03/06/2026 18:00:53).
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

function ensureProgramacoesIdUtilizador($conn) {
    if (!columnExists($conn, 'programacoes', 'id_utilizador')) {
        $conn->query("ALTER TABLE programacoes ADD COLUMN id_utilizador INT NULL AFTER duracao");
    }
}

function scheduleConflictExists($conn, $bomba_id, DateTime $start, DateTime $end, $excludeId = null) {
    $sql = "SELECT id, data_hora, duracao FROM programacoes WHERE bomba_id = ? AND executado IN (0,1)";
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

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

if (!in_array($_SESSION["tipo"], ["admin", "cliente_atual"])) {
    $errorMsg = urlencode("Sem permissão para agendar regas.");
    header("Location: dashboard.php?error_msg={$errorMsg}");
    exit;
}

ensureProgramacoesIdUtilizador($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bomba_id = intval($_POST['bomba_id']);
    $data_hora = $_POST['data_hora'];
    $duracao = intval($_POST['duracao']); // Recebe a duração em segundos

    $scheduleStart = parseScheduleDate($data_hora);
    if (!$scheduleStart) {
        $errorMsg = urlencode("Data e hora inválidas. Por favor, escolha um horário correto.");
        header("Location: dashboard.php?error_msg={$errorMsg}");
        exit;
    }

    // Se o `datetime-local` não incluir segundos (formato Y-m-dTH:i),
    // alguns navegadores irão enviar os segundos como :00, o que pode
    // fazer com que um horário selecionado "agora" seja interpretado
    // como passado se os segundos do servidor já avançaram.
    // Neste caso, elevamos para o minuto seguinte se já passou.
    $now = new DateTime();
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $data_hora)) {
        if ($scheduleStart < $now) {
            $scheduleStart->modify('+1 minute');
        }
    }

    $scheduleEnd = clone $scheduleStart;
    $scheduleEnd->modify('+' . max(1, $duracao) . ' seconds');

    if ($scheduleStart < $now) {
        $errorMsg = urlencode("Não é possível agendar uma rega no passado. Escolha um horário futuro.");
        header("Location: dashboard.php?error_msg={$errorMsg}");
        exit;
    }

    if (scheduleConflictExists($conn, $bomba_id, $scheduleStart, $scheduleEnd)) {
        $errorMsg = urlencode("Já existe um agendamento para esta bomba no mesmo período. Escolha outro horário ou outra bomba.");
        header("Location: dashboard.php?error_msg={$errorMsg}");
        exit;
    }

    $sql = "INSERT INTO programacoes (bomba_id, data_hora, duracao, id_utilizador) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $bomba_id, $data_hora, $duracao, $_SESSION['user_id']);
    
    if($stmt->execute()){
        header("Location: dashboard.php?agendado=1");
    } else {
        $errorMsg = urlencode("Erro ao agendar: " . $conn->error);
        header("Location: dashboard.php?error_msg={$errorMsg}");
    }
} else {
    header("Location: dashboard.php");
}
exit;