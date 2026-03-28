<?php
require_once __DIR__ . '/includes/functions.php';

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    redirect('/booking-confirm.php');
}

$lang = get_language();

if (empty($_SESSION['booking_confirm'])) {
    redirect('/index.php');
}

$confirm = $_SESSION['booking_confirm'];
unset($_SESSION['booking_confirm']);

$pageTitle = lang('confirm.title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1><?= lang('confirm.title') ?></h1>
    </div>
</section>

<section class="confirm-section">
    <div class="container">
        <div class="confirm-box">
            <div class="confirm-icon">&#10003;</div>
            <p class="confirm-message"><?= lang('confirm.thank_you') ?></p>

            <div class="payment-box">
                <h3><?= lang('confirm.reference') ?></h3>
                <div class="payment-reference"><?= sanitize($confirm['reference']) ?></div>
                <p><?= lang('confirm.payment_instructions') ?></p>
            </div>

            <div class="next-steps">
                <h3><?= lang('confirm.what_next') ?></h3>
                <ol>
                    <?php
                    $file = __DIR__ . '/lang/' . $lang . '.php';
                    $strings = file_exists($file) ? require $file : require __DIR__ . '/lang/pt.php';
                    $steps = $strings['confirm']['next_steps'] ?? [];
                    foreach ($steps as $step):
                    ?>
                    <li><?= sanitize($step) ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>

            <a href="/index.php" class="btn btn-primary"><?= lang('nav.home') ?></a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
