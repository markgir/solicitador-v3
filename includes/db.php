<?php
function get_db(): PDO {
    $dir = __DIR__ . '/../database';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $dsn = 'sqlite:' . $dir . '/solicitor.db';
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA journal_mode=WAL');
    return $pdo;
}
