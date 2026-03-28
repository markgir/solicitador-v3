<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

$lang = get_language();
$db = get_db();

// Check if portfolio is enabled
$portfolioActive = get_setting($db, 'portfolio_active');
if ($portfolioActive === '0') {
    redirect('/');
}

try {
    $stmt = $db->query("SELECT * FROM portfolio_items WHERE active = 1 ORDER BY sort_order ASC, created_at DESC");
    $portfolioItems = $stmt->fetchAll();
} catch (PDOException $e) {
    $portfolioItems = [];
}

$pageTitle = lang('portfolio.title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="portfolio-section" id="portfolio">
    <div class="container">
        <h2 class="section-title"><?= lang('portfolio.title') ?></h2>
        <?php if (!empty($portfolioItems)): ?>
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
                    <p><?= sanitize(strip_tags($pDesc)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center" style="margin-top:2rem;color:var(--text-muted);"><?= lang('portfolio.no_items') ?></p>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
