<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

$statusFilter = $_GET['status'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

// Handle toggle status / delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token inválido.';
    } else {
        $action = $_POST['action'] ?? '';
        $msgId  = (int)($_POST['id'] ?? 0);

        if ($action === 'delete' && $msgId) {
            $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$msgId]);
        } elseif ($action === 'mark_read' && $msgId) {
            $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?")->execute([$msgId]);
        }
    }
}

$where  = [];
$params = [];

if (in_array($statusFilter, ['new', 'read', 'replied'])) {
    $where[]  = "status = ?";
    $params[] = $statusFilter;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM contact_messages $whereSQL");
$countStmt->execute($params);
$total      = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$stmt = $db->prepare("SELECT * FROM contact_messages $whereSQL ORDER BY created_at DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$stmt->execute($params);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Mensagens de Contacto</h1>
        </header>

        <div class="admin-filters">
            <form method="GET" action="/admin/messages.php" class="filter-form">
                <select name="status">
                    <option value="">Todos os Estados</option>
                    <option value="new"     <?= $statusFilter === 'new'     ? 'selected' : '' ?>>Nova</option>
                    <option value="read"    <?= $statusFilter === 'read'    ? 'selected' : '' ?>>Lida</option>
                    <option value="replied" <?= $statusFilter === 'replied' ? 'selected' : '' ?>>Respondida</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="/admin/messages.php" class="btn btn-outline">Limpar</a>
            </form>
        </div>

        <div class="admin-card">
            <p class="results-count">Total: <strong><?= $total ?></strong> mensagens</p>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Assunto</th>
                            <th>Documento</th>
                            <th>Estado</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr class="<?= $msg['status'] === 'new' ? 'row-highlight' : '' ?>">
                            <td><?= (int)$msg['id'] ?></td>
                            <td><?= sanitize($msg['name']) ?></td>
                            <td><a href="mailto:<?= sanitize($msg['email']) ?>"><?= sanitize($msg['email']) ?></a></td>
                            <td><?= sanitize($msg['subject']) ?></td>
                            <td>
                                <?php if ($msg['document_url']): ?>
                                    <a href="<?= sanitize($msg['document_url']) ?>" target="_blank" rel="noopener noreferrer" title="<?= sanitize($msg['document_filename']) ?>">&#x1F4CE; <?= sanitize($msg['document_filename']) ?></a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match($msg['status']) {
                                    'new' => 'badge-pending',
                                    'read' => 'badge-confirmed',
                                    'replied' => 'badge-completed',
                                    default => 'badge-pending',
                                };
                                $statusLabel = match($msg['status']) {
                                    'new' => 'Nova',
                                    'read' => 'Lida',
                                    'replied' => 'Respondida',
                                    default => $msg['status'],
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td><?= sanitize(substr($msg['created_at'], 0, 10)) ?></td>
                            <td>
                                <a href="/admin/message-detail.php?id=<?= (int)$msg['id'] ?>" class="btn btn-sm">Ver</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Tem a certeza que deseja apagar esta mensagem?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Apagar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                        <tr><td colspan="8" class="text-center">Sem mensagens.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?status=<?= urlencode($statusFilter) ?>&amp;page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
