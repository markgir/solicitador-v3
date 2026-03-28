<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_language(): string {
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['pt', 'fr'])) {
        return $_SESSION['lang'];
    }
    if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['pt', 'fr'])) {
        return $_COOKIE['lang'];
    }
    return 'pt';
}

function set_language(string $lang): void {
    if (in_array($lang, ['pt', 'fr'])) {
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, time() + (86400 * 365), '/');
    }
}

function lang(string $key): string {
    static $strings = null;
    if ($strings === null) {
        $langCode = get_language();
        $file = __DIR__ . '/../lang/' . $langCode . '.php';
        if (file_exists($file)) {
            $strings = require $file;
        } else {
            $strings = require __DIR__ . '/../lang/pt.php';
        }
    }
    $keys = explode('.', $key);
    $value = $strings;
    foreach ($keys as $k) {
        if (is_array($value) && isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $key;
        }
    }
    return is_string($value) ? $value : $key;
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function sanitize(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function generate_payment_reference(): string {
    $date = date('Ymd');
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $random = '';
    for ($i = 0; $i < 4; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return 'SOL-' . $date . '-' . $random;
}

function format_date(string $date, string $lang): string {
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;
    if ($lang === 'fr') {
        $months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        $day = date('d', $timestamp);
        $month = $months[(int)date('n', $timestamp) - 1];
        $year = date('Y', $timestamp);
        return $day . ' ' . $month . ' ' . $year;
    }
    $months = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    $day = date('d', $timestamp);
    $month = $months[(int)date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    return $day . ' de ' . $month . ' de ' . $year;
}

function get_status_label(string $status, string $lang): string {
    $labels = [
        'pt' => [
            'pending'   => 'Pendente',
            'confirmed' => 'Confirmado',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
        ],
        'fr' => [
            'pending'   => 'En attente',
            'confirmed' => 'Confirmé',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
        ],
    ];
    return $labels[$lang][$status] ?? $status;
}

function log_email(string $to, string $subject, string $body): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/email.log';
    $entry = '[' . date('Y-m-d H:i:s') . '] TO: ' . $to . ' | SUBJECT: ' . $subject . "\n" . $body . "\n---\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

function upload_image(array $file, string $subdir = ''): ?string {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] === 0) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes, true)) {
        return null;
    }

    if ($file['size'] > $maxSize) {
        return null;
    }

    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    $uploadsDir = __DIR__ . '/../uploads';
    if ($subdir) {
        $uploadsDir .= '/' . $subdir;
    }
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    $destPath = $uploadsDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return null;
    }

    $relativePath = '/uploads' . ($subdir ? '/' . $subdir : '') . '/' . $filename;
    return $relativePath;
}

function get_setting(PDO $db, string $key): string {
    $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? ($row['setting_value'] ?? '') : '';
}

function set_setting(PDO $db, string $key, string $value): void {
    $existing = $db->prepare("SELECT id FROM site_settings WHERE setting_key = ?");
    $existing->execute([$key]);
    if ($existing->fetch()) {
        $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?")->execute([$value, $key]);
    } else {
        $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)")->execute([$key, $value]);
    }
}
