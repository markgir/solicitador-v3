<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

$totalAppointments     = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$pendingAppointments   = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
$confirmedAppointments = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'confirmed'")->fetchColumn();
$paidAppointments      = $db->query("SELECT COUNT(*) FROM appointments WHERE payment_status = 'paid'")->fetchColumn();

$recentStmt = $db->query("SELECT a.*, s.title_pt as service_name FROM appointments a LEFT JOIN services s ON a.service_id = s.id ORDER BY a.created_at DESC LIMIT 10");
$recentAppointments = $recentStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Painel</h1>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= (int)$totalAppointments ?></div>
                <div class="stat-label">Total Consultas</div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-number"><?= (int)$pendingAppointments ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-number"><?= (int)$confirmedAppointments ?></div>
                <div class="stat-label">Confirmadas</div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-number"><?= (int)$paidAppointments ?></div>
                <div class="stat-label">Pagas</div>
            </div>
        </div>

        <div class="admin-card">
            <h2>Consultas Recentes</h2>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Serviço</th>
                            <th>Data</th>
                            <th>Estado</th>
                            <th>Pago</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAppointments as $apt): ?>
                        <tr>
                            <td><?= (int)$apt['id'] ?></td>
                            <td><?= sanitize($apt['name']) ?></td>
                            <td><?= sanitize($apt['service_name'] ?? '-') ?></td>
                            <td><?= sanitize($apt['preferred_date']) ?></td>
                            <td><span class="badge badge-<?= sanitize($apt['status']) ?>"><?= sanitize(get_status_label($apt['status'], 'pt')) ?></span></td>
                            <td><?= ($apt['payment_status'] ?? '') === 'paid' ? '&#x2705;' : '&#x274C;' ?></td>
                            <td><a href="/admin/appointment-detail.php?id=<?= (int)$apt['id'] ?>" class="btn btn-sm">Ver</a></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentAppointments)): ?>
                        <tr><td colspan="7" class="text-center">Sem consultas ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
