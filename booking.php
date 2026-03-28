<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    redirect('/booking.php' . (isset($_GET['service']) ? '?service=' . urlencode($_GET['service']) : ''));
}

$lang = get_language();
$db = get_db();

$preselectedService = isset($_GET['service']) ? trim($_GET['service']) : '';

$stmt = $db->query("SELECT * FROM services WHERE active = 1 ORDER BY sort_order ASC");
$services = $stmt->fetchAll();

$errors = [];
$formData = [
    'name'           => '',
    'email'          => '',
    'phone'          => '',
    'nif'            => '',
    'address'        => '',
    'service_slug'   => $preselectedService,
    'preferred_date' => '',
    'preferred_time' => '',
    'notes'          => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de segurança inválido. Por favor recarregue a página.';
    } else {
        $formData['name']           = trim($_POST['name'] ?? '');
        $formData['email']          = trim($_POST['email'] ?? '');
        $formData['phone']          = trim($_POST['phone'] ?? '');
        $formData['nif']            = trim($_POST['nif'] ?? '');
        $formData['address']        = trim($_POST['address'] ?? '');
        $formData['service_slug']   = trim($_POST['service_slug'] ?? '');
        $formData['preferred_date'] = trim($_POST['preferred_date'] ?? '');
        $formData['preferred_time'] = trim($_POST['preferred_time'] ?? '');
        $formData['notes']          = trim($_POST['notes'] ?? '');

        if (empty($formData['name'])) $errors['name'] = lang('errors.required_field');
        if (empty($formData['email'])) {
            $errors['email'] = lang('errors.required_field');
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = lang('errors.invalid_email');
        }
        if (empty($formData['phone'])) $errors['phone'] = lang('errors.required_field');
        if (!empty($formData['nif']) && !preg_match('/^\d{9}$/', $formData['nif'])) {
            $errors['nif'] = lang('errors.invalid_nif');
        }
        if (empty($formData['service_slug'])) $errors['service_slug'] = lang('errors.required_field');
        if (empty($formData['preferred_date'])) {
            $errors['preferred_date'] = lang('errors.required_field');
        } elseif ($formData['preferred_date'] < date('Y-m-d', strtotime('+1 day'))) {
            $errors['preferred_date'] = lang('errors.past_date');
        }
        if (empty($formData['preferred_time'])) $errors['preferred_time'] = lang('errors.required_field');

        if (empty($errors)) {
            $serviceStmt = $db->prepare("SELECT id FROM services WHERE slug = ? AND active = 1");
            $serviceStmt->execute([$formData['service_slug']]);
            $serviceRow = $serviceStmt->fetch();

            if (!$serviceRow) {
                $errors['service_slug'] = lang('errors.required_field');
            } else {
                $ref = generate_payment_reference();
                try {
                    $insert = $db->prepare("INSERT INTO appointments (name, email, phone, nif, address, service_id, preferred_date, preferred_time, notes, payment_reference, status, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', NOW())");
                    $insert->execute([
                        $formData['name'],
                        $formData['email'],
                        $formData['phone'],
                        $formData['nif'],
                        $formData['address'],
                        $serviceRow['id'],
                        $formData['preferred_date'],
                        $formData['preferred_time'],
                        $formData['notes'],
                        $ref,
                    ]);
                    $appointmentId = $db->lastInsertId();

                    log_email($formData['email'], 'Confirmação de Pedido - ' . $ref, 'Pedido recebido. Referência: ' . $ref);

                    $_SESSION['booking_confirm'] = [
                        'id'             => $appointmentId,
                        'name'           => $formData['name'],
                        'email'          => $formData['email'],
                        'reference'      => $ref,
                        'service_slug'   => $formData['service_slug'],
                        'preferred_date' => $formData['preferred_date'],
                        'preferred_time' => $formData['preferred_time'],
                    ];
                    redirect('/booking-confirm.php');
                } catch (Exception $e) {
                    $errors[] = lang('errors.booking_failed');
                }
            }
        }
    }
}

$pageTitle = lang('booking.title') . ' | Solicitador';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1><?= lang('booking.title') ?></h1>
    </div>
</section>

