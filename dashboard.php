<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$sql   = "SELECT * FROM leituras ORDER BY data_leitura DESC LIMIT 1";
$res   = $conn->query($sql);
$dados = $res->fetch_assoc() ?: ["sensor_1"=>0,"sensor_2"=>0,"sensor_3"=>0,"sensor_4"=>0];

$res_bombas = $conn->query("SELECT * FROM estados_bombas WHERE id = 1");
$bombas     = $res_bombas->fetch_assoc() ?: ["bomba_1"=>0,"bomba_2"=>0,"bomba_3"=>0,"bomba_4"=>0];

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count_col FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($row['count_col']);
}

$has_user_id = columnExists($conn, 'programacoes', 'id_utilizador');
if (!$has_user_id) {
    $conn->query("ALTER TABLE programacoes ADD COLUMN id_utilizador INT NULL AFTER duracao");
    $has_user_id = columnExists($conn, 'programacoes', 'id_utilizador');
}

$selectFields = "p.id, p.bomba_id, p.data_hora, p.duracao, p.executado";
$canControl = ($_SESSION["tipo"] === "admin" || $_SESSION["tipo"] === "cliente_atual");
if ($has_user_id) {
    $selectFields .= ", p.id_utilizador, COALESCE(u.nome_utilizador, 'Sistema') AS agendado_por";
    $res_agendados = $conn->query("SELECT $selectFields FROM programacoes p LEFT JOIN users u ON p.id_utilizador = u.id_utilizador WHERE p.executado IN (0,1) ORDER BY p.data_hora ASC");
} else {
    $selectFields .= ", 'Sistema' AS agendado_por";
    $res_agendados = $conn->query("SELECT $selectFields FROM programacoes p WHERE p.executado IN (0,1) ORDER BY p.data_hora ASC");
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel — SmartRega</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_pap.css">
    <style>
        .btn-disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body class="dashboard">

<header>
    <a href="dashboard.php" class="logo"><img src="img/logo.png" alt="SmartRega"></a>
    <nav>
        <a href="dashboard.php">Painel</a>
        <a href="projeto.php">O Projeto</a>
        <?php if ($_SESSION["tipo"] == "admin"): ?>
            <a href="gestoes.php"><i class="fas fa-sliders-h"></i> Gestões</a>
        <?php endif; ?>
        <span class="user-menu">
            <button type="button" class="user-menu-trigger">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION["nome"]);
                    if ($_SESSION["tipo"] == "admin"): ?>
                        <span class="badge-admin">ADMIN</span>
                    <?php elseif ($_SESSION["tipo"] == "cliente_atual"): ?>
                        <span class="badge-cliente-atual">CLIENTE ATUAL</span>
                    <?php elseif ($_SESSION["tipo"] == "cliente"): ?>
                        <span class="badge-cliente">CLIENTE</span>
                    <?php endif; ?>
            </button>
            <div class="user-menu-dropdown">
                <a href="minha_conta.php"><i class="fas fa-calendar-check"></i> Minhas Regas</a>
                <hr style="margin: 8px 0; border: none; border-top: 1px solid rgba(0,0,0,0.1);">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </span>
    </nav>
</header>

