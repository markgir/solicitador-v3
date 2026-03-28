<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    redirect('/service.php' . (isset($_GET['slug']) ? '?slug=' . urlencode($_GET['slug']) : ''));
}

$lang = get_language();
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($slug)) {
    redirect('/index.php');
}

$db = get_db();
$stmt = $db->prepare("SELECT * FROM services WHERE slug = ? AND active = 1");
$stmt->execute([$slug]);
$service = $stmt->fetch();

if (!$service) {
    http_response_code(404);
    $pageTitle = '404 | Solicitador';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding: 80px 0; text-align:center;"><h1>404</h1><p>Serviço não encontrado.</p><a href="/index.php" class="btn btn-primary">Início</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$name = $lang === 'fr' ? $service['title_fr'] : $service['title_pt'];
$desc = $lang === 'fr' ? $service['description_fr'] : $service['description_pt'];

$pageTitle = sanitize($name) . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1><?= sanitize($name) ?></h1>
    </div>
</section>

<section class="service-detail">
    <div class="container">
        <a href="/index.php#services" class="back-link">&larr; <?= lang('service.back') ?></a>
        <div class="service-content">
            <div class="service-desc">
                <?= nl2br(sanitize($desc)) ?>
            </div>
            <div class="service-cta-box">
                <h3><?= lang('service.consultation_request') ?></h3>
                <a href="/booking.php?service=<?= sanitize($service['slug']) ?>" class="btn btn-gold btn-large"><?= lang('service.book_now') ?></a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
