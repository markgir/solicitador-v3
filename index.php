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

$pageTitle = lang('home.hero_title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1><?= lang('home.hero_title') ?></h1>
        <p><?= lang('home.hero_subtitle') ?></p>
        <a href="/booking.php" class="btn btn-gold"><?= lang('home.hero_cta') ?></a>
    </div>
</section>

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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
