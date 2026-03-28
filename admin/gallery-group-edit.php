<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$id     = (int)($_GET['id'] ?? 0);
$group  = null;
$isEdit = false;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM gallery_groups WHERE id = ?");
    $stmt->execute([$id]);
    $group = $stmt->fetch();
    if ($group) $isEdit = true;
}

$errors   = [];
$formData = [
    'name_pt'     => $group['name_pt']     ?? '',
    'name_fr'     => $group['name_fr']     ?? '',
    'sort_order'  => $group['sort_order']  ?? 0,
    'active'      => $group['active']      ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $formData['name_pt']    = trim($_POST['name_pt']    ?? '');
        $formData['name_fr']    = trim($_POST['name_fr']    ?? '');
        $formData['sort_order'] = (int)($_POST['sort_order'] ?? 0);
        $formData['active']     = isset($_POST['active']) ? 1 : 0;

        if (empty($formData['name_pt'])) {
            $errors[] = 'O nome (PT) é obrigatório.';
        }

        $coverUrl = $group['cover_image_url'] ?? '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['cover_image'], 'gallery');
            if ($uploaded) {
                $coverUrl = $uploaded;
            } else {
                $errors[] = 'Erro ao carregar imagem de capa.';
            }
        }

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->prepare("UPDATE gallery_groups SET name_pt=?, name_fr=?, cover_image_url=?, sort_order=?, active=? WHERE id=?")
                       ->execute([$formData['name_pt'], $formData['name_fr'], $coverUrl, $formData['sort_order'], $formData['active'], $id]);
                } else {
                    $db->prepare("INSERT INTO gallery_groups (name_pt, name_fr, cover_image_url, sort_order, active) VALUES (?,?,?,?,?)")
                       ->execute([$formData['name_pt'], $formData['name_fr'], $coverUrl, $formData['sort_order'], $formData['active']]);
                }
                redirect('/admin/gallery-groups.php');
            } catch (Exception $e) {
                $errors[] = 'Erro ao guardar: ' . $e->getMessage();
            }
        }
    }
}

$pageAction = $isEdit ? 'Editar Grupo' : 'Novo Grupo';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageAction) ?> | Galeria | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <a href="/admin/gallery-groups.php" class="back-link">&larr; Grupos</a>
            <h1><?= sanitize($pageAction) ?></h1>
        </header>

        <?php if (!empty($errors)): ?>
        <div class="admin-card"><div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div></div>
        <?php endif; ?>

        <div class="admin-card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="name_pt">Nome do Grupo (PT) *</label>
                        <input type="text" id="name_pt" name="name_pt" value="<?= sanitize($formData['name_pt']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="name_fr">Nome do Grupo (FR)</label>
                        <input type="text" id="name_fr" name="name_fr" value="<?= sanitize($formData['name_fr']) ?>">
                    </div>
                    <div class="form-group form-full">
                        <label for="cover_image">Imagem de Capa</label>
                        <?php if ($isEdit && !empty($group['cover_image_url'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <img src="<?= sanitize($group['cover_image_url']) ?>" alt="" style="max-width:300px;border-radius:6px;">
                        </div>
                        <?php endif; ?>
                        <input type="file" id="cover_image" name="cover_image" accept="image/*">
                        <small>Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB. Opcional — se vazio, será usada a primeira imagem do grupo.</small>
                    </div>
                    <div class="form-group">
                        <label for="sort_order">Ordem</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?= (int)$formData['sort_order'] ?>" min="0">
                    </div>
                    <div class="form-group form-full">
                        <label>
                            <input type="checkbox" name="active" value="1" <?= $formData['active'] ? 'checked' : '' ?>>
                            Ativo
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-gold btn-large">Guardar</button>
                    <a href="/admin/gallery-groups.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
