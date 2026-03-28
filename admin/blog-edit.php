<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$id     = (int)($_GET['id'] ?? 0);
$post   = null;
$isEdit = false;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if ($post) $isEdit = true;
}

$errors   = [];
$formData = [
    'slug'        => $post['slug']        ?? '',
    'title_pt'    => $post['title_pt']    ?? '',
    'title_fr'    => $post['title_fr']    ?? '',
    'excerpt_pt'  => $post['excerpt_pt']  ?? '',
    'excerpt_fr'  => $post['excerpt_fr']  ?? '',
    'content_pt'  => $post['content_pt']  ?? '',
    'content_fr'  => $post['content_fr']  ?? '',
    'published'   => $post['published']   ?? 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token inválido.';
    } else {
        $rawSlug = strtolower(trim($_POST['slug'] ?? ''));
        $formData['slug']       = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]+/', '-', $rawSlug)), '-');
        $formData['title_pt']   = trim($_POST['title_pt']   ?? '');
        $formData['title_fr']   = trim($_POST['title_fr']   ?? '');
        $formData['excerpt_pt'] = trim($_POST['excerpt_pt'] ?? '');
        $formData['excerpt_fr'] = trim($_POST['excerpt_fr'] ?? '');
        $formData['content_pt'] = trim($_POST['content_pt'] ?? '');
        $formData['content_fr'] = trim($_POST['content_fr'] ?? '');
        $formData['published']  = isset($_POST['published']) ? 1 : 0;

        // Handle image upload
        $imageUrl = $post['image_url'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['image'], 'blog');
            if ($uploaded) {
                $imageUrl = $uploaded;
            } else {
                $errors[] = 'Erro ao carregar imagem.';
            }
        }

        // Handle image removal
        if (isset($_POST['remove_image'])) {
            $imageUrl = '';
        }

        if (empty($formData['slug']))       $errors[] = 'Slug é obrigatório.';
        if (empty($formData['title_pt']))   $errors[] = 'Título PT é obrigatório.';
        if (empty($formData['title_fr']))   $errors[] = 'Título FR é obrigatório.';
        if (empty($formData['content_pt'])) $errors[] = 'Conteúdo PT é obrigatório.';
        if (empty($formData['content_fr'])) $errors[] = 'Conteúdo FR é obrigatório.';

        if (empty($errors)) {
            try {
                if ($isEdit) {
                    $db->prepare("UPDATE blog_posts SET slug=?, title_pt=?, title_fr=?, excerpt_pt=?, excerpt_fr=?, content_pt=?, content_fr=?, image_url=?, published=? WHERE id=?")->execute([
                        $formData['slug'], $formData['title_pt'], $formData['title_fr'],
                        $formData['excerpt_pt'], $formData['excerpt_fr'],
                        $formData['content_pt'], $formData['content_fr'],
                        $imageUrl, $formData['published'], $id
                    ]);
                } else {
                    $db->prepare("INSERT INTO blog_posts (slug, title_pt, title_fr, excerpt_pt, excerpt_fr, content_pt, content_fr, image_url, published) VALUES (?,?,?,?,?,?,?,?,?)")->execute([
                        $formData['slug'], $formData['title_pt'], $formData['title_fr'],
                        $formData['excerpt_pt'], $formData['excerpt_fr'],
                        $formData['content_pt'], $formData['content_fr'],
                        $imageUrl, $formData['published']
                    ]);
                }
                redirect('/admin/blog.php');
            } catch (Exception $e) {
                $errors[] = 'Erro ao guardar: ' . $e->getMessage();
            }
        }
    }
}

$pageAction = $isEdit ? 'Editar Artigo' : 'Novo Artigo';
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
    <!-- Replace 'no-api-key' with your TinyMCE API key from https://www.tiny.cloud/ for production use -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <a href="/admin/blog.php" class="back-link">&larr; Blog</a>
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
                        <label for="slug">Slug *</label>
                        <input type="text" id="slug" name="slug" value="<?= sanitize($formData['slug']) ?>" required pattern="[a-z0-9-]+" placeholder="meu-artigo">
                        <small>Apenas letras minúsculas, números e hífens.</small>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="published" value="1" <?= $formData['published'] ? 'checked' : '' ?>>
                            Publicado
                        </label>
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
                        <label for="image">Imagem de Destaque</label>
                        <?php if ($isEdit && !empty($post['image_url'])): ?>
                        <div style="margin-bottom: 0.75rem;">
                            <img src="<?= sanitize($post['image_url']) ?>" alt="" style="max-width:300px;border-radius:6px;">
                            <div style="margin-top: 0.5rem;">
                                <button type="submit" name="remove_image" value="1" class="btn btn-sm btn-danger">Remover Imagem</button>
                            </div>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*">
                        <small>Formatos: JPG, PNG, GIF, WEBP. Máx: 5MB.</small>
                    </div>
                    <div class="form-group form-full">
                        <label for="excerpt_pt">Resumo (PT)</label>
                        <textarea id="excerpt_pt" name="excerpt_pt" rows="3" placeholder="Breve resumo do artigo (opcional)"><?= sanitize($formData['excerpt_pt']) ?></textarea>
                        <small>Se não preenchido, será gerado automaticamente a partir do conteúdo.</small>
                    </div>
                    <div class="form-group form-full">
                        <label for="excerpt_fr">Resumo (FR)</label>
                        <textarea id="excerpt_fr" name="excerpt_fr" rows="3" placeholder="Bref résumé de l'article (optionnel)"><?= sanitize($formData['excerpt_fr']) ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label for="content_pt">Conteúdo (PT) *</label>
                        <textarea id="content_pt" name="content_pt" rows="10" required><?= sanitize($formData['content_pt']) ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label for="content_fr">Conteúdo (FR) *</label>
                        <textarea id="content_fr" name="content_fr" rows="10" required><?= sanitize($formData['content_fr']) ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-gold btn-large">Guardar</button>
                    <a href="/admin/blog.php" class="btn btn-outline">Cancelar</a>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
tinymce.init({
    selector: '#content_pt, #content_fr',
    height: 400,
    language: 'pt_PT',
    plugins: 'link image media table lists code wordcount fullscreen',
    toolbar: 'undo redo | styles | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | table | code fullscreen',
    menubar: 'file edit view insert format tools table',
    branding: false,
    promotion: false,
    relative_urls: false,
    remove_script_host: true
});
</script>
</body>
</html>
