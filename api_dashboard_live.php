<?php
// api_dashboard_live.php
// Endpoint chamado pelo JavaScript da dashboard a cada 5 segundos
// Devolve os dados mais recentes em formato JSON

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

// Segurança: só utilizadores com sessão ativa podem aceder
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["erro" => "Não autorizado"]);
    exit;
}

$has_user_id = columnExists($conn, 'programacoes', 'id_utilizador');

header('Content-Type: application/json');

// Última leitura dos sensores
$sql_sensores = "SELECT sensor_1, sensor_2, sensor_3, sensor_4 FROM leituras ORDER BY data_leitura DESC LIMIT 1";
$res_sensores = $conn->query($sql_sensores);
$sensores = $res_sensores->fetch_assoc();
if (!$sensores) {
    $sensores = ["sensor_1" => 0, "sensor_2" => 0, "sensor_3" => 0, "sensor_4" => 0];
}

// Estado atual das bombas
// ==========================================
// Processar programações pendentes (liga/desliga) — garantir que
// um pedido à dashboard também atualiza o estado das bombas
// ==========================================
function validar_bomba_id_local($id) {
    $id = (int)$id;
    if ($id < 1 || $id > 4) return false;
    return $id;
}

// Ligar bombas cuja hora chegou
$sql_ligar = "SELECT * FROM programacoes WHERE executado = 0 AND data_hora <= NOW()";
$res_ligar = $conn->query($sql_ligar);
if ($res_ligar && $res_ligar->num_rows > 0) {
    while ($row = $res_ligar->fetch_assoc()) {
        $id_agendamento = (int)$row['id'];
        $bomba_id = validar_bomba_id_local($row['bomba_id']);
        if (!$bomba_id) continue;

        $stmt = $conn->prepare("UPDATE estados_bombas SET bomba_$bomba_id = 1 WHERE id = 1");
        if ($stmt) { $stmt->execute(); $stmt->close(); }

        $stmt2 = $conn->prepare("UPDATE programacoes SET executado = 1 WHERE id = ?");
        if ($stmt2) { $stmt2->bind_param("i", $id_agendamento); $stmt2->execute(); $stmt2->close(); }
    }
}

// Desligar bombas cujo tempo já passou
$sql_desligar = "SELECT * FROM programacoes WHERE executado = 1 AND DATE_ADD(data_hora, INTERVAL duracao SECOND) <= NOW()";
$res_desligar = $conn->query($sql_desligar);
if ($res_desligar && $res_desligar->num_rows > 0) {
    while ($row = $res_desligar->fetch_assoc()) {
        $id_agendamento = (int)$row['id'];
        $bomba_id = validar_bomba_id_local($row['bomba_id']);
        if (!$bomba_id) continue;

        $stmt = $conn->prepare("UPDATE estados_bombas SET bomba_$bomba_id = 0 WHERE id = 1");
        if ($stmt) { $stmt->execute(); $stmt->close(); }

        $stmt2 = $conn->prepare("UPDATE programacoes SET executado = 2 WHERE id = ?");
        if ($stmt2) { $stmt2->bind_param("i", $id_agendamento); $stmt2->execute(); $stmt2->close(); }
    }
}

// Agora ler o estado atual das bombas
 $sql_bombas = "SELECT bomba_1, bomba_2, bomba_3, bomba_4 FROM estados_bombas WHERE id = 1";
 $res_bombas = $conn->query($sql_bombas);
 $bombas = $res_bombas->fetch_assoc();
 if (!$bombas) {
     $bombas = ["bomba_1" => 0, "bomba_2" => 0, "bomba_3" => 0, "bomba_4" => 0];
 }

// Programações (agendamentos) — devolve próximas programações e estado
$sql_prog = "SELECT p.id, p.bomba_id, p.data_hora, p.duracao, p.executado" . ($has_user_id ? ", p.id_utilizador, COALESCE(u.nome_utilizador, 'Sistema') AS agendado_por" : "") . " FROM programacoes p" . ($has_user_id ? " LEFT JOIN users u ON p.id_utilizador = u.id_utilizador" : "") . " WHERE p.executado IN (0,1) ORDER BY p.data_hora ASC";
$res_prog = $conn->query($sql_prog);
$programacoes = [];
if ($res_prog) {
    while ($row = $res_prog->fetch_assoc()) {
        $isOwner = false;
        $canCancel = isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'admin';

        if ($has_user_id) {
            $isOwner = isset($row['id_utilizador']) && intval($row['id_utilizador']) === intval($_SESSION['user_id']);
            $canCancel = $canCancel || $isOwner;
            $row['agendado_por'] = $row['agendado_por'] ?: 'Sistema';
        } else {
            $row['agendado_por'] = 'Sistema';
        }

        $row['is_owner'] = $isOwner;
        $row['can_cancel'] = $canCancel;
        $programacoes[] = $row;
    }
}

echo json_encode([
    "sensores"     => $sensores,
    "bombas"       => $bombas,
    "programacoes" => $programacoes,
    "hora"         => date("H:i:s")
]);
?>