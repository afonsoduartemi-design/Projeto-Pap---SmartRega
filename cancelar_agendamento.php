<?php
session_start();
include "config/db.php";

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count_col FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($row['count_col']);
}

$has_user_id = columnExists($conn, 'programacoes', 'id_utilizador');

// 1. Verificar login
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

// 2. Verificar se o ID foi enviado
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $isAdmin = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';

    if ($has_user_id) {
        if ($isAdmin) {
            $sql = "DELETE FROM programacoes WHERE id = ? AND executado = 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
        } else {
            $sql = "DELETE FROM programacoes WHERE id = ? AND executado = 0 AND id_utilizador = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $id, $_SESSION["user_id"]);
        }
    } else {
        if ($isAdmin) {
            $sql = "DELETE FROM programacoes WHERE id = ? AND executado = 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
        } else {
            header("Location: dashboard.php?error_msg=" . urlencode("Apenas o criador ou administrador pode cancelar esta rega."));
            exit;
        }
    }

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            header("Location: dashboard.php?cancelado=1");
        } else {
            header("Location: dashboard.php?error_msg=" . urlencode("Não foi possível cancelar esta rega. Pode não ser sua ou já ter sido executada."));
        }
    } else {
        header("Location: dashboard.php?error_msg=" . urlencode("Erro ao cancelar: " . $conn->error));
    }
} else {
    header("Location: dashboard.php");
}
exit;