<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        $sectionId = (int)($_POST['section_id'] ?? 0);
        if ($sectionId) {
            $current = $db->prepare("SELECT active FROM homepage_sections WHERE id = ?");
            $current->execute([$sectionId]);
            $row = $current->fetch();
            if ($row) {
                $db->prepare("UPDATE homepage_sections SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $sectionId]);
            }
        }
        redirect('/admin/sections.php');
    }

    if ($action === 'save_order') {
        $orders = $_POST['sort_order'] ?? [];
        $stmt = $db->prepare("UPDATE homepage_sections SET sort_order = ? WHERE id = ?");
        foreach ($orders as $sectionId => $order) {
            $stmt->execute([(int)$order, (int)$sectionId]);
        }
        $success = 'Ordem das secções atualizada com sucesso.';
    }
}

try {
    $sections = $db->query("SELECT * FROM homepage_sections ORDER BY sort_order ASC")->fetchAll();
} catch (PDOException $e) {
    $sections = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secções da Homepage | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Secções da Homepage</h1>
        </header>

        <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.95rem;">
            Gerencie a ordem e visibilidade das secções na página principal. Altere a ordem numérica e clique em &quot;Guardar Ordem&quot;. Ative ou desative cada secção para controlar o que aparece no site.
        </p>

        <?php if ($success): ?>
        <div class="admin-card"><div class="alert alert-success"><?= sanitize($success) ?></div></div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Secção</th>
                            <th style="width:100px;">Ordem</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections as $section): ?>
                        <tr>
                            <td>
                                <strong><?= sanitize($section['label_pt']) ?></strong>
                                <br><small style="color:var(--text-muted);"><?= sanitize($section['section_key']) ?></small>
                            </td>
                            <td>
                                <input type="number" class="section-order-input" data-id="<?= (int)$section['id'] ?>" value="<?= (int)$section['sort_order'] ?>" min="0" style="width:70px;">
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $section['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $section['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($sections)): ?>
                        <tr><td colspan="3" class="text-center">Sem secções configuradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($sections)): ?>
            <form method="POST" id="orderForm" style="margin-top:1rem;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_order">
                <div id="orderFields"></div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-gold">Guardar Ordem</button>
                </div>
            </form>
            <script>
            document.getElementById('orderForm').addEventListener('submit', function() {
                var container = document.getElementById('orderFields');
                container.innerHTML = '';
                document.querySelectorAll('.section-order-input').forEach(function(input) {
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'sort_order[' + input.dataset.id + ']';
                    hidden.value = input.value;
                    container.appendChild(hidden);
                });
            });
            </script>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
