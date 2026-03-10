<?php
// AssetIQ REST API — with audit log + archive support

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/auth.php';
auth_require_json();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();
$user   = auth_user();
$actor  = $user['name'] ?? $user['email'] ?? 'Unknown';

// Ensure schema columns/tables exist
$db->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS eol_override TINYINT(1) NOT NULL DEFAULT 0");
$db->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS archived TINYINT(1) NOT NULL DEFAULT 0");
$db->exec("ALTER TABLE assets ADD COLUMN IF NOT EXISTS archived_at DATETIME NULL");
$db->exec("CREATE TABLE IF NOT EXISTS asset_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    asset_id     VARCHAR(32) NOT NULL,
    asset_name   VARCHAR(255) NOT NULL DEFAULT '',
    action       VARCHAR(32) NOT NULL,
    changed_fields JSON NULL,
    performed_by VARCHAR(255) NOT NULL DEFAULT '',
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_asset_id (asset_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Helpers
function respond(mixed $data, int $code = 200): void {
    http_response_code($code); echo json_encode($data); exit;
}
function bodyJson(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}
function sanitizeAsset(array $d): array {
    $validStatuses = ['active','retired'];
    return [
        'name'          => trim($d['name'] ?? ''),
        'type'          => trim($d['type'] ?? 'Laptop'),
        'serial'        => trim($d['serial'] ?? ''),
        'assigned_to'   => trim($d['assigned_to'] ?? ''),
        'department'    => trim($d['department'] ?? ''),
        'status'        => in_array($d['status'] ?? '', $validStatuses) ? $d['status'] : 'active',
        'purchase_date' => !empty($d['purchase_date']) ? $d['purchase_date'] : null,
        'end_of_life'   => !empty($d['end_of_life'])   ? $d['end_of_life']   : null,
        'cost'          => isset($d['cost']) && $d['cost'] !== '' ? (float)$d['cost'] : null,
        'notes'         => trim($d['notes'] ?? ''),
        'eol_override'  => isset($d['eol_override']) ? (int)(bool)$d['eol_override'] : 0,
    ];
}
function nextId(PDO $db, string $type = ''): string {
    $prefix = match(strtolower($type)) {
        'laptop' => 'SEM-NB', 'desktop' => 'SEM-PC', 'monitor' => 'SEM-M',
        'docking station' => 'SEM-D', 'printer' => 'SEM-PR', 'camera' => 'SEM-CAM',
        default => 'SEM-P',
    };
    $stmt = $db->prepare("SELECT id FROM assets WHERE id LIKE ? ORDER BY id DESC");
    $stmt->execute([$prefix.'%']);
    $max = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id)
        if (preg_match('/-(\d+)$/', $id, $m)) $max = max($max, (int)$m[1]);
    return $prefix . str_pad($max + 1, 2, '0', STR_PAD_LEFT);
}
function rowToAsset(array $r): array {
    return [
        'id'          => $r['id'],         'name'        => $r['name'],
        'type'        => $r['type'],        'serial'      => $r['serial'],
        'assignedTo'  => $r['assigned_to'], 'dept'        => $r['department'],
        'status'      => $r['status'] ?? 'active',
        'purchaseDate'=> $r['purchase_date'], 'endOfLife' => $r['end_of_life'],
        'eolOverride' => !empty($r['eol_override']),
        'archived'    => !empty($r['archived']),
        'archivedAt'  => $r['archived_at'] ?? null,
        'cost'        => $r['cost'],        'notes'       => $r['notes'],
        'createdAt'   => $r['created_at'],  'updatedAt'   => $r['updated_at'],
    ];
}
function writeLog(PDO $db, string $assetId, string $assetName, string $action, array $changed, string $actor): void {
    $db->prepare("INSERT INTO asset_logs (asset_id,asset_name,action,changed_fields,performed_by) VALUES (?,?,?,?,?)")
       ->execute([$assetId, $assetName, $action, empty($changed) ? null : json_encode($changed), $actor]);
}
function diffFields(array $old, array $new): array {
    $track = ['name','type','serial','assigned_to','department','status','purchase_date','end_of_life','cost','notes','eol_override'];
    $changed = [];
    foreach ($track as $f) if ((string)($old[$f]??'') !== (string)($new[$f]??'')) $changed[] = $f;
    return $changed;
}

// Routes

// Audit log
if ($method === 'GET' && isset($_GET['logs'])) {
    $where = ['1=1']; $params = [];
    if (!empty($_GET['id'])) { $where[] = 'asset_id = ?'; $params[] = $_GET['id']; }
    $limit  = min((int)($_GET['limit'] ?? 50), 200);
    $offset = (int)($_GET['offset'] ?? 0);
    $rows = $db->prepare("SELECT * FROM asset_logs WHERE ".implode(' AND ',$where)." ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $rows->execute($params); $rows = $rows->fetchAll();
    $total = (int)$db->prepare("SELECT COUNT(*) FROM asset_logs WHERE ".implode(' AND ',$where))->execute($params) ? 0 : 0;
    // simpler count:
    $cstmt = $db->prepare("SELECT COUNT(*) FROM asset_logs WHERE ".implode(' AND ',$where));
    $cstmt->execute($params); $total = (int)$cstmt->fetchColumn();
    respond(['logs' => array_map(fn($r)=>[
        'id'=>$r['id'],'assetId'=>$r['asset_id'],'assetName'=>$r['asset_name'],
        'action'=>$r['action'],'changedFields'=>$r['changed_fields']?json_decode($r['changed_fields'],true):[],
        'performedBy'=>$r['performed_by'],'createdAt'=>$r['created_at'],
    ], $rows), 'total'=>$total]);
}

// Stats
if ($method === 'GET' && isset($_GET['stats'])) {
    $base   = "COALESCE(archived,0)=0";
    $active = "$base AND COALESCE(status,'active')!='retired'";
    $dateFilter = '';
    $params     = [];
    if (!empty($_GET['purchased_after'])) {
        $dateFilter .= " AND purchase_date >= ?";
        $params[]    = $_GET['purchased_after'];
    }
    if (!empty($_GET['purchased_before'])) {
        $dateFilter .= " AND purchase_date <= ?";
        $params[]    = $_GET['purchased_before'];
    }
    $af = $active . $dateFilter;
    $bf = $base   . $dateFilter;
    $run = function(string $sql) use ($db, $params) {
        $s = $db->prepare($sql); $s->execute($params); return $s->fetchColumn();
    };
    $typeStmt = $db->prepare("SELECT type,COUNT(*) cnt FROM assets WHERE $af GROUP BY type");
    $typeStmt->execute($params);
    respond([
        'total'      => (int)$run("SELECT COUNT(*) FROM assets WHERE $af"),
        'retired'    => (int)$run("SELECT COUNT(*) FROM assets WHERE $bf AND COALESCE(status,'active')='retired'"),
        'assigned'   => (int)$run("SELECT COUNT(*) FROM assets WHERE assigned_to!='' AND assigned_to IS NOT NULL AND $af"),
        'unassigned' => (int)$run("SELECT COUNT(*) FROM assets WHERE (assigned_to='' OR assigned_to IS NULL) AND $af"),
        'totalCost'  => (float)$run("SELECT COALESCE(SUM(cost),0) FROM assets WHERE $af"),
        'archived'   => (int)$db->query("SELECT COUNT(*) FROM assets WHERE COALESCE(archived,0)=1")->fetchColumn(),
        'byType'     => array_column($typeStmt->fetchAll(), 'cnt', 'type'),
    ]);
}

// Archived list
if ($method === 'GET' && isset($_GET['archived'])) {
    $where = ['COALESCE(archived,0)=1']; $params = [];
    if (!empty($_GET['q'])) {
        $where[] = "(name LIKE ? OR serial LIKE ? OR assigned_to LIKE ? OR id LIKE ?)";
        $q = '%'.$_GET['q'].'%'; $params = [$q,$q,$q,$q];
    }
    $stmt = $db->prepare("SELECT * FROM assets WHERE ".implode(' AND ',$where)." ORDER BY archived_at DESC");
    $stmt->execute($params);
    respond(array_map('rowToAsset', $stmt->fetchAll()));
}

// GET single
if ($method === 'GET' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM assets WHERE id=?"); $stmt->execute([$_GET['id']]);
    $row = $stmt->fetch(); if (!$row) respond(['error'=>'Not found'],404);
    respond(rowToAsset($row));
}

// GET all
if ($method === 'GET') {
    $where = ['COALESCE(archived,0)=0']; $params = [];
    if (!empty($_GET['q'])) {
        $where[] = "(name LIKE ? OR serial LIKE ? OR assigned_to LIKE ? OR department LIKE ? OR id LIKE ?)";
        $q = '%'.$_GET['q'].'%'; $params = array_merge($params,[$q,$q,$q,$q,$q]);
    }
    if (!empty($_GET['type'])) { $where[] = "type=?"; $params[] = $_GET['type']; }
    if (!empty($_GET['dept'])) { $where[] = "department=?"; $params[] = $_GET['dept']; }
    if (!empty($_GET['status'])) {
        if     ($_GET['status']==='assigned')   $where[]="assigned_to!='' AND assigned_to IS NOT NULL AND COALESCE(status,'active')!='retired'";
        elseif ($_GET['status']==='unassigned') $where[]="(assigned_to='' OR assigned_to IS NULL) AND COALESCE(status,'active')!='retired'";
        elseif ($_GET['status']==='retired')    $where[]="COALESCE(status,'active')='retired'";
        elseif ($_GET['status']==='eol')        $where[]="end_of_life IS NOT NULL AND end_of_life<=DATE_ADD(CURDATE(),INTERVAL 12 MONTH) AND COALESCE(status,'active')!='retired'";
        else                                    $where[]="COALESCE(status,'active')!='retired'";
    } else {
        if (empty($_GET['show_retired'])) $where[]="COALESCE(status,'active')!='retired'";
    }
    $order = 'created_at DESC';
    $allowed = ['id','name','type','serial','assigned_to','purchase_date','cost'];
    if (!empty($_GET['sort']) && in_array($_GET['sort'],$allowed)) {
        $order = $_GET['sort'].' '.(($_GET['dir']??'asc')==='desc'?'DESC':'ASC');
    }
    $stmt = $db->prepare("SELECT * FROM assets WHERE ".implode(' AND ',$where)." ORDER BY $order");
    $stmt->execute($params);
    respond(array_map('rowToAsset',$stmt->fetchAll()));
}

// POST — create
if ($method === 'POST') {
    $d = bodyJson(); if (empty($d['name'])) respond(['error'=>'name is required'],422);
    $id = nextId($db, $d['type']??''); $s = sanitizeAsset($d);
    $db->prepare("INSERT INTO assets (id,name,type,serial,assigned_to,department,status,purchase_date,end_of_life,cost,notes,eol_override) VALUES (:id,:name,:type,:serial,:assigned_to,:department,:status,:purchase_date,:end_of_life,:cost,:notes,:eol_override)")
       ->execute(array_merge(['id'=>$id],$s));
    writeLog($db,$id,$s['name'],'created',[],$actor);
    respond(rowToAsset($db->query("SELECT * FROM assets WHERE id='$id'")->fetch()),201);
}

// PUT — archive
if ($method === 'PUT' && isset($_GET['archive'])) {
    $d = bodyJson(); if (empty($d['id'])) respond(['error'=>'id required'],422);
    $stmt = $db->prepare("SELECT * FROM assets WHERE id=?"); $stmt->execute([$d['id']]);
    $row = $stmt->fetch(); if (!$row) respond(['error'=>'Not found'],404);
    $db->prepare("UPDATE assets SET archived=1,archived_at=NOW() WHERE id=?")->execute([$d['id']]);
    writeLog($db,$d['id'],$row['name'],'archived',[],$actor);
    respond(rowToAsset($db->query("SELECT * FROM assets WHERE id='{$d['id']}'")->fetch()));
}

// PUT — restore
if ($method === 'PUT' && isset($_GET['restore'])) {
    $d = bodyJson(); if (empty($d['id'])) respond(['error'=>'id required'],422);
    $stmt = $db->prepare("SELECT * FROM assets WHERE id=?"); $stmt->execute([$d['id']]);
    $row = $stmt->fetch(); if (!$row) respond(['error'=>'Not found'],404);
    $db->prepare("UPDATE assets SET archived=0,archived_at=NULL WHERE id=?")->execute([$d['id']]);
    writeLog($db,$d['id'],$row['name'],'restored',[],$actor);
    respond(rowToAsset($db->query("SELECT * FROM assets WHERE id='{$d['id']}'")->fetch()));
}

// PUT — update
if ($method === 'PUT') {
    $d = bodyJson(); if (empty($d['id'])) respond(['error'=>'id required'],422);
    $chk = $db->prepare("SELECT * FROM assets WHERE id=?"); $chk->execute([$d['id']]);
    $old = $chk->fetch(); if (!$old) respond(['error'=>'Not found'],404);
    $s = sanitizeAsset($d); $diff = diffFields($old,$s);
    $db->prepare("UPDATE assets SET name=:name,type=:type,serial=:serial,assigned_to=:assigned_to,department=:department,status=:status,purchase_date=:purchase_date,end_of_life=:end_of_life,cost=:cost,notes=:notes,eol_override=:eol_override WHERE id=:id")
       ->execute(array_merge(['id'=>$d['id']],$s));
    if (!empty($diff)) writeLog($db,$d['id'],$s['name'],'updated',$diff,$actor);
    respond(rowToAsset($db->query("SELECT * FROM assets WHERE id='{$d['id']}'")->fetch()));
}

// DELETE — hard delete
if ($method === 'DELETE' && isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT name FROM assets WHERE id=?"); $stmt->execute([$_GET['id']]);
    $row = $stmt->fetch(); if (!$row) respond(['error'=>'Not found'],404);
    writeLog($db,$_GET['id'],$row['name'],'deleted',[],$actor);
    $db->prepare("DELETE FROM assets WHERE id=?")->execute([$_GET['id']]);
    respond(['deleted'=>$_GET['id']]);
}

respond(['error'=>'Method not allowed'],405);
