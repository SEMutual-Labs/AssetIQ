<?php
require_once '/home/1280766.cloudwaysapps.com/awhfqygezp/private_html/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
    }
    return $pdo;
}

function installSchema(): void {
    $db = getDB();
    $db->exec("
        CREATE TABLE IF NOT EXISTS assets (
            id            VARCHAR(20)    PRIMARY KEY,
            name          VARCHAR(255)   NOT NULL,
            type          VARCHAR(50)    NOT NULL,
            serial        VARCHAR(255)   DEFAULT '',
            assigned_to   VARCHAR(255)   DEFAULT '',
            department    VARCHAR(255)   DEFAULT '',
            status        VARCHAR(20)    DEFAULT 'active',
            purchase_date DATE           DEFAULT NULL,
            end_of_life   DATE           DEFAULT NULL,
            cost          DECIMAL(10,2)  DEFAULT NULL,
            notes         TEXT           DEFAULT '',
            created_at    DATETIME       DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

installSchema();

try {
    getDB()->exec("ALTER TABLE assets ADD COLUMN end_of_life DATE DEFAULT NULL AFTER purchase_date");
} catch (PDOException $e) {}

try {
    getDB()->exec("ALTER TABLE assets ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER department");
} catch (PDOException $e) {}
