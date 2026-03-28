<?php
$currentLang = get_language();
$footerDb = get_db();
try {
    $footerLogo = get_setting($footerDb, 'site_logo');
} catch (PDOException $e) {
    $footerLogo = '';
}
?>
<footer class="footer" id="contact">
    <div class="container footer-grid">
        <div class="footer-brand">
            <?php if ($footerLogo): ?>
                <img src="<?= sanitize($footerLogo) ?>" alt="Solicitador" class="footer-logo-img">
            <?php else: ?>
                <h3 class="footer-logo">Solicitador</h3>
            <?php endif; ?>
            <p><?= lang('footer.tagline') ?></p>
        </div>
        <div class="footer-contact">
            <h4><?= lang('footer.contact_us') ?></h4>
            <p>&#x1F4CD; <?= lang('footer.address_placeholder') ?></p>
            <p>&#x1F4DE; <a href="tel:+351000000000"><?= lang('footer.phone_placeholder') ?></a></p>
            <p>&#x2709;&#xFE0F; <a href="mailto:info@solicitador.pt"><?= lang('footer.email_placeholder') ?></a></p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Solicitador. <?= lang('footer.rights') ?></p>
    </div>
</footer>
<script src="/assets/js/main.js"></script>
</body>
</html>
