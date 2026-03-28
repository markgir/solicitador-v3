<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$id     = (int)($_GET['id'] ?? 0);
$item   = null;
$isEdit = false;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) $isEdit = true;
}

$errors   = [];
$formData = [
    'title_pt'   => $item['title_pt']   ?? '',
    'title_fr'   => $item['title_fr']   ?? '',
    'url'        => $item['url']        ?? '',
    'target'     => $item['target']     ?? '_self',
    'sort_order' => $item['sort_order'] ?? 0,
    'active'     => $item['active']     ?? 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $formData['title_pt']   = trim($_POST['title_pt']   ?? '');
        $formData['title_fr']   = trim($_POST['title_fr']   ?? '');
        $formData['url']        = trim($_POST['url']        ?? '');
        $formData['target']     = ($_POST['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
        $formData['sort_order'] = (int)($_POST['sort_order'] ?? 0);
        $formData['active']     = isset($_POST['active']) ? 1 : 0;

        if (empty($formData['title_pt'])) $errors[] = 'Nome PT é obrigatório.';
        if (empty($formData['title_fr'])) $errors[] = 'Nome FR é obrigatório.';
        if (empty($formData['url']))      $errors[] = 'URL é obrigatório.';

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->prepare("UPDATE menu_items SET title_pt=?, title_fr=?, url=?, target=?, sort_order=?, active=? WHERE id=?")->execute([
                        $formData['title_pt'], $formData['title_fr'], $formData['url'],
                        $formData['target'], $formData['sort_order'], $formData['active'], $id
                    ]);
                } else {
                    $db->prepare("INSERT INTO menu_items (title_pt, title_fr, url, target, sort_order, active) VALUES (?,?,?,?,?,?)")->execute([
                        $formData['title_pt'], $formData['title_fr'], $formData['url'],
                        $formData['target'], $formData['sort_order'], $formData['active']
                    ]);
                }
                redirect('/admin/menu.php');
            } catch (Exception $e) {
                $errors[] = 'Erro ao guardar: ' . $e->getMessage();
            }
        }
    }
}

$pageAction = $isEdit ? 'Editar Item do Menu' : 'Novo Item do Menu';
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
            <a href="/admin/menu.php" class="back-link">&larr; Menu</a>
            <h1><?= sanitize($pageAction) ?></h1>
        </header>

        <?php if (!empty($errors)): ?>
        <div class="admin-card"><div class="alert alert-error"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div></div>
        <?php endif; ?>

        <div class="admin-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="title_pt">Nome (PT) *</label>
                        <input type="text" id="title_pt" name="title_pt" value="<?= sanitize($formData['title_pt']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="title_fr">Nome (FR) *</label>
                        <input type="text" id="title_fr" name="title_fr" value="<?= sanitize($formData['title_fr']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="url">URL *</label>
                        <input type="text" id="url" name="url" value="<?= sanitize($formData['url']) ?>" required placeholder="/pagina ou https://exemplo.com">
                        <small>Use URLs internas (ex: /index.php#services) ou externas (ex: https://google.com)</small>
                    </div>
                    <div class="form-group">
                        <label for="target">Abrir em</label>
                        <select id="target" name="target">
                            <option value="_self" <?= $formData['target'] === '_self' ? 'selected' : '' ?>>Mesma janela</option>
                            <option value="_blank" <?= $formData['target'] === '_blank' ? 'selected' : '' ?>>Nova janela (link externo)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="sort_order">Ordem</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?= (int)$formData['sort_order'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="active" value="1" <?= $formData['active'] ? 'checked' : '' ?>>
                            Ativo
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-gold btn-large">Guardar</button>
                    <a href="/admin/menu.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
