<?php
session_start();
include "config/db.php";

// 1. Recebe o token enviado pelo Google via POST
if (!isset($_POST['credential'])) {
    header("Location: index.php");
    exit;
}

$id_token = $_POST['credential'];

// ==========================================
// CORREÇÃO: Verificar o token com a API do Google
// Em vez de apenas descodificar o JWT cegamente,
// pedimos ao Google para confirmar que o token é válido.
// ==========================================
$client_id = "tapado_por_segurança.apps.googleusercontent.com";

$verify_url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($id_token);
$response = file_get_contents($verify_url);

if ($response === false) {
    header("Location: index.php?erro=google");
    exit;
}

$payload = json_decode($response, true);

// Verificar se o token é válido E se foi emitido para a nossa aplicação
if (!$payload || !isset($payload['sub']) || $payload['aud'] !== $client_id) {
    header("Location: index.php?erro=google");
    exit;
}

// Verificar se o token não expirou
if (isset($payload['exp']) && $payload['exp'] < time()) {
    header("Location: index.php?erro=google_expired");
    exit;
}

// A partir daqui, o token é genuíno e pertence ao nosso app
$g_id  = $payload['sub'];
$email = $payload['email'];
$nome  = $payload['name'];

// 3. Verificar se o utilizador já existe por google_id OU por email
$sql = "SELECT * FROM users WHERE google_id = ? OR email_utilizador = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $g_id, $email);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Se o utilizador já existia por email mas não tinha o google_id guardado, atualizamos agora
    if (empty($user['google_id'])) {
        $update = "UPDATE users SET google_id = ? WHERE id_utilizador = ?";
        $up_stmt = $conn->prepare($update);
        $up_stmt->bind_param("si", $g_id, $user['id_utilizador']);
        $up_stmt->execute();
        $up_stmt->close();
        $user['google_id'] = $g_id;
    }
} else {
    // 4. Se não existe, cria um novo utilizador automaticamente
    $sql_ins = "INSERT INTO users (google_id, nome_utilizador, email_utilizador, tipo_utilizador) VALUES (?, ?, ?, 'cliente')";
    $stmt_ins = $conn->prepare($sql_ins);
    $stmt_ins->bind_param("sss", $g_id, $nome, $email);
    $stmt_ins->execute();

    $id_novo = $conn->insert_id;
    $stmt_ins->close();
    $user = [
        'id_utilizador'  => $id_novo,
        'nome_utilizador' => $nome,
        'tipo_utilizador' => 'cliente',
        'google_id'      => $g_id
    ];
}

// 5. Iniciar Sessão
session_regenerate_id(true);
$_SESSION["user_id"] = $user["id_utilizador"];
$_SESSION["nome"]    = $user["nome_utilizador"];
$_SESSION["tipo"]    = $user["tipo_utilizador"];

// O Google Identity Services está a fazer um POST direto (navegação completa
// da página) para este endpoint, em vez de chamar o callback JavaScript.
// Por isso, fazemos nós mesmos o redirect para a dashboard aqui no PHP,
// garantindo que o utilizador chega lá independentemente do modo usado pelo Google.
header("Location: dashboard.php");
exit;
?>
