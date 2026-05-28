<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) { return $pdo; }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST, DB_NAME, DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
        error_log('DB Connection failed: ' . $e->getMessage());
        
        // Show actual error in development
        if (APP_DEBUG) {
            die("<h2>Database Connection Error</h2>
                 <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                 <h3>Current Settings:</h3>
                 <ul>
                     <li>DB_HOST: " . DB_HOST . "</li>
                     <li>DB_NAME: " . DB_NAME . "</li>
                     <li>DB_USER: " . DB_USER . "</li>
                     <li>DB_PASS: " . (DB_PASS ? '********' : '(empty)') . "</li>
                 </ul>
                 <h3>Solutions:</h3>
                 <ul>
                     <li>Make sure MySQL is running (XAMPP/WAMP/MAMP)</li>
                     <li>Check username/password in config.php</li>
                     <li>Create database 'rooms' in phpMyAdmin</li>
                 </ul>");
        }
        
        throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
    }

    return $pdo;
}

function dbQuery(string $sql, array $params = []): \PDOStatement {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetchOne(string $sql, array $params = []): ?object {
    $row = dbQuery($sql, $params)->fetch();
    return $row ?: null;
}

function dbFetchAll(string $sql, array $params = []): array {
    return dbQuery($sql, $params)->fetchAll();
}

function dbInsert(string $table, array $data): int {
    $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    dbQuery("INSERT INTO `$table` ($cols) VALUES ($placeholders)", array_values($data));
    return (int)getDB()->lastInsertId();
}