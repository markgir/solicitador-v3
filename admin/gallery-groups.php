<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action  = $_POST['action'] ?? '';
    $groupId = (int)($_POST['group_id'] ?? 0);

    if ($action === 'toggle_active' && $groupId) {
        $current = $db->prepare("SELECT active FROM gallery_groups WHERE id = ?");
        $current->execute([$groupId]);
        $row = $current->fetch();
        if ($row) {
            $db->prepare("UPDATE gallery_groups SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $groupId]);
        }
    } elseif ($action === 'delete' && $groupId) {
        // Delete associated images first
        $db->prepare("DELETE FROM gallery_images WHERE group_id = ?")->execute([$groupId]);
        $db->prepare("DELETE FROM gallery_groups WHERE id = ?")->execute([$groupId]);
    } elseif ($action === 'toggle_gallery_page') {
        $current = get_setting($db, 'gallery_active');
        set_setting($db, 'gallery_active', $current === '0' ? '1' : '0');
    }
    redirect('/admin/gallery-groups.php');
}

try {
    $groups = $db->query("SELECT g.*, (SELECT COUNT(*) FROM gallery_images gi WHERE gi.group_id = g.id) as image_count FROM gallery_groups g ORDER BY g.sort_order ASC, g.created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $groups = [];
}

$galleryActive = get_setting($db, 'gallery_active');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria - Grupos | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Galeria de Imagens - Grupos</h1>
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="toggle_gallery_page">
                    <button type="submit" class="btn btn-sm <?= $galleryActive !== '0' ? 'btn-success' : 'btn-muted' ?>">
                        Página <?= $galleryActive !== '0' ? 'Ativa' : 'Inativa' ?>
                    </button>
                </form>
                <a href="/admin/gallery-group-edit.php" class="btn btn-gold">+ Novo Grupo</a>
            </div>
        </header>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Capa</th>
                            <th>Nome (PT)</th>
                            <th>Nome (FR)</th>
                            <th>Imagens</th>
                            <th>Ord.</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?= (int)$group['id'] ?></td>
                            <td>
                                <?php if (!empty($group['cover_image_url'])): ?>
                                <img src="<?= sanitize($group['cover_image_url']) ?>" alt="" style="width:80px;height:60px;object-fit:cover;border-radius:4px;">
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($group['name_pt']) ?></td>
                            <td><?= sanitize($group['name_fr']) ?></td>
                            <td><?= (int)$group['image_count'] ?></td>
                            <td><?= (int)$group['sort_order'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="group_id" value="<?= (int)$group['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $group['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $group['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="/admin/gallery-group-edit.php?id=<?= (int)$group['id'] ?>" class="btn btn-sm">Editar</a>
                                <a href="/admin/gallery.php?group_id=<?= (int)$group['id'] ?>" class="btn btn-sm">Imagens</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este grupo e todas as suas imagens?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="group_id" value="<?= (int)$group['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($groups)): ?>
                        <tr><td colspan="8" class="text-center">Sem grupos na galeria. Crie o primeiro grupo.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
