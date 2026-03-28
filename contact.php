<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    redirect('/contact.php');
}

$lang = get_language();
$db = get_db();

$errors = [];
$success = false;
$formData = [
    'name'    => '',
    'email'   => '',
    'phone'   => '',
    'subject' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Por favor recarregue a página.';
    } else {
        $formData['name']    = trim($_POST['name'] ?? '');
        $formData['email']   = trim($_POST['email'] ?? '');
        $formData['phone']   = trim($_POST['phone'] ?? '');
        $formData['subject'] = trim($_POST['subject'] ?? '');
        $formData['message'] = trim($_POST['message'] ?? '');

        if (empty($formData['name']))    $errors['name']    = lang('errors.required_field');
        if (empty($formData['email'])) {
            $errors['email'] = lang('errors.required_field');
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = lang('errors.invalid_email');
        }
        if (empty($formData['subject'])) $errors['subject'] = lang('errors.required_field');
        if (empty($formData['message'])) $errors['message'] = lang('errors.required_field');

        $documentUrl = '';
        $documentFilename = '';

        if (isset($_FILES['document']) && $_FILES['document']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploaded = upload_document($_FILES['document'], 'contact');
            if ($uploaded) {
                $documentUrl = $uploaded['url'];
                $documentFilename = $uploaded['original_filename'];
            } else {
                $errors['document'] = lang('contact.upload_error');
            }
        }

        if (empty($errors)) {
            try {
                $insert = $db->prepare("INSERT INTO contact_messages (name, email, phone, subject, message, document_url, document_filename, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'new', NOW())");
                $insert->execute([
                    $formData['name'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['subject'],
                    $formData['message'],
                    $documentUrl,
                    $documentFilename,
                ]);

                log_email($formData['email'], 'Mensagem de Contacto - ' . $formData['subject'], 'Mensagem recebida de: ' . $formData['name']);

                $success = true;
                $formData = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
            } catch (Exception $e) {
                $errors[] = lang('contact.error');
            }
        }
    }
}

$pageTitle = lang('contact.title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1><?= lang('contact.title') ?></h1>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="contact-layout">
            <div class="contact-form-wrapper">
                <?php if ($success): ?>
                <div class="alert alert-success"><?= lang('contact.success') ?></div>
                <?php endif; ?>

                <?php if (!empty($errors) && isset($errors[0])): ?>
                <div class="alert alert-error"><?= sanitize($errors[0]) ?></div>
                <?php endif; ?>

                <?php if (!$success): ?>
                <p class="contact-intro"><?= lang('contact.subtitle') ?></p>

                <form class="contact-form" method="POST" action="/contact.php" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

                    <div class="form-grid">
                        <div class="form-group <?= isset($errors['name']) ? 'has-error' : '' ?>">
                            <label for="name"><?= lang('contact.name') ?> *</label>
                            <input type="text" id="name" name="name" value="<?= sanitize($formData['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?><span class="field-error"><?= sanitize($errors['name']) ?></span><?php endif; ?>
                        </div>

                        <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <label for="email"><?= lang('contact.email') ?> *</label>
                            <input type="email" id="email" name="email" value="<?= sanitize($formData['email']) ?>" required>
                            <?php if (isset($errors['email'])): ?><span class="field-error"><?= sanitize($errors['email']) ?></span><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="phone"><?= lang('contact.phone') ?></label>
                            <input type="tel" id="phone" name="phone" value="<?= sanitize($formData['phone']) ?>">
                        </div>

                        <div class="form-group <?= isset($errors['subject']) ? 'has-error' : '' ?>">
                            <label for="subject"><?= lang('contact.subject') ?> *</label>
                            <input type="text" id="subject" name="subject" value="<?= sanitize($formData['subject']) ?>" required>
                            <?php if (isset($errors['subject'])): ?><span class="field-error"><?= sanitize($errors['subject']) ?></span><?php endif; ?>
                        </div>

                        <div class="form-group form-full <?= isset($errors['message']) ? 'has-error' : '' ?>">
                            <label for="message"><?= lang('contact.message') ?> *</label>
                            <textarea id="message" name="message" rows="6" required><?= sanitize($formData['message']) ?></textarea>
                            <?php if (isset($errors['message'])): ?><span class="field-error"><?= sanitize($errors['message']) ?></span><?php endif; ?>
                        </div>

                        <div class="form-group form-full <?= isset($errors['document']) ? 'has-error' : '' ?>">
                            <label for="document"><?= lang('contact.document') ?></label>
                            <input type="file" id="document" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <small><?= lang('contact.document_help') ?></small>
                            <?php if (isset($errors['document'])): ?><span class="field-error"><?= sanitize($errors['document']) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <p class="form-required-note"><?= lang('contact.required') ?></p>
                    <button type="submit" class="btn btn-gold btn-large"><?= lang('contact.submit') ?></button>
                </form>
                <?php endif; ?>
            </div>

            <aside class="contact-info-sidebar">
                <h3><?= lang('contact.info_title') ?></h3>
                <p><?= lang('contact.info_text') ?></p>
                <ul class="contact-info-list">
                    <li>&#x1F4CD; <?= lang('footer.address_placeholder') ?></li>
                    <li>&#x1F4DE; <a href="tel:+351000000000"><?= lang('footer.phone_placeholder') ?></a></li>
                    <li>&#x2709;&#xFE0F; <a href="mailto:info@solicitador.pt"><?= lang('footer.email_placeholder') ?></a></li>
                </ul>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
