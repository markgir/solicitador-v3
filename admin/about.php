<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    // Save presentation text
    if ($action === 'save_text') {
        $textPt = trim($_POST['about_text_pt'] ?? '');
        $textFr = trim($_POST['about_text_fr'] ?? '');
        set_setting($db, 'about_text_pt', $textPt);
        set_setting($db, 'about_text_fr', $textFr);
        $success = 'Texto de apresentação guardado com sucesso.';
    }

    // Toggle about page active
    if ($action === 'toggle_page') {
        $current = get_setting($db, 'about_active');
        set_setting($db, 'about_active', $current === '0' ? '1' : '0');
        redirect('/admin/about.php');
    }

    // Add image
    if ($action === 'add_image') {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['image'], 'about');
            if ($uploaded) {
                $captionPt = trim($_POST['caption_pt'] ?? '');
                $captionFr = trim($_POST['caption_fr'] ?? '');
                $sortOrder = (int)($_POST['sort_order'] ?? 0);
                $db->prepare("INSERT INTO about_images (image_url, caption_pt, caption_fr, sort_order, active) VALUES (?,?,?,?,1)")
                   ->execute([$uploaded, $captionPt, $captionFr, $sortOrder]);
                $success = 'Imagem adicionada com sucesso.';
            } else {
                $errors[] = 'Erro ao carregar imagem.';
            }
        } else {
            $errors[] = 'Selecione uma imagem.';
        }
    }

    // Delete image
    if ($action === 'delete_image') {
        $imgId = (int)($_POST['image_id'] ?? 0);
        if ($imgId) {
            $db->prepare("DELETE FROM about_images WHERE id = ?")->execute([$imgId]);
            $success = 'Imagem removida.';
        }
    }

    // Add partner
    if ($action === 'add_partner') {
        $name = trim($_POST['partner_name'] ?? '');
        $website = trim($_POST['partner_website'] ?? '');
        $sortOrder = (int)($_POST['partner_sort_order'] ?? 0);

        if (empty($name)) {
            $errors[] = 'O nome do parceiro é obrigatório.';
        } else {
            $logoUrl = '';
            if (isset($_FILES['partner_logo']) && $_FILES['partner_logo']['error'] === UPLOAD_ERR_OK) {
                $uploaded = upload_image($_FILES['partner_logo'], 'partners');
                if ($uploaded) {
                    $logoUrl = $uploaded;
                } else {
                    $errors[] = 'Erro ao carregar logo do parceiro.';
                }
            }
            if (empty($errors)) {
                $db->prepare("INSERT INTO about_partners (name, logo_url, website_url, sort_order, active) VALUES (?,?,?,?,1)")
                   ->execute([$name, $logoUrl, $website, $sortOrder]);
                $success = 'Parceiro adicionado com sucesso.';
            }
        }
    }

    // Delete partner
    if ($action === 'delete_partner') {
        $partnerId = (int)($_POST['partner_id'] ?? 0);
        if ($partnerId) {
            $db->prepare("DELETE FROM about_partners WHERE id = ?")->execute([$partnerId]);
            $success = 'Parceiro removido.';
        }
    }

    // Toggle partner active
    if ($action === 'toggle_partner') {
        $partnerId = (int)($_POST['partner_id'] ?? 0);
        if ($partnerId) {
            $current = $db->prepare("SELECT active FROM about_partners WHERE id = ?");
            $current->execute([$partnerId]);
            $row = $current->fetch();
            if ($row) {
                $db->prepare("UPDATE about_partners SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $partnerId]);
            }
        }
        redirect('/admin/about.php');
    }

    // Toggle image active
    if ($action === 'toggle_image') {
        $imgId = (int)($_POST['image_id'] ?? 0);
        if ($imgId) {
            $current = $db->prepare("SELECT active FROM about_images WHERE id = ?");
            $current->execute([$imgId]);
            $row = $current->fetch();
            if ($row) {
                $db->prepare("UPDATE about_images SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $imgId]);
            }
        }
        redirect('/admin/about.php');
    }
}

$aboutTextPt = get_setting($db, 'about_text_pt');
$aboutTextFr = get_setting($db, 'about_text_fr');
$aboutActive = get_setting($db, 'about_active');

try {
    $aboutImages = $db->query("SELECT * FROM about_images ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $aboutImages = [];
}

try {
    $partners = $db->query("SELECT * FROM about_partners ORDER BY sort_order ASC, id ASC")->fetchAll();
} catch (PDOException $e) {
    $partners = [];
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quem Somos | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Quem Somos</h1>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="toggle_page">
                <button type="submit" class="btn btn-sm <?= $aboutActive !== '0' ? 'btn-success' : 'btn-muted' ?>">
                    Página <?= $aboutActive !== '0' ? 'Ativa' : 'Inativa' ?>
                </button>
            </form>
        </header>

        <?php if ($success): ?>
        <div class="admin-card"><div class="alert alert-success"><?= sanitize($success) ?></div></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
        <div class="admin-card"><div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div></div>
        <?php endif; ?>

        <!-- Presentation Text -->
        <div class="admin-card">
            <h2>Texto de Apresentação</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="save_text">
                <div class="form-group">
                    <label for="about_text_pt">Texto (PT)</label>
                    <textarea id="about_text_pt" name="about_text_pt" rows="8"><?= sanitize($aboutTextPt) ?></textarea>
                    <small>Pode usar HTML para formatação avançada.</small>
                </div>
                <div class="form-group">
                    <label for="about_text_fr">Texto (FR)</label>
                    <textarea id="about_text_fr" name="about_text_fr" rows="8"><?= sanitize($aboutTextFr) ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-gold">Guardar Texto</button>
                </div>
            </form>
        </div>

        <!-- Images -->
        <div class="admin-card">
            <h2>Imagens</h2>
            <?php if (!empty($aboutImages)): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Imagem</th>
                            <th>Legenda (PT)</th>
                            <th>Ord.</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aboutImages as $img): ?>
                        <tr>
                            <td><img src="<?= sanitize($img['image_url']) ?>" alt="" style="width:80px;height:60px;object-fit:cover;border-radius:4px;"></td>
                            <td><?= sanitize($img['caption_pt']) ?></td>
                            <td><?= (int)$img['sort_order'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_image">
                                    <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $img['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $img['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar esta imagem?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="image_id" value="<?= (int)$img['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <h3 style="margin-top:1.5rem;">Adicionar Imagem</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_image">
                <div class="form-grid">
                    <div class="form-group form-full">
                        <label for="about_image">Imagem *</label>
                        <input type="file" id="about_image" name="image" accept="image/*" required>
                    </div>
                    <div class="form-group">
                        <label for="caption_pt">Legenda (PT)</label>
                        <input type="text" id="caption_pt" name="caption_pt" placeholder="Opcional">
                    </div>
                    <div class="form-group">
                        <label for="caption_fr">Legenda (FR)</label>
                        <input type="text" id="caption_fr" name="caption_fr" placeholder="Opcional">
                    </div>
                    <div class="form-group">
                        <label for="img_sort_order">Ordem</label>
                        <input type="number" id="img_sort_order" name="sort_order" value="0" min="0">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-gold">Adicionar Imagem</button>
                </div>
            </form>
        </div>

        <!-- Partners -->
        <div class="admin-card">
            <h2>Parceiros</h2>
            <?php if (!empty($partners)): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Logo</th>
                            <th>Nome</th>
                            <th>Website</th>
                            <th>Ord.</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($partners as $partner): ?>
                        <tr>
                            <td>
                                <?php if ($partner['logo_url']): ?>
                                <img src="<?= sanitize($partner['logo_url']) ?>" alt="" style="width:80px;height:60px;object-fit:contain;border-radius:4px;">
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($partner['name']) ?></td>
                            <td><?= $partner['website_url'] ? '<a href="' . sanitize($partner['website_url']) . '" target="_blank">Link</a>' : '-' ?></td>
                            <td><?= (int)$partner['sort_order'] ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_partner">
                                    <input type="hidden" name="partner_id" value="<?= (int)$partner['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $partner['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $partner['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este parceiro?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete_partner">
                                    <input type="hidden" name="partner_id" value="<?= (int)$partner['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <h3 style="margin-top:1.5rem;">Adicionar Parceiro</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="add_partner">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="partner_name">Nome *</label>
                        <input type="text" id="partner_name" name="partner_name" required>
                    </div>
                    <div class="form-group">
                        <label for="partner_website">Website</label>
                        <input type="url" id="partner_website" name="partner_website" placeholder="https://...">
                    </div>
                    <div class="form-group form-full">
                        <label for="partner_logo">Logo</label>
                        <input type="file" id="partner_logo" name="partner_logo" accept="image/*">
                        <small>Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB.</small>
                    </div>
                    <div class="form-group">
                        <label for="partner_sort_order">Ordem</label>
                        <input type="number" id="partner_sort_order" name="partner_sort_order" value="0" min="0">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-gold">Adicionar Parceiro</button>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
