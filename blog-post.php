<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    $slug = $_GET['slug'] ?? '';
    redirect('/blog-post.php?slug=' . urlencode($slug));
}

$lang = get_language();
$db = get_db();

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    redirect('/blog.php');
}

$stmt = $db->prepare("SELECT * FROM blog_posts WHERE slug = ? AND published = 1");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    $pageTitle = '404 | Solicitador';
    require_once __DIR__ . '/includes/header.php';
    echo '<section class="page-hero"><div class="container"><h1>404</h1></div></section>';
    echo '<section class="blog-detail"><div class="container"><p class="text-center">Artigo não encontrado.</p></div></section>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$postTitle = $lang === 'fr' ? $post['title_fr'] : $post['title_pt'];
$postContent = $lang === 'fr' ? $post['content_fr'] : $post['content_pt'];

$pageTitle = sanitize($postTitle) . ' | Blog | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1><?= sanitize($postTitle) ?></h1>
        <p style="color: rgba(255,255,255,0.7); margin-top: 0.5rem;"><?= lang('blog.published_on') ?> <?= format_date($post['created_at'], $lang) ?></p>
    </div>
</section>

<section class="blog-detail">
    <div class="container">
        <a href="/blog.php" class="back-link">&larr; <?= lang('blog.back') ?></a>

        <?php if ($post['image_url']): ?>
        <div class="blog-detail-image">
            <img src="<?= sanitize($post['image_url']) ?>" alt="<?= sanitize($postTitle) ?>">
        </div>
        <?php endif; ?>

        <div class="blog-detail-content">
            <?= render_content($postContent) ?>
        </div>

        <div style="margin-top: 3rem;">
            <a href="/blog.php" class="btn btn-outline">&larr; <?= lang('blog.back') ?></a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
