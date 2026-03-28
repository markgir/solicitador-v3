<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    // Handle logo upload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_image($_FILES['site_logo'], 'settings');
        if ($uploaded) {
            set_setting($db, 'site_logo', $uploaded);
            $success = 'Logo atualizado com sucesso.';
        } else {
            $errors[] = 'Erro ao carregar o logo.';
        }
    }

    // Handle logo removal
    if (isset($_POST['remove_logo'])) {
        set_setting($db, 'site_logo', '');
        $success = 'Logo removido com sucesso.';
    }

    // Handle parallax image upload
    if (isset($_FILES['parallax_image']) && $_FILES['parallax_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_image($_FILES['parallax_image'], 'settings');
        if ($uploaded) {
            set_setting($db, 'parallax_image', $uploaded);
            $success = 'Imagem parallax atualizada com sucesso.';
        } else {
            $errors[] = 'Erro ao carregar a imagem parallax.';
        }
    }

    // Handle parallax removal
    if (isset($_POST['remove_parallax'])) {
        set_setting($db, 'parallax_image', '');
        $success = 'Imagem parallax removida com sucesso.';
    }

    if (empty($errors) && empty($success)) {
        $success = 'Definições guardadas.';
    }
}

$siteLogo = get_setting($db, 'site_logo');
$parallaxImage = get_setting($db, 'parallax_image');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Definições | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Definições do Site</h1>
        </header>

        <?php if ($success): ?>
        <div class="admin-card"><div class="alert alert-success"><?= sanitize($success) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
        <div class="admin-card"><div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div></div>
        <?php endif; ?>

        <div class="admin-card">
            <h2>Logo do Site</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <?php if ($siteLogo): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="<?= sanitize($siteLogo) ?>" alt="Logo atual" style="max-height:80px;border-radius:6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <button type="submit" name="remove_logo" value="1" class="btn btn-sm btn-danger">Remover Logo</button>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="site_logo">Carregar Novo Logo</label>
                    <input type="file" id="site_logo" name="site_logo" accept="image/*">
                    <small>Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB. Recomendado: altura de 50px.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-gold">Guardar Logo</button>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <h2>Imagem Parallax</h2>
            <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.9rem;">Esta imagem aparece abaixo dos serviços na página principal com efeito parallax.</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <?php if ($parallaxImage): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="<?= sanitize($parallaxImage) ?>" alt="Parallax atual" style="max-width:400px;border-radius:6px;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <button type="submit" name="remove_parallax" value="1" class="btn btn-sm btn-danger">Remover Imagem</button>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="parallax_image">Carregar Imagem Parallax</label>
                    <input type="file" id="parallax_image" name="parallax_image" accept="image/*">
                    <small>Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB. Recomendado: 1920x600px.</small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-gold">Guardar Imagem</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
