<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);

    if ($action === 'toggle_active' && $itemId) {
        $current = $db->prepare("SELECT active FROM portfolio_items WHERE id = ?");
        $current->execute([$itemId]);
        $row = $current->fetch();
        if ($row) {
            $db->prepare("UPDATE portfolio_items SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $itemId]);
        }
    } elseif ($action === 'delete' && $itemId) {
        $db->prepare("DELETE FROM portfolio_items WHERE id = ?")->execute([$itemId]);
    }
    redirect('/admin/portfolio.php');
}

try {
    $items = $db->query("SELECT * FROM portfolio_items ORDER BY sort_order ASC, created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $items = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfólio | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Portfólio</h1>
            <a href="/admin/portfolio-edit.php" class="btn btn-gold">+ Novo Item</a>
        </header>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Imagem</th>
                            <th>Título (PT)</th>
                            <th>Título (FR)</th>
                            <th>Ord.</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= (int)$item['id'] ?></td>
                            <td>
                                <?php if ($item['image_url']): ?>
                                <img src="<?= sanitize($item['image_url']) ?>" alt="" style="width:80px;height:60px;object-fit:cover;border-radius:4px;">
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($item['title_pt']) ?></td>
                            <td><?= sanitize($item['title_fr']) ?></td>
                            <td><?= (int)$item['sort_order'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $item['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $item['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="/admin/portfolio-edit.php?id=<?= (int)$item['id'] ?>" class="btn btn-sm">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este item do portfólio?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($items)): ?>
                        <tr><td colspan="7" class="text-center">Sem itens no portfólio. Adicione o primeiro item.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
