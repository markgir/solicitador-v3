<?php
require_once __DIR__ . '/functions.php';
if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    $redirect = strtok($_SERVER['REQUEST_URI'], '?');
    redirect($redirect ?: '/');
}
$pageTitle = $pageTitle ?? 'Solicitador';
$currentLang = get_language();
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
        <a href="/index.php" class="nav-logo">Solicitador</a>
        <button class="nav-toggle" aria-label="Menu" id="navToggle">&#9776;</button>
        <ul class="nav-links" id="navLinks">
            <li><a href="/index.php"><?= lang('nav.home') ?></a></li>
            <li><a href="/index.php#services"><?= lang('nav.services') ?></a></li>
            <li><a href="/booking.php"><?= lang('nav.book') ?></a></li>
            <li><a href="/index.php#contact"><?= lang('nav.contact') ?></a></li>
        </ul>
        <div class="lang-switcher">
            <a href="?lang=pt" class="lang-btn <?= $currentLang === 'pt' ? 'active' : '' ?>">PT</a>
            <a href="?lang=fr" class="lang-btn <?= $currentLang === 'fr' ? 'active' : '' ?>">FR</a>
        </div>
    </div>
</nav>
