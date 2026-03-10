<?php
// AssetIQ — Settings API
// GET  api/settings.php        → all settings
// POST api/settings.php        → upsert key/value pairs {key, value}
// GET  api/settings.php?alerts → low-stock alert status

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/auth.php';
auth_require_json();

$db = getDB();

// Ensure settings table exists
$db->exec("CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(64) PRIMARY KEY,
    `value` TEXT NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure asset_links table exists (create here so any API call bootstraps it)
$db->exec("CREATE TABLE IF NOT EXISTS asset_links (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    asset_id_a VARCHAR(32) NOT NULL,
    asset_id_b VARCHAR(32) NOT NULL,
    note       VARCHAR(255) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_link (asset_id_a, asset_id_b),
    INDEX idx_a (asset_id_a),
    INDEX idx_b (asset_id_b)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function respond(mixed $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data); exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Seed defaults if not present
$defaults = [
    'threshold_laptop'          => '2',
    'threshold_desktop'         => '1',
    'threshold_monitor'         => '2',
    'threshold_docking_station' => '1',
    'threshold_printer'         => '1',
    'threshold_camera'          => '1',
    'threshold_other'           => '1',
    'alerts_enabled'            => '1',
    'depreciation_years'        => '5',
];
foreach ($defaults as $k => $v) {
    $db->prepare("INSERT IGNORE INTO settings (`key`,`value`) VALUES (?,?)")->execute([$k,$v]);
}

// GET alerts — returns triggered alert list
if ($method === 'GET' && isset($_GET['alerts'])) {
    $settings = [];
    foreach ($db->query("SELECT `key`,`value` FROM settings")->fetchAll() as $r)
        $settings[$r['key']] = $r['value'];

    if (empty($settings['alerts_enabled']) || $settings['alerts_enabled'] === '0')
        respond(['alerts' => [], 'enabled' => false]);

    // Count unassigned active non-archived assets per type
    $rows = $db->query("
        SELECT type, COUNT(*) as cnt FROM assets
        WHERE (assigned_to='' OR assigned_to IS NULL)
          AND COALESCE(status,'active') != 'retired'
          AND COALESCE(archived,0) = 0
        GROUP BY type
    ")->fetchAll();
    $unassigned = [];
    foreach ($rows as $r) $unassigned[strtolower($r['type'])] = (int)$r['cnt'];

    $typeKeys = [
        'laptop'=>'threshold_laptop','desktop'=>'threshold_desktop',
        'monitor'=>'threshold_monitor','docking station'=>'threshold_docking_station',
        'printer'=>'threshold_printer','camera'=>'threshold_camera',
    ];
    $alerts = [];
    foreach ($typeKeys as $type => $key) {
        $threshold = (int)($settings[$key] ?? 1);
        $have = $unassigned[$type] ?? 0;
        if ($have < $threshold) {
            $alerts[] = [
                'type'      => ucfirst($type),
                'have'      => $have,
                'threshold' => $threshold,
                'key'       => $key,
            ];
        }
    }
    respond(['alerts' => $alerts, 'enabled' => true]);
}

// GET all settings
if ($method === 'GET') {
    $rows = $db->query("SELECT `key`,`value` FROM settings")->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[$r['key']] = $r['value'];
    respond($out);
}

// POST — upsert one or many settings
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $d   = json_decode($raw, true) ?? [];
    if (empty($d)) respond(['error' => 'No data'], 422);

    // Accept either {key,value} or {key1:val1, key2:val2, ...}
    if (isset($d['key']) && isset($d['value'])) {
        $d = [$d['key'] => $d['value']];
    }
    $stmt = $db->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`), updated_at=NOW()");
    foreach ($d as $k => $v) {
        if (strlen($k) <= 64) $stmt->execute([$k, (string)$v]);
    }
    respond(['saved' => true]);
}

respond(['error' => 'Method not allowed'], 405);
