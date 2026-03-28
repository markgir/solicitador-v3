<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) redirect('/admin/appointments.php');

$fetchStmt = $db->prepare("SELECT a.*, s.name_pt as service_name_pt, s.name_fr as service_name_fr, s.slug as service_slug FROM appointments a LEFT JOIN services s ON a.service_id = s.id WHERE a.id = ?");
$fetchStmt->execute([$id]);
$apt = $fetchStmt->fetch();

if (!$apt) redirect('/admin/appointments.php');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_status') {
            $newStatus = $_POST['status'] ?? '';
            if (in_array($newStatus, ['pending','confirmed','completed','cancelled'])) {
                $db->prepare("UPDATE appointments SET status = ?, updated_at = datetime('now') WHERE id = ?")->execute([$newStatus, $id]);
                $success = 'Estado actualizado.';
            }
        } elseif ($action === 'update_paid') {
            $paid = (int)($_POST['paid'] ?? 0);
            $db->prepare("UPDATE appointments SET paid = ?, updated_at = datetime('now') WHERE id = ?")->execute([$paid ? 1 : 0, $id]);
            $success = 'Pagamento actualizado.';
        } elseif ($action === 'update_notes') {
            $notes = trim($_POST['admin_notes'] ?? '');
            $db->prepare("UPDATE appointments SET admin_notes = ?, updated_at = datetime('now') WHERE id = ?")->execute([$notes, $id]);
            $success = 'Notas actualizadas.';
        }

        $fetchStmt->execute([$id]);
        $apt = $fetchStmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta #<?= $id ?> | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-logo">Solicitador</div>
        <nav class="sidebar-nav">
            <a href="/admin/index.php" class="sidebar-link">&#x1F4CA; Painel</a>
            <a href="/admin/appointments.php" class="sidebar-link active">&#x1F4C5; Consultas</a>
            <a href="/admin/services.php" class="sidebar-link">&#x1F4CB; Serviços</a>
        </nav>
        <div class="sidebar-footer">
            <span><?= sanitize($_SESSION['admin_username'] ?? '') ?></span>
            <a href="/admin/logout.php" class="sidebar-link">&#x1F6AA; Sair</a>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <a href="/admin/appointments.php" class="back-link">&larr; Consultas</a>
            <h1>Consulta #<?= $id ?></h1>
        </header>

        <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <div class="detail-grid">
            <div class="admin-card">
                <h2>Dados do Cliente</h2>
                <table class="detail-table">
                    <tr><th>Nome</th><td><?= sanitize($apt['name']) ?></td></tr>
                    <tr><th>Email</th><td><a href="mailto:<?= sanitize($apt['email']) ?>"><?= sanitize($apt['email']) ?></a></td></tr>
                    <tr><th>Telefone</th><td><?= sanitize($apt['phone']) ?></td></tr>
                    <tr><th>NIF</th><td><?= sanitize($apt['nif'] ?: '-') ?></td></tr>
                    <tr><th>Morada</th><td><?= sanitize($apt['address'] ?: '-') ?></td></tr>
                    <tr><th>Serviço</th><td><?= sanitize($apt['service_name_pt'] ?? '-') ?></td></tr>
                    <tr><th>Data Preferida</th><td><?= sanitize($apt['preferred_date']) ?> às <?= sanitize($apt['preferred_time']) ?></td></tr>
                    <tr><th>Notas do Cliente</th><td><?= sanitize($apt['notes'] ?: '-') ?></td></tr>
                    <tr><th>Referência</th><td><strong><?= sanitize($apt['payment_reference']) ?></strong></td></tr>
                    <tr><th>Criado em</th><td><?= sanitize($apt['created_at']) ?></td></tr>
                </table>
            </div>

            <div class="admin-card">
                <h2>Gerir Consulta</h2>

                <form method="POST" style="margin-bottom:1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update_status">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="status">
                            <option value="pending"   <?= $apt['status'] === 'pending'   ? 'selected' : '' ?>>Pendente</option>
                            <option value="confirmed" <?= $apt['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmado</option>
                            <option value="completed" <?= $apt['status'] === 'completed' ? 'selected' : '' ?>>Concluído</option>
                            <option value="cancelled" <?= $apt['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                </form>

                <form method="POST" style="margin-bottom:1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update_paid">
                    <div class="form-group">
                        <label>Pagamento</label>
                        <select name="paid">
                            <option value="0" <?= !$apt['paid'] ? 'selected' : '' ?>>Por Pagar</option>
                            <option value="1" <?= $apt['paid']  ? 'selected' : '' ?>>Pago</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Actualizar Pagamento</button>
                </form>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update_notes">
                    <div class="form-group">
                        <label>Notas Internas</label>
                        <textarea name="admin_notes" rows="4"><?= sanitize($apt['admin_notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Notas</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
