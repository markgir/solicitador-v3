<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

if (!$id) redirect('/admin/messages.php');

$fetchStmt = $db->prepare("SELECT * FROM contact_messages WHERE id = ?");
$fetchStmt->execute([$id]);
$msg = $fetchStmt->fetch();

if (!$msg) redirect('/admin/messages.php');

// Auto-mark as read when viewed
if ($msg['status'] === 'new') {
    $db->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?")->execute([$id]);
    $msg['status'] = 'read';
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Token inválido.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_status') {
            $newStatus = $_POST['status'] ?? '';
            if (in_array($newStatus, ['new', 'read', 'replied'])) {
                $db->prepare("UPDATE contact_messages SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
                $success = 'Estado atualizado.';
            }
        } elseif ($action === 'update_notes') {
            $notes = trim($_POST['admin_notes'] ?? '');
            $db->prepare("UPDATE contact_messages SET admin_notes = ? WHERE id = ?")->execute([$notes, $id]);
            $success = 'Notas atualizadas.';
        } elseif ($action === 'delete') {
            $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
            redirect('/admin/messages.php');
        }

        $fetchStmt = $db->prepare("SELECT * FROM contact_messages WHERE id = ?");
        $fetchStmt->execute([$id]);
        $msg = $fetchStmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagem #<?= $id ?> | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <a href="/admin/messages.php" class="back-link">&larr; Mensagens</a>
            <h1>Mensagem #<?= $id ?></h1>
        </header>

        <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="alert alert-error"><?= sanitize($error) ?></div><?php endif; ?>

        <div class="detail-grid">
            <div class="admin-card">
                <h2>Dados da Mensagem</h2>
                <table class="detail-table">
                    <tr><th>Nome</th><td><?= sanitize($msg['name']) ?></td></tr>
                    <tr><th>Email</th><td><a href="mailto:<?= sanitize($msg['email']) ?>"><?= sanitize($msg['email']) ?></a></td></tr>
                    <tr><th>Telefone</th><td><?= sanitize($msg['phone'] ?: '-') ?></td></tr>
                    <tr><th>Assunto</th><td><strong><?= sanitize($msg['subject']) ?></strong></td></tr>
                    <tr><th>Mensagem</th><td style="white-space:pre-wrap;"><?= sanitize($msg['message']) ?></td></tr>
                    <tr>
                        <th>Documento</th>
                        <td>
                            <?php if ($msg['document_url']): ?>
                                <a href="<?= sanitize($msg['document_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm">&#x1F4CE; <?= sanitize($msg['document_filename']) ?></a>
                            <?php else: ?>
                                Sem documento anexado
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr><th>Estado</th><td>
                        <?php
                        $statusLabel = match($msg['status']) {
                            'new' => 'Nova',
                            'read' => 'Lida',
                            'replied' => 'Respondida',
                            default => $msg['status'],
                        };
                        ?>
                        <span class="badge badge-<?= $msg['status'] === 'new' ? 'pending' : ($msg['status'] === 'read' ? 'confirmed' : 'completed') ?>"><?= $statusLabel ?></span>
                    </td></tr>
                    <tr><th>Recebida em</th><td><?= sanitize($msg['created_at']) ?></td></tr>
                </table>
            </div>

            <div class="admin-card">
                <h2>Gerir Mensagem</h2>

                <form method="POST" style="margin-bottom:1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update_status">
                    <div class="form-group">
                        <label>Estado</label>
                        <select name="status">
                            <option value="new"     <?= $msg['status'] === 'new'     ? 'selected' : '' ?>>Nova</option>
                            <option value="read"    <?= $msg['status'] === 'read'    ? 'selected' : '' ?>>Lida</option>
                            <option value="replied" <?= $msg['status'] === 'replied' ? 'selected' : '' ?>>Respondida</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Atualizar Estado</button>
                </form>

                <form method="POST" style="margin-bottom:1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="update_notes">
                    <div class="form-group">
                        <label>Notas Internas</label>
                        <textarea name="admin_notes" rows="4"><?= sanitize($msg['admin_notes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Notas</button>
                </form>

                <form method="POST" onsubmit="return confirm('Tem a certeza que deseja apagar esta mensagem?');">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">Apagar Mensagem</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
