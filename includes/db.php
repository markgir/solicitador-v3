<?php
function get_db(): PDO {
    $configFile = __DIR__ . '/../config.php';
    if (!file_exists($configFile)) {
        die('Ficheiro config.php não encontrado. Copie config.example.php para config.php e preencha os dados da base de dados.');
    }
    $cfg = require $configFile;

    $dsn = 'mysql:host=' . $cfg['db_host'] . ';dbname=' . $cfg['db_name'] . ';charset=' . ($cfg['db_charset'] ?? 'utf8mb4');
    try {
        $pdo = new PDO($dsn, $cfg['db_user'], $cfg['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die('Erro ao ligar à base de dados. Verifique as credenciais em config.php.');
    }
    return $pdo;
}
