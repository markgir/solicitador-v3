<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    redirect('/blog.php');
}

$lang = get_language();
$db = get_db();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$totalPosts = (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE published = 1")->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / $perPage));

$stmt = $db->prepare("SELECT * FROM blog_posts WHERE published = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$pageTitle = lang('blog.title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1><?= lang('blog.title') ?></h1>
    </div>
</section>

<section class="blog-section">
    <div class="container">
        <?php if (empty($posts)): ?>
            <p class="text-center"><?= lang('blog.no_posts') ?></p>
        <?php else: ?>
        <div class="blog-grid">
            <?php foreach ($posts as $post):
                $postTitle = $lang === 'fr' ? $post['title_fr'] : $post['title_pt'];
                $postExcerpt = $lang === 'fr' ? $post['excerpt_fr'] : $post['excerpt_pt'];
                if (empty($postExcerpt)) {
                    $content = $lang === 'fr' ? $post['content_fr'] : $post['content_pt'];
                    $postExcerpt = mb_substr(strip_tags($content), 0, 150) . '...';
                }
            ?>
            <article class="blog-card">
                <?php if ($post['image_url']): ?>
                <div class="blog-card-image">
                    <a href="/blog-post.php?slug=<?= sanitize($post['slug']) ?>">
                        <img src="<?= sanitize($post['image_url']) ?>" alt="<?= sanitize($postTitle) ?>">
                    </a>
                </div>
                <?php endif; ?>
                <div class="blog-card-content">
                    <p class="blog-card-date"><?= format_date($post['created_at'], $lang) ?></p>
                    <h2><a href="/blog-post.php?slug=<?= sanitize($post['slug']) ?>"><?= sanitize($postTitle) ?></a></h2>
                    <p><?= sanitize($postExcerpt) ?></p>
                    <a href="/blog-post.php?slug=<?= sanitize($post['slug']) ?>" class="btn btn-outline btn-sm"><?= lang('blog.read_more') ?> &rarr;</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination" style="justify-content: center; padding-top: 2rem;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="/blog.php?page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
