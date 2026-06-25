<?php
session_start();
include "config/db.php";

// 1. Verificar se o utilizador está logado
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

// 1.1 Verificar permissões de controlo das bombas
if (!in_array($_SESSION["tipo"], ["admin", "cliente_atual"])) {
    header("Location: dashboard.php?error_msg=" . urlencode("Sem permissão para controlar bombas."));
    exit;
}

// 2. Verificar se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recebe qual a bomba (1, 2, 3 ou 4) e o estado atual (0 ou 1)
    $bomba_id = intval($_POST["bomba_id"]);
    $estado_atual = intval($_POST["estado_atual"]);

    // Inverte o estado: se era 0 (desligado) vira 1 (ligado) e vice-versa
    $novo_estado = ($estado_atual == 1) ? 0 : 1;

    // Define qual coluna vai ser atualizada (ex: bomba_1, bomba_2...)
    $coluna = "bomba_" . $bomba_id;

    // 3. Atualiza a base de dados
    // IMPORTANTE: Assumimos que a linha de controlo é a ID 1
    $sql = "UPDATE estados_bombas SET $coluna = ? WHERE id = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $novo_estado);

    if ($stmt->execute()) {
        // Redireciona de volta para a dashboard para ver a mudança
        header("Location: dashboard.php?sucesso=1");
    } else {
        echo "Erro ao atualizar a bomba: " . $conn->error;
    }
} else {
    // Se tentarem aceder ao ficheiro diretamente sem clicar no botão
    header("Location: dashboard.php");
}
exit;