<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($action === 'toggle_published' && $postId) {
        $current = $db->prepare("SELECT published FROM blog_posts WHERE id = ?");
        $current->execute([$postId]);
        $row = $current->fetch();
        if ($row) {
            $db->prepare("UPDATE blog_posts SET published = ? WHERE id = ?")->execute([$row['published'] ? 0 : 1, $postId]);
        }
    } elseif ($action === 'delete' && $postId) {
        $db->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$postId]);
    }
    redirect('/admin/blog.php');
}

$posts = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Blog</h1>
            <a href="/admin/blog-edit.php" class="btn btn-gold">+ Novo Artigo</a>
        </header>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Imagem</th>
                            <th>Título (PT)</th>
                            <th>Slug</th>
                            <th>Data</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><?= (int)$post['id'] ?></td>
                            <td>
                                <?php if ($post['image_url']): ?>
                                <img src="<?= sanitize($post['image_url']) ?>" alt="" style="width:60px;height:40px;object-fit:cover;border-radius:4px;">
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                            <td><?= sanitize($post['title_pt']) ?></td>
                            <td><code><?= sanitize($post['slug']) ?></code></td>
                            <td><?= sanitize(substr($post['created_at'], 0, 10)) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_published">
                                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $post['published'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $post['published'] ? 'Publicado' : 'Rascunho' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="/admin/blog-edit.php?id=<?= (int)$post['id'] ?>" class="btn btn-sm">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este artigo?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($posts)): ?>
                        <tr><td colspan="7" class="text-center">Sem artigos. Crie o primeiro artigo do blog.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
