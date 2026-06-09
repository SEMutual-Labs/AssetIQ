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
        error_log('AssetIQ DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        die(json_encode(['error' => 'Database connection failed']));
    }
    return $pdo;
}

function installSchema(): void {
    $db = getDB();

    // ── Core assets table ─────────────────────────────────────────
    $db->exec("CREATE TABLE IF NOT EXISTS assets (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Migration patches (idempotent) ────────────────────────────
    $db->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS end_of_life  DATE        DEFAULT NULL       AFTER purchase_date");
    $db->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS status       VARCHAR(20) DEFAULT 'active'   AFTER department");
    $db->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS eol_override TINYINT(1)  NOT NULL DEFAULT 0");
    $db->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS archived     TINYINT(1)  NOT NULL DEFAULT 0");
    $db->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS archived_at  DATETIME    NULL");

    // Convert any archived assets to retired status (archive feature removed)
    $db->exec("UPDATE assets SET archived=0, archived_at=NULL, status='retired' WHERE COALESCE(archived,0)=1 AND COALESCE(status,'active')!='retired'");
    $db->exec("UPDATE assets SET archived=0, archived_at=NULL WHERE COALESCE(archived,0)=1");

    // ── Audit log ─────────────────────────────────────────────────
    $db->exec("CREATE TABLE IF NOT EXISTS asset_logs (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        asset_id       VARCHAR(32)  NOT NULL,
        asset_name     VARCHAR(255) NOT NULL DEFAULT '',
        action         VARCHAR(32)  NOT NULL,
        changed_fields JSON         NULL,
        performed_by   VARCHAR(255) NOT NULL DEFAULT '',
        created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_asset_id  (asset_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db->exec("ALTER TABLE asset_logs ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL");

    // ── Settings ──────────────────────────────────────────────────
    $db->exec("CREATE TABLE IF NOT EXISTS settings (
        `key`      VARCHAR(64) PRIMARY KEY,
        `value`    TEXT        NOT NULL DEFAULT '',
        updated_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Asset links ───────────────────────────────────────────────
    $db->exec("CREATE TABLE IF NOT EXISTS asset_links (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        asset_id_a VARCHAR(32)  NOT NULL,
        asset_id_b VARCHAR(32)  NOT NULL,
        note       VARCHAR(255) NOT NULL DEFAULT '',
        created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_link (asset_id_a, asset_id_b),
        INDEX idx_a (asset_id_a),
        INDEX idx_b (asset_id_b)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Custom field definitions ──────────────────────────────────
    $db->exec("CREATE TABLE IF NOT EXISTS custom_field_defs (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        asset_type VARCHAR(64)  NOT NULL,
        label      VARCHAR(128) NOT NULL,
        field_key  VARCHAR(64)  NOT NULL,
        field_type VARCHAR(16)  NOT NULL DEFAULT 'date',
        sort_order INT          NOT NULL DEFAULT 0,
        UNIQUE KEY uq_key (asset_type, field_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Custom field values ───────────────────────────────────────
    $db->exec("CREATE TABLE IF NOT EXISTS custom_field_values (
        asset_id  VARCHAR(32)  NOT NULL,
        field_key VARCHAR(64)  NOT NULL,
        value     VARCHAR(255) NOT NULL DEFAULT '',
        PRIMARY KEY (asset_id, field_key),
        INDEX idx_asset (asset_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

installSchema();
