<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$filterGroupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
$success = '';

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
    } elseif ($action === 'upload_multiple') {
        $groupId = (int)($_POST['group_id'] ?? 0);
        if ($groupId && isset($_FILES['images'])) {
            $uploaded = 0;
            $fileCount = count($_FILES['images']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $singleFile = [
                        'name'     => $_FILES['images']['name'][$i],
                        'type'     => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error'    => $_FILES['images']['error'][$i],
                        'size'     => $_FILES['images']['size'][$i],
                    ];
                    $uploadedPath = upload_image($singleFile, 'gallery');
                    if ($uploadedPath) {
                        $db->prepare("INSERT INTO gallery_images (group_id, image_url, sort_order, active) VALUES (?,?,0,1)")
                           ->execute([$groupId, $uploadedPath]);
                        $uploaded++;
                    }
                }
            }
            $success = $uploaded . ' imagem(ns) importada(s) com sucesso.';
            $filterGroupId = $groupId;
        }
    }
    if (empty($success)) {
        redirect('/admin/gallery.php' . ($filterGroupId ? '?group_id=' . $filterGroupId : ''));
    }
}

// Get group info if filtering
$filterGroup = null;
if ($filterGroupId) {
    $stmt = $db->prepare("SELECT * FROM gallery_groups WHERE id = ?");
    $stmt->execute([$filterGroupId]);
    $filterGroup = $stmt->fetch();
}

// Fetch groups for dropdown
try {
    $allGroups = $db->query("SELECT * FROM gallery_groups ORDER BY sort_order ASC, name_pt ASC")->fetchAll();
} catch (PDOException $e) {
    $allGroups = [];
}

try {
    if ($filterGroupId) {
        $stmt = $db->prepare("SELECT gi.*, gg.name_pt as group_name FROM gallery_images gi LEFT JOIN gallery_groups gg ON gi.group_id = gg.id WHERE gi.group_id = ? ORDER BY gi.sort_order ASC, gi.created_at DESC");
        $stmt->execute([$filterGroupId]);
        $images = $stmt->fetchAll();
    } else {
        $images = $db->query("SELECT gi.*, gg.name_pt as group_name FROM gallery_images gi LEFT JOIN gallery_groups gg ON gi.group_id = gg.id ORDER BY gi.sort_order ASC, gi.created_at DESC")->fetchAll();
    }
} catch (PDOException $e) {
    $images = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria<?= $filterGroup ? ' - ' . sanitize($filterGroup['name_pt']) : '' ?> | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <?php if ($filterGroup): ?>
            <div>
                <a href="/admin/gallery-groups.php" class="back-link">&larr; Grupos</a>
                <h1>Imagens: <?= sanitize($filterGroup['name_pt']) ?></h1>
            </div>
            <?php else: ?>
            <h1>Galeria de Imagens</h1>
            <?php endif; ?>
            <div style="display:flex;gap:0.5rem;">
                <a href="/admin/gallery-edit.php<?= $filterGroupId ? '?group_id=' . $filterGroupId : '' ?>" class="btn btn-gold">+ Nova Imagem</a>
            </div>
        </header>

        <?php if ($success): ?>
        <div class="admin-card"><div class="alert alert-success"><?= sanitize($success) ?></div></div>
        <?php endif; ?>

        <?php if ($filterGroupId && !empty($allGroups)): ?>
        <!-- Multi-image upload -->
        <div class="admin-card">
            <h2>Importar Múltiplas Imagens</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="upload_multiple">
                <input type="hidden" name="group_id" value="<?= $filterGroupId ?>">
                <div class="form-group">
                    <label for="multi_images">Selecionar Imagens</label>
                    <input type="file" id="multi_images" name="images[]" accept="image/*" multiple required>
                    <small>Selecione várias imagens de uma vez. Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB cada.</small>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-gold">Importar Imagens</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Imagem</th>
                            <th>Título (PT)</th>
                            <?php if (!$filterGroupId): ?><th>Grupo</th><?php endif; ?>
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
                            <?php if (!$filterGroupId): ?><td><?= sanitize($image['group_name'] ?? '-') ?></td><?php endif; ?>
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
                        <tr><td colspan="<?= $filterGroupId ? 6 : 7 ?>" class="text-center">Sem imagens<?= $filterGroup ? ' neste grupo' : ' na galeria' ?>. Adicione a primeira imagem.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
