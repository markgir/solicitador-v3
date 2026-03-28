<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$lang = get_language();
$db = get_db();

// Check if about page is enabled
$aboutActive = get_setting($db, 'about_active');
if ($aboutActive === '0') {
    redirect('/');
}

$aboutTextPt = get_setting($db, 'about_text_pt');
$aboutTextFr = get_setting($db, 'about_text_fr');
$aboutText = $lang === 'fr' ? $aboutTextFr : $aboutTextPt;

// Fetch about images
try {
    $imgStmt = $db->query("SELECT * FROM about_images WHERE active = 1 ORDER BY sort_order ASC");
    $aboutImages = $imgStmt->fetchAll();
} catch (PDOException $e) {
    $aboutImages = [];
}

// Fetch partners
try {
    $partnerStmt = $db->query("SELECT * FROM about_partners WHERE active = 1 ORDER BY sort_order ASC");
    $partners = $partnerStmt->fetchAll();
} catch (PDOException $e) {
    $partners = [];
}

$pageTitle = lang('about.title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="about-section">
    <div class="container">
        <h2 class="section-title"><?= lang('about.title') ?></h2>

        <?php if (!empty($aboutText)): ?>
        <div class="about-text">
            <?= render_content($aboutText) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($aboutImages)): ?>
        <div class="about-images-section">
            <h3 class="about-subtitle"><?= lang('about.images_title') ?></h3>
            <div class="about-images-grid">
                <?php foreach ($aboutImages as $img):
                    $caption = $lang === 'fr' ? ($img['caption_fr'] ?: $img['caption_pt']) : $img['caption_pt'];
                ?>
                <div class="about-image-item">
                    <img src="<?= sanitize($img['image_url']) ?>" alt="<?= sanitize($caption) ?>" loading="lazy">
                    <?php if (!empty($caption)): ?>
                    <p class="about-image-caption"><?= sanitize($caption) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($partners)): ?>
        <div class="about-partners-section">
            <h3 class="about-subtitle"><?= lang('about.partners_title') ?></h3>
            <div class="about-partners-grid">
                <?php foreach ($partners as $partner): ?>
                <div class="about-partner-card">
                    <?php if (!empty($partner['logo_url'])): ?>
                    <?php if (!empty($partner['website_url'])): ?>
                    <a href="<?= sanitize($partner['website_url']) ?>" target="_blank" rel="noopener noreferrer">
                        <img src="<?= sanitize($partner['logo_url']) ?>" alt="<?= sanitize($partner['name']) ?>" class="partner-logo">
                    </a>
                    <?php else: ?>
                    <img src="<?= sanitize($partner['logo_url']) ?>" alt="<?= sanitize($partner['name']) ?>" class="partner-logo">
                    <?php endif; ?>
                    <?php endif; ?>
                    <p class="partner-name"><?= sanitize($partner['name']) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
