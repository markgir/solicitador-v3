<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action    = $_POST['action'] ?? '';
    $serviceId = (int)($_POST['service_id'] ?? 0);

    if ($action === 'toggle_active' && $serviceId) {
        $current = $db->prepare("SELECT active FROM services WHERE id = ?");
        $current->execute([$serviceId]);
        $row = $current->fetch();
        if ($row) {
            $db->prepare("UPDATE services SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $serviceId]);
        }
    } elseif ($action === 'delete' && $serviceId) {
        $db->prepare("DELETE FROM services WHERE id = ?")->execute([$serviceId]);
    }
    redirect('/admin/services.php');
}

$services = $db->query("SELECT * FROM services ORDER BY sort_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Serviços | Admin Solicitador</title>
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
            <a href="/admin/appointments.php" class="sidebar-link">&#x1F4C5; Consultas</a>
            <a href="/admin/services.php" class="sidebar-link active">&#x1F4CB; Serviços</a>
        </nav>
        <div class="sidebar-footer">
            <span><?= sanitize($_SESSION['admin_username'] ?? '') ?></span>
            <a href="/admin/logout.php" class="sidebar-link">&#x1F6AA; Sair</a>
        </div>
    </aside>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Serviços</h1>
            <a href="/admin/service-edit.php" class="btn btn-gold">+ Novo Serviço</a>
        </header>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ord.</th>
                            <th>Nome (PT)</th>
                            <th>Nome (FR)</th>
                            <th>Slug</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $svc): ?>
                        <tr>
                            <td><?= (int)$svc['id'] ?></td>
                            <td><?= (int)$svc['sort_order'] ?></td>
                            <td><?= sanitize($svc['name_pt']) ?></td>
                            <td><?= sanitize($svc['name_fr']) ?></td>
                            <td><code><?= sanitize($svc['slug']) ?></code></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="service_id" value="<?= (int)$svc['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $svc['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $svc['active'] ? 'Activo' : 'Inactivo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="/admin/service-edit.php?id=<?= (int)$svc['id'] ?>" class="btn btn-sm">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este serviço?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="service_id" value="<?= (int)$svc['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
