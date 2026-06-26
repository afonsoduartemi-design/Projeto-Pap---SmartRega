<?php
// Definir o fuso horário para Portugal, de modo a que as datas do formulário
// `datetime-local` sejam interpretadas corretamente como horário local.
date_default_timezone_set('Europe/Lisbon');

$host = "oculto por razões de segurança";
$user = "oculto por razões de segurança";
$pass = "oculto por razões de segurança";
$dbname = "oculto por razões de segurança";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}
?>
