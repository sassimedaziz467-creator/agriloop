<?php
declare(strict_types=1);

function agriloop_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $databaseConfig = $config['database'];
    $host = $databaseConfig['host'];
    $database = $databaseConfig['name'];
    $username = $databaseConfig['user'];
    $password = $databaseConfig['password'];
    if ($username === '') {
        throw new RuntimeException('AGRILOOP_DB_USER is not configured.');
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $database);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
