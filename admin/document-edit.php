<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$id     = (int)($_GET['id'] ?? 0);
$doc    = null;
$isEdit = false;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();
    if ($doc) $isEdit = true;
}

$errors   = [];
$formData = [
    'title_pt'       => $doc['title_pt']       ?? '',
    'title_fr'       => $doc['title_fr']       ?? '',
    'description_pt' => $doc['description_pt'] ?? '',
    'description_fr' => $doc['description_fr'] ?? '',
    'sort_order'     => $doc['sort_order']     ?? 0,
    'active'         => $doc['active']         ?? 1,
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

        // Handle file upload
        $fileUrl = $doc['file_url'] ?? '';
        $originalFilename = $doc['original_filename'] ?? '';
        if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_document($_FILES['document_file']);
            if ($uploaded) {
                $fileUrl = $uploaded['url'];
                $originalFilename = $uploaded['original_filename'];
            } else {
                $errors[] = 'Erro ao carregar ficheiro. Verifique o formato e tamanho (máx. 10MB).';
            }
        }

        if (empty($formData['title_pt']))  $errors[] = 'Título PT é obrigatório.';
        if (empty($formData['title_fr']))  $errors[] = 'Título FR é obrigatório.';
        if (!$isEdit && empty($fileUrl))   $errors[] = 'Ficheiro é obrigatório.';

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->prepare("UPDATE documents SET title_pt=?, title_fr=?, description_pt=?, description_fr=?, file_url=?, original_filename=?, sort_order=?, active=? WHERE id=?")->execute([
                        $formData['title_pt'], $formData['title_fr'],
                        $formData['description_pt'], $formData['description_fr'],
                        $fileUrl, $originalFilename,
                        $formData['sort_order'], $formData['active'], $id
                    ]);
                } else {
                    $db->prepare("INSERT INTO documents (title_pt, title_fr, description_pt, description_fr, file_url, original_filename, sort_order, active) VALUES (?,?,?,?,?,?,?,?)")->execute([
                        $formData['title_pt'], $formData['title_fr'],
                        $formData['description_pt'], $formData['description_fr'],
                        $fileUrl, $originalFilename,
                        $formData['sort_order'], $formData['active']
                    ]);
                }
                redirect('/admin/documents.php');
            } catch (Exception $e) {
                $errors[] = 'Erro ao guardar: ' . $e->getMessage();
            }
        }
    }
}

$pageAction = $isEdit ? 'Editar Documento' : 'Novo Documento';
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
            <a href="/admin/documents.php" class="back-link">&larr; Documentos</a>
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
                        <label for="title_pt">Título (PT) *</label>
                        <input type="text" id="title_pt" name="title_pt" value="<?= sanitize($formData['title_pt']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="title_fr">Título (FR) *</label>
                        <input type="text" id="title_fr" name="title_fr" value="<?= sanitize($formData['title_fr']) ?>" required>
                    </div>
                    <div class="form-group form-full">
                        <label for="description_pt">Descrição (PT)</label>
                        <textarea id="description_pt" name="description_pt" rows="3" placeholder="Breve descrição do documento (opcional)"><?= sanitize($formData['description_pt']) ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label for="description_fr">Descrição (FR)</label>
                        <textarea id="description_fr" name="description_fr" rows="3" placeholder="Brève description du document (optionnel)"><?= sanitize($formData['description_fr']) ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label for="document_file">Ficheiro <?= $isEdit ? '' : '*' ?></label>
                        <?php if ($isEdit && !empty($doc['file_url'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <p>Ficheiro atual: <a href="<?= sanitize($doc['file_url']) ?>" target="_blank"><?= sanitize($doc['original_filename']) ?></a></p>
                            <small>Carregue um novo ficheiro para substituir o atual.</small>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="document_file" name="document_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" <?= $isEdit ? '' : 'required' ?>>
                        <small>Formatos: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, WEBP. Máx: 10MB.</small>
                    </div>
                    <div class="form-group">
                        <label for="sort_order">Ordem</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?= (int)$formData['sort_order'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="active" value="1" <?= $formData['active'] ? 'checked' : '' ?>>
                            Ativo (visível no website)
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-gold btn-large">Guardar</button>
                    <a href="/admin/documents.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
</div>
</body>
</html>
