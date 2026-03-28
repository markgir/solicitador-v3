<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $menuId = (int)($_POST['menu_id'] ?? 0);

    if ($action === 'toggle_active' && $menuId) {
        $current = $db->prepare("SELECT active FROM menu_items WHERE id = ?");
        $current->execute([$menuId]);
        $row = $current->fetch();
        if ($row) {
            $db->prepare("UPDATE menu_items SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $menuId]);
        }
    } elseif ($action === 'delete' && $menuId) {
        $db->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$menuId]);
    }
    redirect('/admin/menu.php');
}

$menuItems = $db->query("SELECT * FROM menu_items ORDER BY sort_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Menu</h1>
            <a href="/admin/menu-edit.php" class="btn btn-gold">+ Novo Item</a>
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
                            <th>URL</th>
                            <th>Abrir em</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menuItems as $item): ?>
                        <tr>
                            <td><?= (int)$item['id'] ?></td>
                            <td><?= (int)$item['sort_order'] ?></td>
                            <td><?= sanitize($item['title_pt']) ?></td>
                            <td><?= sanitize($item['title_fr']) ?></td>
                            <td><code><?= sanitize($item['url']) ?></code></td>
                            <td><?= $item['target'] === '_blank' ? 'Nova janela' : 'Mesma janela' ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="menu_id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $item['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $item['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="/admin/menu-edit.php?id=<?= (int)$item['id'] ?>" class="btn btn-sm">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este item do menu?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="menu_id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($menuItems)): ?>
                        <tr><td colspan="8" class="text-center">Sem itens de menu.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