<div class="container">
    <div class="page-header">
        <h1>Controlo das Zonas</h1>
        <p>
            Monitorize os sensores e controle as bombas em tempo real. &nbsp;
            <span style="font-size:0.8rem; color:#aaa;">
                Última atualização: <span id="ultima-atualizacao">agora</span>
            </span>
        </p>
    </div>

    <?php if (!$canControl): ?>
        <div class="alert alert-error" style="margin-bottom:24px;">
            <strong>Sem permissão de controlo em tempo real:</strong> apenas administradores ou o cliente atual podem ligar/desligar as bombas.
        </div>
    <?php endif; ?>

    <div class="grid-dashboard">
        <?php for ($i = 1; $i <= 4; $i++):
            $hum     = (int)$dados["sensor_$i"];
            $bombaOn = $bombas["bomba_$i"] == 1;
            $seco    = $hum < 30;

            // Cor da barra de humidade
            if ($hum >= 60)      $barColor = "#0288d1";
            elseif ($hum >= 30)  $barColor = "#4caf50";
            else                 $barColor = "#ef6c00";
        ?>
        <div class="card <?php echo $seco ? 'alerta-seco' : ''; ?>" id="card-zona-<?php echo $i; ?>">
            <h3>Zona <?php echo $i; ?></h3>
            <p style="color:#aaa; font-size:0.75rem; margin:0 0 4px; text-transform:uppercase; letter-spacing:0.4px;">Humidade do Solo</p>

            <div class="humidade-valor" id="humidade-<?php echo $i; ?>">
                <?php echo $hum; ?><span class="unit">%</span>
            </div>

            <!-- Barra de progresso de humidade -->
            <div class="humidity-bar">
                <div class="humidity-bar-fill" id="barra-<?php echo $i; ?>"
                     style="width:<?php echo $hum; ?>%; background:<?php echo $barColor; ?>;"></div>
            </div>

            <?php if ($seco): ?>
                <p style="font-size:0.75rem; color:#ef6c00; margin:0 0 10px; font-weight:600;"><i class="fas fa-exclamation-triangle"></i> Solo seco — rega recomendada</p>
            <?php endif; ?>

            <div class="bomba-estado <?php echo $bombaOn ? 'bomba-on' : 'bomba-off'; ?>" id="estado-bomba-<?php echo $i; ?>">
                <span class="dot"></span>
                <?php echo $bombaOn ? 'Bomba Ligada' : 'Bomba Desligada'; ?>
            </div>

            <?php if ($canControl): ?>
            <form action="controlar_bomba.php" method="POST">
                <input type="hidden" name="bomba_id"    value="<?php echo $i; ?>">
                <input type="hidden" name="estado_atual" value="<?php echo $bombas["bomba_$i"]; ?>" id="estado-input-<?php echo $i; ?>">
                <button type="submit" class="btn-pump <?php echo $bombaOn ? 'btn-off' : 'btn-on'; ?>" id="btn-bomba-<?php echo $i; ?>">
                    <?php echo $bombaOn ? '<i class="fas fa-stop"></i> Desligar Água' : '<i class="fas fa-play"></i> Ligar Água'; ?>
                </button>
            </form>
            <?php else: ?>
            <button type="button" class="btn-pump btn-disabled" disabled>
                <i class="fas fa-ban"></i> Sem permissão
            </button>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>

    <!-- AGENDAMENTOS -->
    <div class="scheduling-section">
        <h2><i class="fas fa-calendar-alt"></i> Programar Rega <span id="hora-portugal" style="font-size:0.9rem; color:#666; margin-left:12px;"></span></h2>

        <?php if (!empty($_GET['agendado'])): ?>
            <div class="alert alert-success">Rega agendada com sucesso!</div>
        <?php endif; ?>
        <?php if (!empty($_GET['error_msg'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error_msg']); ?></div>
        <?php endif; ?>

        <?php if ($canControl): ?>
        <form action="agendar_rega.php" method="POST" class="form-inline">
            <div class="form-group">
                <label>Zona / Bomba</label>
                <select name="bomba_id" required>
                    <option value="1">Bomba 1 — Zona 1</option>
                    <option value="2">Bomba 2 — Zona 2</option>
                    <option value="3">Bomba 3 — Zona 3</option>
                    <option value="4">Bomba 4 — Zona 4</option>
                </select>
            </div>
            <div class="form-group">
                <label>Data e Hora</label>
                <input type="datetime-local" name="data_hora" step="1" required>
            </div>
            <div class="form-group">
                <label>Duração</label>
                <select name="duracao" required>
                    <option value="5">5 segundos</option>
                    <option value="10">10 segundos</option>
                    <option value="15">15 segundos</option>
                    <option value="30">30 segundos</option>
                    <option value="45">45 segundos</option>
                    <option value="60">1 minuto</option>
                </select>
            </div>
            <button type="submit" class="btn-schedule"><i class="fas fa-plus"></i> Agendar Rega</button>
        </form>
        <?php else: ?>
            <div class="alert alert-error" style="margin-bottom:24px;">
                <strong>Sem permissão:</strong> apenas administradores ou o cliente atual podem agendar regas.
            </div>
        <?php endif; ?>

        <h3 style="font-size:1rem; color:#555; margin:0 0 12px; font-weight:600;">Próximas Regas Agendadas</h3>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Bomba</th>
                        <th>Agendado por</th>
                        <th>Horário</th>
                        <th>Duração</th>
                        <th>Estado</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody id="agendamentos-tbody">
                    <?php if ($res_agendados->num_rows > 0): ?>
                        <?php while ($row = $res_agendados->fetch_assoc()): ?>
                        <?php $isOwner = isset($row['id_utilizador']) && $row['id_utilizador'] == $_SESSION['user_id']; ?>
                        <?php $canCancel = $row['executado'] == 0 && ($_SESSION['tipo'] === 'admin' || $isOwner); ?>
                        <tr>
                            <td><strong>Bomba <?php echo $row['bomba_id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($row['agendado_por'] ?: 'Sistema'); ?></td>
                            <td><?php echo date("d/m/Y — H:i:s", strtotime($row['data_hora'])); ?></td>
                            <td><?php echo $row['duracao']; ?> seg</td>
                            <td>
                                <?php if ($row['executado'] == 1): ?>
                                    <span style="color:#2e7d32; font-weight:600;"><i class="fas fa-droplet"></i> A regar...</span>
                                <?php else: ?>
                                    <span style="color:#f39c12;"><i class="fas fa-hourglass"></i> Agendado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['executado'] == 0): ?>
                                    <?php if ($canCancel): ?>
                                        <button class="btn-cancel btn-cancel-trigger" 
                                                data-id="<?php echo $row['id']; ?>"
                                                data-bomba="<?php echo $row['bomba_id']; ?>">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    <?php else: ?>
                                        <span style="color:#bbb; font-size:0.8rem;">Sem permissão</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color:#bbb; font-size:0.8rem;">Em curso</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:#bbb; padding:32px;">
                                Nenhuma rega programada.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL CUSTOMIZADO PARA CANCELAR REGA -->
<div id="cancelModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon warning">
            <i class="fas fa-question-circle"></i>
        </div>
        <h3>Cancelar Rega?</h3>
        <p>Tens a certeza que queres cancelar esta rega agendada para a <strong>Bomba <span id="cancelBombaNum"></span></strong>? Esta ação não pode ser desfeita.</p>
        <div class="modal-buttons">
            <button type="button" class="modal-btn modal-btn-cancel" id="cancelCancelBtn">Manter Agendado</button>
            <button type="button" class="modal-btn modal-btn-confirm" id="confirmCancelBtn">Sim, Cancelar</button>
        </div>
    </div>
</div>

<footer class="footer-simples">
    <div class="footer-linha-verde"></div>
    <div class="footer-conteudo">
        <p><strong>SmartRega</strong> &copy; <?php echo date("Y"); ?> — Todos os direitos reservados</p>
        <p class="footer-autor">Desenvolvido por Afonso Inácio | PAP TGPSI</p>
    </div>
</footer>

<script src="js/java-pap.js"></script>
</body>
</html>