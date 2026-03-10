<?php
// ─────────────────────────────────────────────────────────────
//  AssetIQ — AI Price Estimate Proxy (Anthropic)
// ─────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

require_once __DIR__ . '/../auth/auth.php';
auth_require_json();

require_once __DIR__ . '/../config.php';

if (!defined('ANTHROPIC_API_KEY') || !ANTHROPIC_API_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'Anthropic API key not configured. Add ANTHROPIC_API_KEY to config.php.']);
    exit;
}

$body         = json_decode(file_get_contents('php://input'), true);
$name         = trim($body['name']         ?? '');
$type         = trim($body['type']         ?? '');
$serial       = trim($body['serial']       ?? '');
$purchaseDate = trim($body['purchaseDate'] ?? '');
$notes        = trim($body['notes']        ?? '');

if (!$name && !$serial) {
    http_response_code(422);
    echo json_encode(['error' => 'Name or serial required']);
    exit;
}

$assetAge = 'purchase date unknown';
if ($purchaseDate) {
    $yrs = round((time() - strtotime($purchaseDate)) / 31557600, 1);
    $assetAge = "purchased $purchaseDate ({$yrs} years ago)";
}

$prompt = "You are an IT asset valuation assistant. Estimate the current fair market / used resale value of this asset.\n\nAsset details:\n- Name/Model: $name\n- Type: $type\n- Serial: " . ($serial ?: 'not provided') . "\n- Age: $assetAge\n- Notes: " . ($notes ?: 'none') . "\n\nRespond in this exact JSON format, nothing else:\n{\n  \"low\": 000,\n  \"high\": 000,\n  \"midpoint\": 000,\n  \"currency\": \"CAD\",\n  \"confidence\": \"high|medium|low\",\n  \"reasoning\": \"one sentence explanation\",\n  \"caveat\": \"one sentence caveat if any, or null\"\n}\n\nBase estimates on current Canadian used/refurbished market prices. Factor in age and typical depreciation for business IT equipment. If the model is unrecognisable, set confidence to low and give a broad range.";

$payload = json_encode([
    'model'      => 'claude-haiku-4-5-20251001',
    'max_tokens' => 400,
    'messages'   => [
        ['role' => 'user', 'content' => $prompt]
    ]
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not reach Anthropic API: ' . $curlError]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => $data['error']['message'] ?? 'Anthropic API error']);
    exit;
}

$text  = $data['content'][0]['text'] ?? '';
$clean = preg_replace('/```json|```/', '', $text);
$est   = json_decode(trim($clean), true);

if (!$est || !isset($est['low'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Could not parse price estimate']);
    exit;
}

echo json_encode($est);