<section class="booking-section">
    <div class="container">
        <?php if (!empty($errors) && isset($errors[0])): ?>
        <div class="alert alert-error"><?= sanitize($errors[0]) ?></div>
        <?php endif; ?>

        <form class="booking-form" method="POST" action="/booking.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-grid">
                <div class="form-group <?= isset($errors['name']) ? 'has-error' : '' ?>">
                    <label for="name"><?= lang('booking.name') ?> *</label>
                    <input type="text" id="name" name="name" value="<?= sanitize($formData['name']) ?>" required>
                    <?php if (isset($errors['name'])): ?><span class="field-error"><?= sanitize($errors['name']) ?></span><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                    <label for="email"><?= lang('booking.email') ?> *</label>
                    <input type="email" id="email" name="email" value="<?= sanitize($formData['email']) ?>" required>
                    <?php if (isset($errors['email'])): ?><span class="field-error"><?= sanitize($errors['email']) ?></span><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                    <label for="phone"><?= lang('booking.phone') ?> *</label>
                    <input type="tel" id="phone" name="phone" value="<?= sanitize($formData['phone']) ?>" required>
                    <?php if (isset($errors['phone'])): ?><span class="field-error"><?= sanitize($errors['phone']) ?></span><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errors['nif']) ? 'has-error' : '' ?>">
                    <label for="nif"><?= lang('booking.nif') ?></label>
                    <input type="text" id="nif" name="nif" value="<?= sanitize($formData['nif']) ?>" pattern="\d{9}" maxlength="9">
                    <?php if (isset($errors['nif'])): ?><span class="field-error"><?= sanitize($errors['nif']) ?></span><?php endif; ?>
                </div>

                <div class="form-group form-full <?= isset($errors['address']) ? 'has-error' : '' ?>">
                    <label for="address"><?= lang('booking.address') ?></label>
                    <input type="text" id="address" name="address" value="<?= sanitize($formData['address']) ?>">
                </div>

                <div class="form-group form-full <?= isset($errors['service_slug']) ? 'has-error' : '' ?>">
                    <label for="service_slug"><?= lang('booking.service') ?> *</label>
                    <select id="service_slug" name="service_slug" required>
                        <option value=""><?= lang('booking.select_service') ?></option>
                        <?php foreach ($services as $svc):
                            $svcName = $lang === 'fr' ? $svc['title_fr'] : $svc['title_pt'];
                            $selected = $formData['service_slug'] === $svc['slug'] ? 'selected' : '';
                        ?>
                        <option value="<?= sanitize($svc['slug']) ?>" <?= $selected ?>><?= sanitize($svcName) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['service_slug'])): ?><span class="field-error"><?= sanitize($errors['service_slug']) ?></span><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errors['preferred_date']) ? 'has-error' : '' ?>">
                    <label for="preferred_date"><?= lang('booking.date') ?> *</label>
                    <input type="date" id="preferred_date" name="preferred_date" value="<?= sanitize($formData['preferred_date']) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    <?php if (isset($errors['preferred_date'])): ?><span class="field-error"><?= sanitize($errors['preferred_date']) ?></span><?php endif; ?>
                </div>

                <div class="form-group <?= isset($errors['preferred_time']) ? 'has-error' : '' ?>">
                    <label for="preferred_time"><?= lang('booking.time') ?> *</label>
                    <select id="preferred_time" name="preferred_time" required>
                        <option value=""><?= lang('booking.select_time') ?></option>
                        <?php
                        $times = ['09:00','09:30','10:00','10:30','11:00','11:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00'];
                        foreach ($times as $t):
                            $sel = $formData['preferred_time'] === $t ? 'selected' : '';
                        ?>
                        <option value="<?= $t ?>" <?= $sel ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['preferred_time'])): ?><span class="field-error"><?= sanitize($errors['preferred_time']) ?></span><?php endif; ?>
                </div>

                <div class="form-group form-full">
                    <label for="notes"><?= lang('booking.notes') ?></label>
                    <textarea id="notes" name="notes" rows="4"><?= sanitize($formData['notes']) ?></textarea>
                </div>
            </div>

            <p class="form-required-note"><?= lang('booking.required') ?></p>
            <button type="submit" class="btn btn-gold btn-large"><?= lang('booking.submit') ?></button>
        </form>
    </div>
</section>
<script>
window.i18n = {
  required:    <?= json_encode(lang('errors.required_field')) ?>,
  invalidEmail:<?= json_encode(lang('errors.invalid_email')) ?>,
  invalidNif:  <?= json_encode(lang('errors.invalid_nif')) ?>,
  pastDate:    <?= json_encode(lang('errors.past_date')) ?>,
  weekendDate: <?= json_encode(lang('errors.weekend_date')) ?>,
  sending:     <?= json_encode(lang('booking.sending')) ?>
};
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
