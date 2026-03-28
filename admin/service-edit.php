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
    'name_pt'    => $service['name_pt']    ?? '',
    'name_fr'    => $service['name_fr']    ?? '',
    'desc_pt'    => $service['desc_pt']    ?? '',
    'desc_fr'    => $service['desc_fr']    ?? '',
    'sort_order' => $service['sort_order'] ?? 0,
    'active'     => $service['active']     ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $rawSlug = strtolower(trim($_POST['slug'] ?? ''));
        $formData['slug']       = preg_replace('/[^a-z0-9-]/', '-', $rawSlug);
        $formData['name_pt']    = trim($_POST['name_pt']    ?? '');
        $formData['name_fr']    = trim($_POST['name_fr']    ?? '');
        $formData['desc_pt']    = trim($_POST['desc_pt']    ?? '');
        $formData['desc_fr']    = trim($_POST['desc_fr']    ?? '');
        $formData['sort_order'] = (int)($_POST['sort_order'] ?? 0);
        $formData['active']     = isset($_POST['active']) ? 1 : 0;

        if (empty($formData['slug']))   $errors[] = 'Slug é obrigatório.';
        if (empty($formData['name_pt'])) $errors[] = 'Nome PT é obrigatório.';
        if (empty($formData['name_fr'])) $errors[] = 'Nome FR é obrigatório.';
        if (empty($formData['desc_pt'])) $errors[] = 'Descrição PT é obrigatória.';
        if (empty($formData['desc_fr'])) $errors[] = 'Descrição FR é obrigatória.';

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->prepare("UPDATE services SET slug=?, name_pt=?, name_fr=?, desc_pt=?, desc_fr=?, sort_order=?, active=? WHERE id=?")->execute([
                        $formData['slug'], $formData['name_pt'], $formData['name_fr'],
                        $formData['desc_pt'], $formData['desc_fr'],
                        $formData['sort_order'], $formData['active'], $id
                    ]);
                } else {
                    $db->prepare("INSERT INTO services (slug, name_pt, name_fr, desc_pt, desc_fr, sort_order, active) VALUES (?,?,?,?,?,?,?)")->execute([
                        $formData['slug'], $formData['name_pt'], $formData['name_fr'],
                        $formData['desc_pt'], $formData['desc_fr'],
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
    <aside class="admin-sidebar">
        <div class="sidebar-logo">Solicitador</div>
        <nav class="sidebar-nav">
            <a href="/admin/index.php" class="sidebar-link">&#x1F4CA; Painel</a>
            <a href="/admin/appointments.php" class="sidebar-link">&#x1F4C5; Consultas</a>
            <a href="/admin/services.php" class="sidebar-link active">&#x1F4CB; Serviços</a>
        </nav>
        <div class="sidebar-footer">
            <span><?= sanitize($_SESSION['admin_username'] ?? '') ?></span>
            <a href="/admin/logout.php" class="sidebar-link">&#x1F6AA; Sair</a>
        </div>
    </aside>
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
                        <label for="name_pt">Nome (PT) *</label>
                        <input type="text" id="name_pt" name="name_pt" value="<?= sanitize($formData['name_pt']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="name_fr">Nome (FR) *</label>
                        <input type="text" id="name_fr" name="name_fr" value="<?= sanitize($formData['name_fr']) ?>" required>
                    </div>
                    <div class="form-group form-full">
                        <label for="desc_pt">Descrição (PT) *</label>
                        <textarea id="desc_pt" name="desc_pt" rows="6" required><?= sanitize($formData['desc_pt']) ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label for="desc_fr">Descrição (FR) *</label>
                        <textarea id="desc_fr" name="desc_fr" rows="6" required><?= sanitize($formData['desc_fr']) ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label>
                            <input type="checkbox" name="active" value="1" <?= $formData['active'] ? 'checked' : '' ?>>
                            Activo
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
