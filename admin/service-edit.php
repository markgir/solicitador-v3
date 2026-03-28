<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$id      = (int)($_GET['id'] ?? 0);
$service = null;
$isEdit  = false;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$id]);
    $service = $stmt->fetch();
    if ($service) $isEdit = true;
}

$errors   = [];
$formData = [
    'slug'       => $service['slug']       ?? '',
    'title_pt'    => $service['title_pt']    ?? '',
    'title_fr'    => $service['title_fr']    ?? '',
    'description_pt' => $service['description_pt']    ?? '',
    'description_fr' => $service['description_fr']    ?? '',
    'sort_order' => $service['sort_order'] ?? 0,
    'active'     => $service['active']     ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $rawSlug = strtolower(trim($_POST['slug'] ?? ''));
        $formData['slug']            = trim(preg_replace('/[^a-z0-9]+/', '-', $rawSlug), '-');
        $formData['title_pt']        = trim($_POST['title_pt']        ?? '');
        $formData['title_fr']        = trim($_POST['title_fr']        ?? '');
        $formData['description_pt']  = trim($_POST['description_pt']  ?? '');
        $formData['description_fr']  = trim($_POST['description_fr']  ?? '');
        $formData['sort_order'] = (int)($_POST['sort_order'] ?? 0);
        $formData['active']     = isset($_POST['active']) ? 1 : 0;

        if (empty($formData['slug']))            $errors[] = 'Slug é obrigatório.';
        if (empty($formData['title_pt']))        $errors[] = 'Nome PT é obrigatório.';
        if (empty($formData['title_fr']))        $errors[] = 'Nome FR é obrigatório.';
        if (empty($formData['description_pt'])) $errors[] = 'Descrição PT é obrigatória.';
        if (empty($formData['description_fr'])) $errors[] = 'Descrição FR é obrigatória.';

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->prepare("UPDATE services SET slug=?, title_pt=?, title_fr=?, description_pt=?, description_fr=?, sort_order=?, active=? WHERE id=?")->execute([
                        $formData['slug'], $formData['title_pt'], $formData['title_fr'],
                        $formData['description_pt'], $formData['description_fr'],
                        $formData['sort_order'], $formData['active'], $id
                    ]);
                } else {
                    $db->prepare("INSERT INTO services (slug, title_pt, title_fr, description_pt, description_fr, image_url, sort_order, active) VALUES (?,?,?,?,?,'',?,?)")->execute([
                        $formData['slug'], $formData['title_pt'], $formData['title_fr'],
                        $formData['description_pt'], $formData['description_fr'],
                        $formData['sort_order'], $formData['active']
                    ]);
                }
                redirect('/admin/services.php');
            } catch (Exception $e) {
                $errors[] = 'Erro ao guardar: ' . $e->getMessage();
            }
        }
    }
}

$pageAction = $isEdit ? 'Editar Serviço' : 'Novo Serviço';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageAction) ?> | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <a href="/admin/services.php" class="back-link">&larr; Serviços</a>
            <h1><?= sanitize($pageAction) ?></h1>
        </header>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="slug">Slug *</label>
                        <input type="text" id="slug" name="slug" value="<?= sanitize($formData['slug']) ?>" required pattern="[a-z0-9-]+" placeholder="meu-servico">
                        <small>Apenas letras minúsculas, números e hífens.</small>
                    </div>
                    <div class="form-group">
                        <label for="sort_order">Ordem</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?= (int)$formData['sort_order'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label for="title_pt">Título (PT) *</label>
                        <input type="text" id="title_pt" name="title_pt" value="<?= sanitize($formData['title_pt']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="title_fr">Título (FR) *</label>
                        <input type="text" id="title_fr" name="title_fr" value="<?= sanitize($formData['title_fr']) ?>" required>
                    </div>
                    <div class="form-group form-full">
                        <label for="description_pt">Descrição (PT) *</label>
                        <textarea id="description_pt" name="description_pt" rows="6" required><?= sanitize($formData['description_pt']) ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label for="description_fr">Descrição (FR) *</label>
                        <textarea id="description_fr" name="description_fr" rows="6" required><?= sanitize($formData['description_fr']) ?></textarea>
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
                    <a href="/admin/services.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
