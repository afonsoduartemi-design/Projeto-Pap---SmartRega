<?php
// Incluir a ligação à base de dados
include "config/db.php";

// CORREÇÃO: Usar POST em vez de GET para receber dados do ESP32
// No ESP32, muda o método para HTTP POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo "Erro: Método não permitido.";
    exit;
}

if (isset($_POST['s1']) && isset($_POST['s2']) && isset($_POST['s3']) && isset($_POST['s4'])) {
    
    // Limpar e validar os dados (0-100 para percentagem de humidade)
    $s1 = max(0, min(100, (int)$_POST['s1']));
    $s2 = max(0, min(100, (int)$_POST['s2']));
    $s3 = max(0, min(100, (int)$_POST['s3']));
    $s4 = max(0, min(100, (int)$_POST['s4']));

    // CORREÇÃO: Usar prepared statement em vez de interpolação direta
    $sql = "INSERT INTO leituras (sensor_1, sensor_2, sensor_3, sensor_4) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $s1, $s2, $s3, $s4);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo "OK";
    } else {
        http_response_code(500);
        echo "Erro ao guardar: " . $conn->error;
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo "Erro: Faltam parâmetros dos sensores.";
}
?>