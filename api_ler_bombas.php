<?php
include "../config/db.php";

// ==========================================
// FUNÇÃO DE SEGURANÇA: Validar bomba_id
// ==========================================
function validar_bomba_id($id) {
    $id = (int)$id;
    // CORREÇÃO: Só aceita valores entre 1 e 4
    if ($id < 1 || $id > 4) return false;
    return $id;
}

// ==========================================
// 1. LIGAR AS BOMBAS (Chegou a hora)
// ==========================================
$sql_ligar = "SELECT * FROM programacoes WHERE executado = 0 AND data_hora <= NOW()";
$res_ligar = $conn->query($sql_ligar);

if ($res_ligar->num_rows > 0) {
    while ($row = $res_ligar->fetch_assoc()) {
        $id_agendamento = (int)$row['id'];
        
        // CORREÇÃO: Validar bomba_id antes de usar na query
        $bomba_id = validar_bomba_id($row['bomba_id']);
        if (!$bomba_id) continue; // Ignora se inválido

        // LIGA a bomba correspondente
        $stmt = $conn->prepare("UPDATE estados_bombas SET bomba_$bomba_id = 1 WHERE id = 1");
        $stmt->execute();
        $stmt->close();
        
        // Marca o agendamento como EM EXECUÇÃO (1)
        $stmt2 = $conn->prepare("UPDATE programacoes SET executado = 1 WHERE id = ?");
        $stmt2->bind_param("i", $id_agendamento);
        $stmt2->execute();
        $stmt2->close();
    }
}

// ==========================================
// 2. DESLIGAR AS BOMBAS (Passou o tempo em SEGUNDOS)
// ==========================================
$sql_desligar = "SELECT * FROM programacoes WHERE executado = 1 AND DATE_ADD(data_hora, INTERVAL duracao SECOND) <= NOW()";
$res_desligar = $conn->query($sql_desligar);

if ($res_desligar->num_rows > 0) {
    while ($row = $res_desligar->fetch_assoc()) {
        $id_agendamento = (int)$row['id'];

        // CORREÇÃO: Validar bomba_id antes de usar na query
        $bomba_id = validar_bomba_id($row['bomba_id']);
        if (!$bomba_id) continue;
        
        // DESLIGA a bomba correspondente
        $stmt = $conn->prepare("UPDATE estados_bombas SET bomba_$bomba_id = 0 WHERE id = 1");
        $stmt->execute();
        $stmt->close();
        
        // Marca o agendamento como TOTALMENTE CONCLUÍDO (2)
        $stmt2 = $conn->prepare("UPDATE programacoes SET executado = 2 WHERE id = ?");
        $stmt2->bind_param("i", $id_agendamento);
        $stmt2->execute();
        $stmt2->close();
    }
}

// ==========================================
// 3. ENVIAR O ESTADO PARA O ESP32
// ==========================================
$sql = "SELECT * FROM estados_bombas WHERE id = 1";
$res = $conn->query($sql);

if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo (int)$row['bomba_1'] . "," . (int)$row['bomba_2'] . "," . (int)$row['bomba_3'] . "," . (int)$row['bomba_4'];
} else {
    echo "0,0,0,0";
}
?>