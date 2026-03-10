<?php
// AssetIQ — Asset Links API
// GET  api/links.php?id=X      → get all links for asset X
// POST api/links.php            → create link {asset_id_a, asset_id_b, note?}
// DELETE api/links.php?id=X    → delete link by link id

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/auth.php';
auth_require_json();

$db = getDB();
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

// GET links for an asset (bidirectional)
if ($method === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $db->prepare("
        SELECT l.id, l.note, l.created_at,
               CASE WHEN l.asset_id_a = ? THEN l.asset_id_b ELSE l.asset_id_a END AS linked_id,
               a.name AS linked_name, a.type AS linked_type,
               a.assigned_to AS linked_assigned_to, a.status AS linked_status,
               COALESCE(a.archived,0) AS linked_archived
        FROM asset_links l
        JOIN assets a ON a.id = CASE WHEN l.asset_id_a = ? THEN l.asset_id_b ELSE l.asset_id_a END
        WHERE l.asset_id_a = ? OR l.asset_id_b = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$id,$id,$id,$id]);
    respond($stmt->fetchAll());
}

// POST — create link
if ($method === 'POST') {
    $d  = json_decode(file_get_contents('php://input'), true) ?? [];
    $a  = trim($d['asset_id_a'] ?? '');
    $b  = trim($d['asset_id_b'] ?? '');
    if (!$a || !$b) respond(['error' => 'Both asset IDs required'], 422);
    if ($a === $b)  respond(['error' => 'Cannot link an asset to itself'], 422);
    // Normalise order so (A,B) and (B,A) don't both insert
    if ($a > $b) [$a,$b] = [$b,$a];
    $note = trim($d['note'] ?? '');
    try {
        $db->prepare("INSERT INTO asset_links (asset_id_a, asset_id_b, note) VALUES (?,?,?)")
           ->execute([$a,$b,$note]);
        respond(['created' => true, 'id' => $db->lastInsertId()]);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') respond(['error' => 'Link already exists'], 409);
        throw $e;
    }
}

// DELETE — remove link
if ($method === 'DELETE' && isset($_GET['id'])) {
    $stmt = $db->prepare("DELETE FROM asset_links WHERE id=?");
    $stmt->execute([$_GET['id']]);
    if ($stmt->rowCount() === 0) respond(['error' => 'Not found'], 404);
    respond(['deleted' => true]);
}

respond(['error' => 'Method not allowed'], 405);
