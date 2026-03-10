<?php
// ─────────────────────────────────────────────────────────────
//  AssetIQ — Intune Sync API
//  Authenticates with Microsoft Graph using client credentials
//  and returns managed devices ready for import.
//
//  Actions (POST with JSON body):
//    { "action": "fetch" }           → pull devices from Intune
//    { "action": "import", "devices": [...] } → import selected into DB
//    { "action": "test" }            → test credentials only
// ─────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../db.php';

// ── Config ────────────────────────────────────────────────────
require_once __DIR__ . '/../config.php';

// Intune / Microsoft Graph credentials
// Add these to your config.php:
//   define('INTUNE_TENANT_ID',     'your-tenant-id');
//   define('INTUNE_CLIENT_ID',     'your-client-id');
//   define('INTUNE_CLIENT_SECRET', 'your-client-secret');

function respond(mixed $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'POST required'], 405);
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? '';

// ── Validate credentials are configured ───────────────────────
if (!defined('INTUNE_TENANT_ID') || !defined('INTUNE_CLIENT_ID') || !defined('INTUNE_CLIENT_SECRET')) {
    respond(['error' => 'Intune credentials not configured. Add INTUNE_TENANT_ID, INTUNE_CLIENT_ID, and INTUNE_CLIENT_SECRET to config.php.', 'unconfigured' => true], 400);
}

// ── cURL helper ───────────────────────────────────────────────
function curlRequest(string $url, array $options = []): array {
    if (!function_exists('curl_init')) {
        throw new Exception('cURL is not available on this server. Please enable the php-curl extension in Cloudways → PHP Settings.');
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $options['timeout'] ?? 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTPHEADER     => $options['headers'] ?? [],
    ]);
    if (!empty($options['post'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['post']);
    }
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($res === false || $err) {
        throw new Exception('cURL error: ' . $err);
    }
    $json = json_decode($res, true);
    if ($json === null) {
        throw new Exception('Invalid JSON response (HTTP ' . $code . '): ' . substr($res, 0, 200));
    }
    return ['body' => $json, 'code' => $code];
}

// ── Get OAuth2 token ──────────────────────────────────────────
function getGraphToken(): string {
    $url  = 'https://login.microsoftonline.com/' . INTUNE_TENANT_ID . '/oauth2/v2.0/token';
    $data = http_build_query([
        'grant_type'    => 'client_credentials',
        'client_id'     => INTUNE_CLIENT_ID,
        'client_secret' => INTUNE_CLIENT_SECRET,
        'scope'         => 'https://graph.microsoft.com/.default',
    ]);
    $result = curlRequest($url, [
        'post'    => $data,
        'headers' => ['Content-Type: application/x-www-form-urlencoded'],
        'timeout' => 15,
    ]);
    $json = $result['body'];
    if (isset($json['error'])) {
        throw new Exception('Auth error (' . $json['error'] . '): ' . ($json['error_description'] ?? 'unknown'));
    }
    if (empty($json['access_token'])) {
        throw new Exception('No access token returned. Check Tenant ID and Client Secret.');
    }
    return $json['access_token'];
}

// ── Graph API GET helper ──────────────────────────────────────
function graphGet(string $url, string $token): array {
    $result = curlRequest($url, [
        'headers' => [
            'Authorization: Bearer ' . $token,
            'ConsistencyLevel: eventual',
            'Accept: application/json',
        ],
        'timeout' => 30,
    ]);
    $json = $result['body'];
    $code = $result['code'];

    if (isset($json['error'])) {
        $msg  = $json['error']['message'] ?? $json['error']['code'] ?? 'Unknown error';
        $code = $json['error']['code']    ?? $code;
        throw new Exception('Graph API error [' . $code . ']: ' . $msg);
    }
    if ($result['code'] >= 400) {
        throw new Exception('Graph API returned HTTP ' . $result['code'] . ' for URL: ' . $url);
    }
    return $json;
}

// ── Map Intune device type to AssetIQ type ────────────────────
function mapDeviceType(string $os, string $model = ''): string {
    $os    = strtolower($os);
    $model = strtolower($model);
    if (str_contains($model, 'macbook') || str_contains($model, 'laptop') || str_contains($model, 'notebook')) return 'Laptop';
    if (str_contains($model, 'imac') || str_contains($model, 'desktop') || str_contains($model, 'mini')) return 'Desktop';
    if (str_contains($os, 'windows')) return 'Laptop';
    if (str_contains($os, 'mac'))     return 'Laptop';
    if (str_contains($os, 'ios') || str_contains($os, 'android')) return 'Peripheral';
    return 'Laptop';
}

// ── Next asset ID ─────────────────────────────────────────────
function nextId(PDO $db): string {
    $row = $db->query("SELECT id FROM assets ORDER BY created_at DESC, id DESC LIMIT 1")->fetch();
    if (!$row) return 'AST-0001';
    preg_match('/AST-(\d+)/', $row['id'], $m);
    $n = isset($m[1]) ? (int)$m[1] + 1 : 1;
    return 'AST-' . str_pad($n, 4, '0', STR_PAD_LEFT);
}

// ═════════════════════════════════════════════════════════════
//  ACTION: test
// ═════════════════════════════════════════════════════════════
if ($action === 'test') {
    try {
        $token = getGraphToken();
        $res   = graphGet('https://graph.microsoft.com/v1.0/deviceManagement/managedDevices?$top=1&$select=id', $token);
        respond(['success' => true, 'message' => 'Connection successful! Intune API is reachable.']);
    } catch (Exception $e) {
        respond(['success' => false, 'error' => $e->getMessage()], 400);
    }
}

