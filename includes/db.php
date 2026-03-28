<?php
function get_db(): PDO {
    $configFile = __DIR__ . '/../config.php';
    if (!file_exists($configFile)) {
        die('Ficheiro config.php não encontrado. Copie config.example.php para config.php e preencha os dados da base de dados.');
    }
    $cfg = require $configFile;

    $dsn = 'mysql:host=' . $cfg['db_host'] . ';dbname=' . $cfg['db_name'] . ';charset=' . ($cfg['db_charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}
