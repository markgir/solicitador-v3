<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

$statusFilter = $_GET['status'] ?? '';
$paidFilter   = $_GET['paid'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];

if (in_array($statusFilter, ['pending','confirmed','completed','cancelled'])) {
    $where[]  = "a.status = ?";
    $params[] = $statusFilter;
}
if ($paidFilter === 'paid') {
    $where[] = "a.payment_status = 'paid'";
} elseif ($paidFilter === 'unpaid') {
    $where[] = "a.payment_status = 'unpaid'";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM appointments a $whereSQL");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$stmt = $db->prepare("SELECT a.*, s.title_pt as service_name FROM appointments a LEFT JOIN services s ON a.service_id = s.id $whereSQL ORDER BY a.created_at DESC LIMIT ? OFFSET ?");
$listParams   = array_values($params);
$listParams[] = $perPage;
$listParams[] = $offset;
$stmt->execute($listParams);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas | Admin Solicitador</title>
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
            <h1>Consultas</h1>
        </header>

        <div class="admin-filters">
            <form method="GET" action="/admin/appointments.php" class="filter-form">
                <select name="status">
                    <option value="">Todos os Estados</option>
                    <option value="pending"   <?= $statusFilter === 'pending'    ? 'selected' : '' ?>>Pendente</option>
                    <option value="confirmed" <?= $statusFilter === 'confirmed'  ? 'selected' : '' ?>>Confirmado</option>
                    <option value="completed" <?= $statusFilter === 'completed'  ? 'selected' : '' ?>>Concluído</option>
                    <option value="cancelled" <?= $statusFilter === 'cancelled'  ? 'selected' : '' ?>>Cancelado</option>
                </select>
                <select name="paid">
                    <option value="">Todos os Pagamentos</option>
                    <option value="paid"   <?= $paidFilter === 'paid'   ? 'selected' : '' ?>>Pago</option>
                    <option value="unpaid" <?= $paidFilter === 'unpaid' ? 'selected' : '' ?>>Por Pagar</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="/admin/appointments.php" class="btn btn-outline">Limpar</a>
            </form>
        </div>

        <div class="admin-card">
            <p class="results-count">Total: <strong><?= $total ?></strong> consultas</p>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Serviço</th>
                            <th>Data Preferida</th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th>Referência</th>
                            <th>Criado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $apt): ?>
                        <tr>
                            <td><?= (int)$apt['id'] ?></td>
                            <td><?= sanitize($apt['name']) ?></td>
                            <td><?= sanitize($apt['email']) ?></td>
                            <td><?= sanitize($apt['service_name'] ?? '-') ?></td>
                            <td><?= sanitize($apt['preferred_date']) ?> <?= sanitize($apt['preferred_time']) ?></td>
                            <td><span class="badge badge-<?= sanitize($apt['status']) ?>"><?= sanitize(get_status_label($apt['status'], 'pt')) ?></span></td>
                            <td><?= $apt['payment_status'] === 'paid' ? '<span class="badge badge-paid">Pago</span>' : '<span class="badge badge-unpaid">Por Pagar</span>' ?></td>
                            <td><code><?= sanitize($apt['payment_reference']) ?></code></td>
                            <td><?= sanitize(substr($apt['created_at'], 0, 10)) ?></td>
                            <td><a href="/admin/appointment-detail.php?id=<?= (int)$apt['id'] ?>" class="btn btn-sm">Ver</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($appointments)): ?>
                        <tr><td colspan="10" class="text-center">Sem resultados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?status=<?= urlencode($statusFilter) ?>&amp;paid=<?= urlencode($paidFilter) ?>&amp;page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