// ═════════════════════════════════════════════════════════════
//  ACTION: fetch — pull devices from Intune
// ═════════════════════════════════════════════════════════════
if ($action === 'fetch') {
    try {
        $token = getGraphToken();

        $fields = implode(',', [
            'id', 'deviceName', 'serialNumber', 'model', 'manufacturer',
            'operatingSystem', 'osVersion', 'userPrincipalName',
            'userDisplayName', 'enrolledDateTime', 'lastSyncDateTime',
            'complianceState', 'managedDeviceOwnerType',
        ]);

        $devices = [];
        $url = "https://graph.microsoft.com/v1.0/deviceManagement/managedDevices?\$select={$fields}&\$top=999";

        // Handle pagination
        while ($url) {
            $res     = graphGet($url, $token);
            $devices = array_merge($devices, $res['value'] ?? []);
            $url     = $res['@odata.nextLink'] ?? null;
        }

        // Get existing serials to flag duplicates
        $db              = getDB();
        $existingSerials = $db->query("SELECT serial FROM assets WHERE serial != '' AND serial IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
        $existingSerials = array_map('strtolower', $existingSerials);

        // Shape for frontend
        $shaped = array_map(function($d) use ($existingSerials) {
            $serial   = trim($d['serialNumber'] ?? '');
            $enrolled = !empty($d['enrolledDateTime'])
                ? substr($d['enrolledDateTime'], 0, 10)
                : null;

            return [
                'intuneId'      => $d['id'],
                'deviceName'    => $d['deviceName'] ?? '',
                'serial'        => $serial,
                'model'         => trim(($d['manufacturer'] ?? '') . ' ' . ($d['model'] ?? '')),
                'os'            => ($d['operatingSystem'] ?? '') . ' ' . ($d['osVersion'] ?? ''),
                'assignedTo'    => $d['userDisplayName'] ?? '',
                'userEmail'     => $d['userPrincipalName'] ?? '',
                'enrolledDate'  => $enrolled,
                'type'          => mapDeviceType($d['operatingSystem'] ?? '', $d['model'] ?? ''),
                'compliance'    => $d['complianceState'] ?? 'unknown',
                'ownerType'     => $d['managedDeviceOwnerType'] ?? '',
                'alreadyExists' => in_array(strtolower($serial), $existingSerials) && $serial !== '',
            ];
        }, $devices);

        // Sort: not-yet-imported first
        usort($shaped, fn($a,$b) => $a['alreadyExists'] <=> $b['alreadyExists']);

        respond([
            'devices' => $shaped,
            'total'   => count($shaped),
            'new'     => count(array_filter($shaped, fn($d) => !$d['alreadyExists'])),
        ]);

    } catch (Exception $e) {
        respond(['error' => $e->getMessage()], 400);
    }
}

// ═════════════════════════════════════════════════════════════
//  ACTION: import — save selected devices into AssetIQ DB
// ═════════════════════════════════════════════════════════════
if ($action === 'import') {
    $toImport = $body['devices'] ?? [];
    if (empty($toImport)) respond(['error' => 'No devices provided'], 422);

    $db      = getDB();
    $imported = 0;
    $skipped  = 0;
    $errors   = [];

    foreach ($toImport as $d) {
        try {
            $serial = trim($d['serial'] ?? '');

            // Skip if serial already exists
            if ($serial !== '') {
                $check = $db->prepare("SELECT id FROM assets WHERE serial = ?");
                $check->execute([$serial]);
                if ($check->fetch()) { $skipped++; continue; }
            }

            $id          = nextId($db);
            $name        = trim($d['model'] ?: $d['deviceName'] ?: 'Unknown Device');
            $assignedTo  = trim($d['assignedTo'] ?? '');
            $dept        = '';
            $enrolled    = $d['enrolledDate'] ?? null;
            $eol         = null;
            if ($enrolled) {
                $eolDate = new DateTime($enrolled);
                $eolDate->modify('+6 years');
                $eol = $eolDate->format('Y-m-d');
            }

            $stmt = $db->prepare("
                INSERT INTO assets (id, name, type, serial, assigned_to, department, purchase_date, end_of_life, cost, notes)
                VALUES (:id,:name,:type,:serial,:assigned_to,:dept,:purchase_date,:end_of_life,NULL,:notes)
            ");
            $stmt->execute([
                'id'           => $id,
                'name'         => $name,
                'type'         => $d['type'] ?? 'Laptop',
                'serial'       => $serial,
                'assigned_to'  => $assignedTo,
                'dept'         => $dept,
                'purchase_date'=> $enrolled,
                'end_of_life'  => $eol,
                'notes'        => 'Imported from Intune. OS: ' . trim($d['os'] ?? '') . ($d['userEmail'] ? ' | User: '.$d['userEmail'] : ''),
            ]);
            $imported++;

        } catch (Exception $e) {
            $errors[] = ($d['deviceName'] ?? '?') . ': ' . $e->getMessage();
        }
    }

    respond([
        'imported' => $imported,
        'skipped'  => $skipped,
        'errors'   => $errors,
    ]);
}

respond(['error' => 'Unknown action'], 400);
