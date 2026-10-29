<?php

header('Content-Type: application/json');
header('Cache-Control: no-store');
require_once __DIR__ . '/../auth/auth.php';
auth_require_json();
send_cors_headers('POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

require_once __DIR__ . '/sharepoint_helpers.php';

function sharepointRespond(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

try {
    $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($body)) throw new InvalidArgumentException('A JSON object is required.');
    if (!is_string($body['csrf'] ?? null) || !isset($_SESSION['sharepoint_csrf']) || !hash_equals($_SESSION['sharepoint_csrf'], $body['csrf'])) {
        sharepointRespond(['error' => 'Invalid SharePoint request token. Reload the page.'], 403);
    }
    $action = $body['action'] ?? '';
    if (!in_array($action, ['preview', 'apply'], true)) throw new InvalidArgumentException('Unknown SharePoint action.');
    require_once __DIR__ . '/../db.php';
    $db = getDB();
    $select = 'SELECT id,name,type,serial,assigned_to,department,status,purchase_date,cost,notes,updated_at,archived FROM assets';
    if ($action === 'preview') {
        unset($_SESSION['sharepoint_preview']);
        $graph = new SharePointGraph();
        $listPath = $graph->listPath();
        $columnMap = defined('SHAREPOINT_COLUMN_MAP') ? SHAREPOINT_COLUMN_MAP : [];
        if (!is_array($columnMap)) throw new RuntimeException('SHAREPOINT_COLUMN_MAP must be an array.');
        $columns = sharepointColumns($graph->collection($listPath . '/columns'), $columnMap);
        $items = $graph->collection($listPath . '/items?$expand=fields&$top=200');
        $assets = $db->query($select . ' WHERE COALESCE(archived,0)=0 ORDER BY id')->fetchAll();
        $valueMaps = defined('SHAREPOINT_VALUE_MAPS') ? SHAREPOINT_VALUE_MAPS : [];
        if (!is_array($valueMaps)) throw new RuntimeException('SHAREPOINT_VALUE_MAPS must be an array.');
        $rows = sharepointPreview($assets, $items, $columns, $valueMaps);
        $id = bin2hex(random_bytes(24));
        $_SESSION['sharepoint_preview'] = [
            'id' => $id, 'expires' => time() + 900, 'listPath' => $listPath,
            'lastUpdatedColumn' => $columns['updated_at']['name'], 'rows' => array_column($rows, null, 'assetId'),
        ];
        $publicRows = array_map(function (array $row): array {
            unset($row['patch'], $row['assetHash'], $row['etag'], $row['itemId']);
            return $row;
        }, $rows);
        sharepointRespond(['previewId' => $id, 'rows' => $publicRows, 'expiresIn' => 900]);
    }

    $plan = $_SESSION['sharepoint_preview'] ?? null;
    if (!$plan || !is_string($body['previewId'] ?? null) || !hash_equals($plan['id'], $body['previewId']) || $plan['expires'] < time()) {
        sharepointRespond(['error' => 'Preview missing or expired. Generate a new preview.'], 409);
    }
    $assetId = $body['assetId'] ?? null;
    if (!is_string($assetId) || !isset($plan['rows'][$assetId]) || $plan['rows'][$assetId]['status'] !== 'ready') {
        throw new InvalidArgumentException('This asset is not available to apply. Preview again.');
    }
    $row = $plan['rows'][$assetId];
    $stmt = $db->prepare($select . ' WHERE id=?');
    $stmt->execute([$assetId]);
    $asset = $stmt->fetch();
    if (!$asset) sharepointRespond(['error' => 'AssetIQ record no longer exists. Preview again.'], 409);
    $graph = new SharePointGraph();
    // Consume before the remote write: an interrupted response must never replay a write.
    $_SESSION['sharepoint_preview']['rows'][$assetId]['status'] = 'attempted';
    $patch = sharepointApplyRow([$graph, 'call'], $plan['listPath'], $row, $asset, $plan['lastUpdatedColumn'], gmdate('Y-m-d\TH:i:s\Z'));
    $user = auth_user();
    try {
        $audit = ['SharePoint Item ID' => ['from' => null, 'to' => $row['itemId']]];
        foreach ($row['changes'] as $change) $audit[$change['column']] = ['from' => $change['before'], 'to' => $change['after']];
        $audit['Last Updated'] = ['from' => null, 'to' => $patch[$plan['lastUpdatedColumn']]];
        $db->prepare('INSERT INTO asset_logs (asset_id,asset_name,action,changed_fields,performed_by) VALUES (?,?,?,?,?)')->execute([
            $assetId, $asset['name'], 'sharepoint_sync',
            json_encode($audit, JSON_THROW_ON_ERROR),
            $user['name'] ?? $user['email'] ?? 'Unknown',
        ]);
    } catch (PDOException $error) {
        error_log('AssetIQ SharePoint update succeeded but audit failed: ' . $error->getMessage());
        sharepointRespond(['error' => 'SharePoint was updated, but the local audit log failed. Do not retry; contact the administrator.'], 500);
    }
    sharepointRespond(['assetId' => $assetId, 'status' => 'updated']);
} catch (InvalidArgumentException | JsonException $error) {
    sharepointRespond(['error' => $error->getMessage()], 400);
} catch (Throwable $error) {
    error_log('AssetIQ SharePoint sync failed: ' . $error->getMessage());
    $message = $error instanceof RuntimeException && !$error instanceof PDOException
        ? $error->getMessage() : 'SharePoint sync failed. Check the server logs and preview again before retrying.';
    sharepointRespond(['error' => $message], 502);
}
