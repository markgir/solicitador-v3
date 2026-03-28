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
$siteLogo = get_setting($headerDb, 'site_logo');
$menuStmt = $headerDb->query("SELECT * FROM menu_items WHERE active = 1 ORDER BY sort_order ASC");
$menuItems = $menuStmt->fetchAll();
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
                <svg class="flag-img" viewBox="0 0 30 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect width="30" height="20" fill="#FF0000"/>
                    <rect width="12" height="20" fill="#006600"/>
                    <circle cx="12" cy="10" r="4.5" fill="#FFCC00"/>
                    <circle cx="12" cy="10" r="3.2" fill="#FF0000"/>
                </svg>
            </a>
            <a href="?lang=fr" class="lang-btn <?= $currentLang === 'fr' ? 'active' : '' ?>" title="Français">
                <svg class="flag-img" viewBox="0 0 30 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect width="10" height="20" fill="#002395"/>
                    <rect x="10" width="10" height="20" fill="#FFFFFF"/>
                    <rect x="20" width="10" height="20" fill="#ED2939"/>
                </svg>
            </a>
        </div>
    </div>
</nav>
