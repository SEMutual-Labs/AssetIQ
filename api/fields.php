<?php
// AssetIQ — Custom Fields API
// GET    api/fields.php              → all field definitions
// GET    api/fields.php?type=Laptop  → defs for one type
// GET    api/fields.php?values=1&id=X → field values for asset X
// POST   api/fields.php              → create field def {asset_type, label}
// POST   api/fields.php?values=1     → save values {asset_id, values:{key:val,...}}
// DELETE api/fields.php?id=X        → delete field def

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/auth.php';
auth_require_json();

$db = getDB();

$db->exec("CREATE TABLE IF NOT EXISTS custom_field_defs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    asset_type VARCHAR(64) NOT NULL,
    label      VARCHAR(128) NOT NULL,
    field_key  VARCHAR(64) NOT NULL,
    field_type VARCHAR(16) NOT NULL DEFAULT 'date',
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_key (asset_type, field_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS custom_field_values (
    asset_id  VARCHAR(32) NOT NULL,
    field_key VARCHAR(64) NOT NULL,
    value     VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (asset_id, field_key),
    INDEX idx_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function respond(mixed $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data); exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET values for an asset
if ($method === 'GET' && isset($_GET['values']) && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT field_key, value FROM custom_field_values WHERE asset_id = ?");
    $stmt->execute([$_GET['id']]);
    $out = [];
    foreach ($stmt->fetchAll() as $r) $out[$r['field_key']] = $r['value'];
    respond($out);
}

// GET defs (all or by type)
if ($method === 'GET') {
    $where = '1=1'; $params = [];
    if (!empty($_GET['type'])) { $where = 'asset_type = ?'; $params[] = $_GET['type']; }
    $rows = $db->prepare("SELECT * FROM custom_field_defs WHERE $where ORDER BY asset_type, sort_order, id");
    $rows->execute($params);
    respond($rows->fetchAll());
}

// POST values
if ($method === 'POST' && isset($_GET['values'])) {
    $d = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($d['asset_id'])) respond(['error' => 'asset_id required'], 422);
    $stmt = $db->prepare("INSERT INTO custom_field_values (asset_id, field_key, value) VALUES (?,?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)");
    foreach (($d['values'] ?? []) as $key => $val) {
        if (strlen($key) <= 64) $stmt->execute([$d['asset_id'], $key, (string)$val]);
    }
    respond(['saved' => true]);
}

// POST — create field def
if ($method === 'POST') {
    $d     = json_decode(file_get_contents('php://input'), true) ?? [];
    $type  = trim($d['asset_type'] ?? '');
    $label = trim($d['label'] ?? '');
    if (!$type || !$label) respond(['error' => 'asset_type and label required'], 422);
    // Generate a safe field_key from label
    $key = 'cf_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($label));
    $key = substr($key, 0, 64);
    try {
        $db->prepare("INSERT INTO custom_field_defs (asset_type, label, field_key, field_type) VALUES (?,?,?,'date')")
           ->execute([$type, $label, $key]);
        respond(['created' => true, 'id' => $db->lastInsertId(), 'field_key' => $key]);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') respond(['error' => 'Field already exists for this type'], 409);
        throw $e;
    }
}

// DELETE — remove field def + all its values
if ($method === 'DELETE' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT field_key, asset_type FROM custom_field_defs WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $def = $stmt->fetch();
    if (!$def) respond(['error' => 'Not found'], 404);
    $db->prepare("DELETE FROM custom_field_values WHERE field_key = ?")->execute([$def['field_key']]);
    $db->prepare("DELETE FROM custom_field_defs WHERE id = ?")->execute([$_GET['id']]);
    respond(['deleted' => true]);
}

respond(['error' => 'Method not allowed'], 405);
