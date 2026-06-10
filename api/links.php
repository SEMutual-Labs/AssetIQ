<?php
// AssetIQ — Asset Links API
// GET  api/links.php?id=X      → get all links for asset X
// POST api/links.php            → create link {asset_id_a, asset_id_b, note?}
// DELETE api/links.php?id=X    → delete link by link id

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/auth.php';
auth_require_json();
send_cors_headers('GET, POST, DELETE, OPTIONS');

$db    = getDB();
$user  = auth_user();
$actor = $user['name'] ?? $user['email'] ?? 'Unknown';

function respond(mixed $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data); exit;
}
function assetNames(PDO $db, array $ids): array {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT id, name FROM assets WHERE id IN ($in)");
    $stmt->execute($ids);
    return array_column($stmt->fetchAll(), 'name', 'id');
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
        $names = assetNames($db, [$a,$b]);
        auditLog($db, $a, $names[$a] ?? $a, 'link_added', ['linked_to'=>['from'=>null,'to'=>"$b (".($names[$b] ?? '?').")"]], $actor);
        auditLog($db, $b, $names[$b] ?? $b, 'link_added', ['linked_to'=>['from'=>null,'to'=>"$a (".($names[$a] ?? '?').")"]], $actor);
        respond(['created' => true, 'id' => $db->lastInsertId()]);
    } catch (\PDOException $e) {
        if ($e->getCode() === '23000') respond(['error' => 'Link already exists'], 409);
        throw $e;
    }
}

// DELETE — remove link
if ($method === 'DELETE' && isset($_GET['id'])) {
    $sel = $db->prepare("SELECT asset_id_a, asset_id_b FROM asset_links WHERE id=?");
    $sel->execute([$_GET['id']]);
    $link = $sel->fetch();
    if (!$link) respond(['error' => 'Not found'], 404);
    $db->prepare("DELETE FROM asset_links WHERE id=?")->execute([$_GET['id']]);
    [$a,$b] = [$link['asset_id_a'], $link['asset_id_b']];
    $names = assetNames($db, [$a,$b]);
    auditLog($db, $a, $names[$a] ?? $a, 'link_removed', ['linked_to'=>['from'=>"$b (".($names[$b] ?? '?').")",'to'=>null]], $actor);
    auditLog($db, $b, $names[$b] ?? $b, 'link_removed', ['linked_to'=>['from'=>"$a (".($names[$a] ?? '?').")",'to'=>null]], $actor);
    respond(['deleted' => true]);
}

respond(['error' => 'Method not allowed'], 405);
