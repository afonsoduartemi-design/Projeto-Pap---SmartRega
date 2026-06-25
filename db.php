<?php
// Definir o fuso horário para Portugal, de modo a que as datas do formulário
// `datetime-local` sejam interpretadas corretamente como horário local.
date_default_timezone_set('Europe/Lisbon');

$host = "sql112.infinityfree.com";
$user = "if0_42259892";
$pass = "Incorreta1212";
$dbname = "if0_42259892_smart_rega";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}
?>