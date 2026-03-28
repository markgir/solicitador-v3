<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    redirect($redirect ?: '/');
}
$pageTitle = $pageTitle ?? 'Solicitador';
$currentLang = get_language();

$headerDb = get_db();
try {
    $siteLogo = get_setting($headerDb, 'site_logo');
} catch (PDOException $e) {
    $siteLogo = '';
}
try {
    $menuStmt = $headerDb->query("SELECT * FROM menu_items WHERE active = 1 ORDER BY sort_order ASC");
    $menuItems = $menuStmt->fetchAll();
} catch (PDOException $e) {
    $menuItems = [];
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="container nav-inner">
        <a href="/index.php" class="nav-logo">
            <?php if ($siteLogo): ?>
                <img src="<?= sanitize($siteLogo) ?>" alt="Solicitador" class="nav-logo-img">
            <?php else: ?>
                Solicitador
            <?php endif; ?>
        </a>
        <button class="nav-toggle" aria-label="Menu" id="navToggle">&#9776;</button>
        <ul class="nav-links" id="navLinks">
            <?php foreach ($menuItems as $mi):
                $menuTitle = $currentLang === 'fr' ? $mi['title_fr'] : $mi['title_pt'];
            ?>
            <li><a href="<?= sanitize($mi['url']) ?>" <?= $mi['target'] === '_blank' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>><?= sanitize($menuTitle) ?></a></li>
            <?php endforeach; ?>
            <?php if (empty($menuItems)): ?>
            <li><a href="/index.php"><?= lang('nav.home') ?></a></li>
            <li><a href="/index.php#services"><?= lang('nav.services') ?></a></li>
            <li><a href="/booking.php"><?= lang('nav.book') ?></a></li>
            <li><a href="/index.php#contact"><?= lang('nav.contact') ?></a></li>
            <?php endif; ?>
        </ul>
        <div class="lang-switcher">
            <a href="?lang=pt" class="lang-btn <?= $currentLang === 'pt' ? 'active' : '' ?>" title="Português">
                <span class="flag-icon">&#x1F1F5;&#x1F1F9;</span>
            </a>
            <a href="?lang=fr" class="lang-btn <?= $currentLang === 'fr' ? 'active' : '' ?>" title="Français">
                <span class="flag-icon">&#x1F1EB;&#x1F1F7;</span>
            </a>
        </div>
    </div>
</nav>
