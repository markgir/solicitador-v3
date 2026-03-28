<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action   = $_POST['action'] ?? '';
    $bannerId = (int)($_POST['banner_id'] ?? 0);

    if ($action === 'toggle_active' && $bannerId) {
        $current = $db->prepare("SELECT active FROM banners WHERE id = ?");
        $current->execute([$bannerId]);
        $row = $current->fetch();
        if ($row) {
            $db->prepare("UPDATE banners SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $bannerId]);
        }
    } elseif ($action === 'delete' && $bannerId) {
        $db->prepare("DELETE FROM banners WHERE id = ?")->execute([$bannerId]);
    }
    redirect('/admin/banners.php');
}

$banners = $db->query("SELECT * FROM banners ORDER BY sort_order ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banners | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Banners</h1>
            <a href="/admin/banner-edit.php" class="btn btn-gold">+ Novo Banner</a>
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
                        <?php foreach ($banners as $banner): ?>
                        <tr>
                            <td><?= (int)$banner['id'] ?></td>
                            <td>
                                <?php if ($banner['image_url']): ?>
                                <img src="<?= sanitize($banner['image_url']) ?>" alt="" style="width:80px;height:40px;object-fit:cover;border-radius:4px;">
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($banner['title_pt']) ?></td>
                            <td><?= sanitize($banner['title_fr']) ?></td>
                            <td><?= (int)$banner['sort_order'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="banner_id" value="<?= (int)$banner['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $banner['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $banner['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="/admin/banner-edit.php?id=<?= (int)$banner['id'] ?>" class="btn btn-sm">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este banner?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="banner_id" value="<?= (int)$banner['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($banners)): ?>
                        <tr><td colspan="7" class="text-center">Sem banners. Adicione o primeiro banner.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
