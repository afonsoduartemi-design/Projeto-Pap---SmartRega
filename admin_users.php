<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["tipo"] != "admin") {
    header("Location: index.php");
    exit;
}

$current_page = 'gestoes';

if (empty($_SESSION['csrf_token'])) {
    // Compatível com PHP 5.x e 7.x+
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_id'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ação não autorizada.");
    }
    $id = intval($_POST['delete_id']);
    if ($id == $_SESSION['user_id']) {
        header("Location: admin_users.php?erro=self_delete");
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id_utilizador = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_users.php?deleted=1");
    exit;
}

$result = $conn->query("SELECT id_utilizador, nome_utilizador, email_utilizador, tipo_utilizador FROM users ORDER BY id_utilizador ASC");
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Utilizadores — SmartRega</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style_pap.css">
    <style>
        .badge-tipo {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-admin-tipo         { background:#e3f2fd; color:#0d47a1; }
        .badge-cliente-atual-tipo { background:#e8f5e9; color:#1b5e20; }
        .badge-cliente-tipo       { background:#fff8e1; color:#e65100; }
        .btn-edit-link {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 5px 12px;
            background: #e3f2fd;
            border-radius: 6px;
            border: 1px solid #90caf9;
            transition: all 0.25s ease;
            margin-right: 6px;
        }
        .btn-edit-link:hover { background: var(--secondary-color); color: white; }
        .btn-delete-inline {
            width: auto !important;
            padding: 5px 14px !important;
            margin: 0 !important;
            font-size: 0.82rem;
            border-radius: 6px;
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-delete-inline:hover { background: #c62828; color: white; transform: none; box-shadow: none; }
        .action-buttons { display: flex; gap: 8px; align-items: center; }
        form.delete-form { margin: 0; }
    </style>
</head>
<body>

<header>
    <a href="dashboard.php" class="logo"><img src="img/logo.png" alt="SmartRega"></a>
    <nav>
        <a href="dashboard.php">Painel</a>
        <a href="projeto.php">O Projeto</a>
        <a href="gestoes.php" class="nav-active"><i class="fas fa-sliders-h"></i> Gestões</a>
        <span class="user-menu">
            <button type="button" class="user-menu-trigger">
                <i class="fas fa-user"></i>&nbsp;<?php echo htmlspecialchars($_SESSION["nome"]);
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

<div class="admin-container">
    <div class="admin-header">
        <h2><i class="fas fa-cog"></i> Gestão de Utilizadores</h2>
        <p>Gere quem tem acesso ao sistema SmartRega.</p>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <p class="alert-success"><i class="fas fa-check"></i> Utilizador apagado com sucesso.</p>
    <?php endif; ?>
    <?php if (isset($_GET['edit_success'])): ?>
        <p class="alert-success"><i class="fas fa-check"></i> Utilizador atualizado com sucesso.</p>
    <?php endif; ?>
    <?php if (isset($_GET['erro']) && $_GET['erro'] == 'self_delete'): ?>
        <p class="alert-error"><i class="fas fa-exclamation-triangle"></i> Não podes apagar a tua própria conta.</p>
    <?php endif; ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $result->fetch_assoc()):
                    $badgeClass = 'badge-cliente-tipo';
                    if ($user['tipo_utilizador'] === 'admin') {
                        $badgeClass = 'badge-admin-tipo';
                    } elseif ($user['tipo_utilizador'] === 'cliente_atual') {
                        $badgeClass = 'badge-cliente-atual-tipo';
                    }
                    $tipoLabel = str_replace('_', ' ', ucfirst($user['tipo_utilizador']));
                ?>
                <tr>
                    <td style="color:#bbb; font-size:0.85rem;">#<?php echo $user['id_utilizador']; ?></td>
                    <td><strong><?php echo htmlspecialchars($user['nome_utilizador']); ?></strong></td>
                    <td style="color:#666;"><?php echo htmlspecialchars($user['email_utilizador']); ?></td>
                    <td>
                        <span class="badge-tipo <?php echo $badgeClass; ?>">
                            <?php echo $tipoLabel; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="edit_user.php?id=<?php echo $user['id_utilizador']; ?>" class="btn-edit-link"><i class="fas fa-edit"></i> Editar</a>

                            <?php if ($user['id_utilizador'] != $_SESSION['user_id']): ?>
                            <form method="POST" class="delete-form">
                                <input type="hidden" name="delete_id"   value="<?php echo $user['id_utilizador']; ?>">
                                <input type="hidden" name="csrf_token"  value="<?php echo $_SESSION['csrf_token']; ?>">
                                <button type="button" class="btn-delete-inline btn-delete-trigger" data-user="<?php echo htmlspecialchars($user['nome_utilizador']); ?>"><i class="fas fa-trash"></i> Apagar</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CUSTOMIZADO PARA APAGAR UTILIZADOR -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon danger">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h3>Apagar Utilizador?</h3>
        <p>Tens a certeza que queres apagar <strong id="deleteUserName"></strong>? Esta ação não pode ser desfeita.</p>
        <div class="modal-buttons">
            <button type="button" class="modal-btn modal-btn-cancel" id="cancelDelete">Cancelar</button>
            <button type="button" class="modal-btn modal-btn-danger" id="confirmDelete">Apagar Utilizador</button>
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

<script>
    let currentDeleteForm = null;

    // Abrir modal de delete
    document.querySelectorAll('.btn-delete-trigger').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            currentDeleteForm = this.closest('form');
            const userName = this.getAttribute('data-user');
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').classList.add('active');
        });
    });

    // Fechar modal
    document.getElementById('cancelDelete').addEventListener('click', function() {
        document.getElementById('deleteModal').classList.remove('active');
        currentDeleteForm = null;
    });

    // Fechar modal ao clicar fora
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
            currentDeleteForm = null;
        }
    });

    // Confirmar delete
    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (currentDeleteForm) {
            currentDeleteForm.submit();
        }
    });
</script>

<script src="js/java-pap.js"></script>

</body>
</html>