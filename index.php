<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    redirect('/');
}

$lang = get_language();
$db = get_db();
$stmt = $db->query("SELECT * FROM services WHERE active = 1 ORDER BY sort_order ASC");
$services = $stmt->fetchAll();

// Fetch active banners
$bannerStmt = $db->query("SELECT * FROM banners WHERE active = 1 ORDER BY sort_order ASC");
$banners = $bannerStmt->fetchAll();

// Fetch parallax image
$parallaxImage = get_setting($db, 'parallax_image');
$siteLogo = get_setting($db, 'site_logo');

// Fetch latest 4 published blog posts
$blogStmt = $db->query("SELECT * FROM blog_posts WHERE published = 1 ORDER BY created_at DESC LIMIT 4");
$latestPosts = $blogStmt->fetchAll();

// Fetch active documents for download
try {
    $docStmt = $db->query("SELECT * FROM documents WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
    $activeDocuments = $docStmt->fetchAll();
} catch (PDOException $e) {
    $activeDocuments = [];
}

// Fetch active portfolio items
try {
    $portfolioStmt = $db->query("SELECT * FROM portfolio_items WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
    $portfolioItems = $portfolioStmt->fetchAll();
} catch (PDOException $e) {
    $portfolioItems = [];
}

// Fetch active gallery images
try {
    $galleryStmt = $db->query("SELECT * FROM gallery_images WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
    $galleryImages = $galleryStmt->fetchAll();
} catch (PDOException $e) {
    $galleryImages = [];
}

// Fetch homepage section order and visibility
try {
    $sectionStmt = $db->query("SELECT * FROM homepage_sections ORDER BY sort_order ASC");
    $homepageSections = $sectionStmt->fetchAll();
} catch (PDOException $e) {
    $homepageSections = [];
}

// If no sections configured, use default order
if (empty($homepageSections)) {
    $homepageSections = [
        ['section_key' => 'banners',   'active' => 1],
        ['section_key' => 'services',  'active' => 1],
        ['section_key' => 'parallax',  'active' => 1],
        ['section_key' => 'portfolio', 'active' => 1],
        ['section_key' => 'gallery',   'active' => 1],
        ['section_key' => 'documents', 'active' => 1],
        ['section_key' => 'blog',      'active' => 1],
    ];
}

$pageTitle = lang('home.hero_title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';

foreach ($homepageSections as $section):
    if (!$section['active']) continue;
    $key = $section['section_key'];

    if ($key === 'banners'):
?>

<?php if (!empty($banners)): ?>
<section class="banner-section banner-hero">
    <div class="banner-slider" id="bannerSlider">
        <?php foreach ($banners as $i => $banner):
            $bannerTitle = $lang === 'fr' ? $banner['title_fr'] : $banner['title_pt'];
        ?>
        <div class="banner-slide <?= $i === 0 ? 'active' : '' ?>">
            <img src="<?= sanitize($banner['image_url']) ?>" alt="<?= sanitize($bannerTitle) ?>">
            <?php if ($banner['link']): ?>
            <a href="<?= sanitize($banner['link']) ?>" class="banner-link-overlay"></a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <div class="banner-hero-overlay">
            <div class="container">
                <h1><?= lang('home.hero_title') ?></h1>
                <p><?= lang('home.hero_subtitle') ?></p>
                <a href="/booking.php" class="btn btn-gold btn-large"><?= lang('home.hero_cta') ?></a>
            </div>
        </div>
        <?php if (count($banners) > 1): ?>
        <button class="banner-nav banner-prev" id="bannerPrev" aria-label="Previous">&#10094;</button>
        <button class="banner-nav banner-next" id="bannerNext" aria-label="Next">&#10095;</button>
        <div class="banner-dots" id="bannerDots">
            <?php foreach ($banners as $i => $b): ?>
            <span class="banner-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<section class="hero">
    <div class="container">
        <h1><?= lang('home.hero_title') ?></h1>
        <p><?= lang('home.hero_subtitle') ?></p>
        <a href="/booking.php" class="btn btn-gold btn-large"><?= lang('home.hero_cta') ?></a>
    </div>
</section>
<?php endif; ?>

<?php
    elseif ($key === 'services'):
?>

<section class="services-section" id="services">
    <div class="container">
        <h2 class="section-title"><?= lang('home.services_title') ?></h2>
        <div class="services-grid">
            <?php foreach ($services as $service):
                $name = $lang === 'fr' ? $service['title_fr'] : $service['title_pt'];
                $desc = $lang === 'fr' ? $service['description_fr'] : $service['description_pt'];
                $excerpt = mb_substr(strip_tags($desc), 0, 80) . '...';
                $verMais = $lang === 'fr' ? 'Voir plus' : 'Ver mais';
            ?>
            <div class="service-card">
                <div class="service-card-inner">
                    <h3><?= sanitize($name) ?></h3>
                    <p><?= sanitize($excerpt) ?></p>
                    <a href="/service.php?slug=<?= sanitize($service['slug']) ?>" class="btn btn-outline"><?= $verMais ?></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
    elseif ($key === 'parallax'):
        if ($parallaxImage):
?>

<section class="parallax-section" style="background-image: url('<?= sanitize($parallaxImage) ?>');">
    <div class="parallax-overlay">
        <div class="container parallax-content">
            <?php if ($siteLogo): ?>
                <img src="<?= sanitize($siteLogo) ?>" alt="Solicitador" class="parallax-logo">
            <?php else: ?>
                <h2 class="parallax-title">Solicitador</h2>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
        endif;
    elseif ($key === 'portfolio'):
        if (!empty($portfolioItems)):
?>

<section class="portfolio-section" id="portfolio">
    <div class="container">
        <h2 class="section-title"><?= lang('portfolio.title') ?></h2>
        <div class="portfolio-grid">
            <?php foreach ($portfolioItems as $pItem):
                $pTitle = $lang === 'fr' ? $pItem['title_fr'] : $pItem['title_pt'];
                $pDesc  = $lang === 'fr' ? $pItem['description_fr'] : $pItem['description_pt'];
            ?>
            <div class="portfolio-card">
                <div class="portfolio-card-image">
                    <img src="<?= sanitize($pItem['image_url']) ?>" alt="<?= sanitize($pTitle) ?>">
                </div>
                <div class="portfolio-card-content">
                    <h3><?= sanitize($pTitle) ?></h3>
                    <?php if (!empty($pDesc)): ?>
                    <p><?= sanitize(mb_substr(strip_tags($pDesc), 0, 120)) ?><?= mb_strlen(strip_tags($pDesc)) > 120 ? '...' : '' ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
        endif;
    elseif ($key === 'gallery'):
        if (!empty($galleryImages)):
?>

<section class="gallery-section" id="gallery">
    <div class="container">
        <h2 class="section-title"><?= lang('gallery.title') ?></h2>
        <div class="gallery-grid">
            <?php foreach ($galleryImages as $gImage):
                $gTitle = $lang === 'fr' ? $gImage['title_fr'] : $gImage['title_pt'];
                $gDesc  = $lang === 'fr' ? $gImage['description_fr'] : $gImage['description_pt'];
            ?>
            <div class="gallery-item">
                <img src="<?= sanitize($gImage['image_url']) ?>" alt="<?= sanitize($gTitle) ?>">
                <?php if (!empty($gTitle)): ?>
                <div class="gallery-item-caption">
                    <strong><?= sanitize($gTitle) ?></strong>
                    <?php if (!empty($gDesc)): ?>
                    <span><?= sanitize(mb_substr(strip_tags($gDesc), 0, 80)) ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
        endif;
    elseif ($key === 'documents'):
        if (!empty($activeDocuments)):
?>

<section class="documents-section">
    <div class="container">
        <h2 class="section-title"><?= lang('documents.title') ?></h2>
        <div class="documents-grid">
            <?php foreach ($activeDocuments as $document):
                $docTitle = $lang === 'fr' ? $document['title_fr'] : $document['title_pt'];
                $docDesc = $lang === 'fr' ? $document['description_fr'] : $document['description_pt'];
            ?>
            <div class="document-card">
                <div class="document-card-icon">&#x1F4C4;</div>
                <div class="document-card-content">
                    <h3><?= sanitize($docTitle) ?></h3>
                    <?php if (!empty($docDesc)): ?>
                    <p><?= sanitize($docDesc) ?></p>
                    <?php endif; ?>
                </div>
                <a href="<?= sanitize($document['file_url']) ?>" class="btn btn-outline btn-sm" download="<?= sanitize($document['original_filename']) ?>"><?= lang('documents.download') ?> &darr;</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
        endif;
    elseif ($key === 'blog'):
        if (!empty($latestPosts)):
?>

<section class="blog-preview-section">
    <div class="container">
        <h2 class="section-title"><?= lang('blog.latest_news') ?></h2>
        <div class="blog-preview-grid">
            <?php foreach ($latestPosts as $post):
                $postTitle = $lang === 'fr' ? $post['title_fr'] : $post['title_pt'];
                $postExcerpt = $lang === 'fr' ? $post['excerpt_fr'] : $post['excerpt_pt'];
                if (empty($postExcerpt)) {
                    $content = $lang === 'fr' ? $post['content_fr'] : $post['content_pt'];
                    $postExcerpt = mb_substr(strip_tags($content), 0, 150) . '...';
                }
            ?>
            <div class="blog-preview-card">
                <?php if ($post['image_url']): ?>
                <div class="blog-preview-image">
                    <a href="/blog-post.php?slug=<?= sanitize($post['slug']) ?>">
                        <img src="<?= sanitize($post['image_url']) ?>" alt="<?= sanitize($postTitle) ?>">
                    </a>
                </div>
                <?php endif; ?>
                <div class="blog-preview-content">
                    <h3><a href="/blog-post.php?slug=<?= sanitize($post['slug']) ?>"><?= sanitize($postTitle) ?></a></h3>
                    <p class="blog-preview-date"><?= format_date($post['created_at'], $lang) ?></p>
                    <p class="blog-preview-excerpt"><?= sanitize($postExcerpt) ?></p>
                    <a href="/blog-post.php?slug=<?= sanitize($post['slug']) ?>" class="btn btn-outline btn-sm"><?= lang('blog.read_more') ?> &rarr;</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
        endif;
    endif;
endforeach;
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
