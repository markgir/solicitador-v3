<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $imageId = (int)($_POST['image_id'] ?? 0);

    if ($action === 'toggle_active' && $imageId) {
        $current = $db->prepare("SELECT active FROM gallery_images WHERE id = ?");
        $current->execute([$imageId]);
        $row = $current->fetch();
        if ($row) {
            $db->prepare("UPDATE gallery_images SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $imageId]);
        }
    } elseif ($action === 'delete' && $imageId) {
        $db->prepare("DELETE FROM gallery_images WHERE id = ?")->execute([$imageId]);
    }
    redirect('/admin/gallery.php');
}

try {
    $images = $db->query("SELECT * FROM gallery_images ORDER BY sort_order ASC, created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $images = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Galeria de Imagens</h1>
            <a href="/admin/gallery-edit.php" class="btn btn-gold">+ Nova Imagem</a>
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
                        <?php foreach ($images as $image): ?>
                        <tr>
                            <td><?= (int)$image['id'] ?></td>
                            <td>
                                <?php if ($image['image_url']): ?>
                                <img src="<?= sanitize($image['image_url']) ?>" alt="" style="width:80px;height:60px;object-fit:cover;border-radius:4px;">
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($image['title_pt']) ?></td>
                            <td><?= sanitize($image['title_fr']) ?></td>
                            <td><?= (int)$image['sort_order'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $image['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $image['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="/admin/gallery-edit.php?id=<?= (int)$image['id'] ?>" class="btn btn-sm">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar esta imagem da galeria?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="image_id" value="<?= (int)$image['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($images)): ?>
                        <tr><td colspan="7" class="text-center">Sem imagens na galeria. Adicione a primeira imagem.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
