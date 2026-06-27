<?php
// Definir o fuso horário para Portugal, de modo a que as datas do formulário
// `datetime-local` sejam interpretadas corretamente como horário local.
date_default_timezone_set('Europe/Lisbon');

$host = "tapado_por_segurança";
$user = "tapado_por_segurança";
$pass = "tapado_por_segurança";
$dbname = "tapado_por_segurança";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}
?>
