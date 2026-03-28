<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$id     = (int)($_GET['id'] ?? 0);
$item   = null;
$isEdit = false;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM portfolio_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) $isEdit = true;
}

$errors   = [];
$formData = [
    'title_pt'       => $item['title_pt']       ?? '',
    'title_fr'       => $item['title_fr']       ?? '',
    'description_pt' => $item['description_pt'] ?? '',
    'description_fr' => $item['description_fr'] ?? '',
    'sort_order'     => $item['sort_order']     ?? 0,
    'active'         => $item['active']         ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $formData['title_pt']       = trim($_POST['title_pt']       ?? '');
        $formData['title_fr']       = trim($_POST['title_fr']       ?? '');
        $formData['description_pt'] = trim($_POST['description_pt'] ?? '');
        $formData['description_fr'] = trim($_POST['description_fr'] ?? '');
        $formData['sort_order']     = (int)($_POST['sort_order']    ?? 0);
        $formData['active']         = isset($_POST['active']) ? 1 : 0;

        if (empty($formData['title_pt'])) {
            $errors[] = 'O título (PT) é obrigatório.';
        }

        $imageUrl = $item['image_url'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['image'], 'portfolio');
            if ($uploaded) {
                $imageUrl = $uploaded;
            } else {
                $errors[] = 'Erro ao carregar imagem. Verifique o formato (JPG, PNG, GIF, WEBP) e tamanho (máx. 5MB).';
            }
        }

        if (!$isEdit && empty($imageUrl)) {
            $errors[] = 'A imagem é obrigatória.';
        }

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->prepare("UPDATE portfolio_items SET title_pt=?, title_fr=?, description_pt=?, description_fr=?, image_url=?, sort_order=?, active=? WHERE id=?")->execute([
                        $formData['title_pt'], $formData['title_fr'],
                        $formData['description_pt'], $formData['description_fr'],
                        $imageUrl, $formData['sort_order'], $formData['active'], $id
                    ]);
                } else {
                    $db->prepare("INSERT INTO portfolio_items (title_pt, title_fr, description_pt, description_fr, image_url, sort_order, active) VALUES (?,?,?,?,?,?,?)")->execute([
                        $formData['title_pt'], $formData['title_fr'],
                        $formData['description_pt'], $formData['description_fr'],
                        $imageUrl, $formData['sort_order'], $formData['active']
                    ]);
                }
                redirect('/admin/portfolio.php');
            } catch (Exception $e) {
                $errors[] = 'Erro ao guardar: ' . $e->getMessage();
            }
        }
    }
}

$pageAction = $isEdit ? 'Editar Item' : 'Novo Item';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageAction) ?> | Portfólio | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <a href="/admin/portfolio.php" class="back-link">&larr; Portfólio</a>
            <h1><?= sanitize($pageAction) ?></h1>
        </header>

        <?php if (!empty($errors)): ?>
        <div class="admin-card"><div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div></div>
        <?php endif; ?>

        <div class="admin-card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-grid">
                    <div class="form-group form-full">
                        <label for="image">Imagem <?= $isEdit ? '' : '*' ?></label>
                        <?php if ($isEdit && !empty($item['image_url'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <img src="<?= sanitize($item['image_url']) ?>" alt="" style="max-width:300px;border-radius:6px;">
                        </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small>Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB. <?= $isEdit ? 'Deixe vazio para manter a imagem atual.' : '' ?></small>
                    </div>
                    <div class="form-group">
                        <label for="title_pt">Título (PT) *</label>
                        <input type="text" id="title_pt" name="title_pt" value="<?= sanitize($formData['title_pt']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="title_fr">Título (FR)</label>
                        <input type="text" id="title_fr" name="title_fr" value="<?= sanitize($formData['title_fr']) ?>">
                    </div>
                    <div class="form-group form-full">
                        <label for="description_pt">Descrição (PT)</label>
                        <textarea id="description_pt" name="description_pt" rows="3"><?= sanitize($formData['description_pt']) ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label for="description_fr">Descrição (FR)</label>
                        <textarea id="description_fr" name="description_fr" rows="3"><?= sanitize($formData['description_fr']) ?></textarea>
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
                    <a href="/admin/portfolio.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
