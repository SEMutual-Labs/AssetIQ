<?php
// ─────────────────────────────────────────────────────────────
//  AssetIQ — ADP Company Property Export (CSV)
//  Generates a CSV matching the ADP bulk import template
//  Opens directly in Excel with correct column mapping
// ─────────────────────────────────────────────────────────────

require_once __DIR__ . '/../auth/auth.php';
auth_require_json();
require_once __DIR__ . '/../db.php';

$db   = getDB();
$rows = $db->query("
    SELECT * FROM assets
    WHERE COALESCE(status,'active') != 'retired'
    AND assigned_to IS NOT NULL AND assigned_to != ''
    ORDER BY assigned_to, type
")->fetchAll(PDO::FETCH_ASSOC);

// Group by user
$byUser = [];
foreach ($rows as $row) {
    $byUser[$row['assigned_to']][] = $row;
}
ksort($byUser);

function adpCategory(string $type): string {
    return match(strtolower($type)) {
        'laptop', 'desktop' => 'computer',
        default             => 'equipment',
    };
}

$headers = [
    'Position ID','Asset Name',
    'Cell Phone Details','Cell Phone Status','Cell Phone Plan Expiry','Cell Phone Date Given','Cell Phone Date Returned',
    'Computer Details','Computer Status','Computer Serial Number','Computer Date Given','Computer Date Returned',
    'Credit Card Status','Credit Card Number','Credit Card Expiry Date','Credit Card Date Given','Credit Card Date Returned',
    'Equipment Details','Equipment Status','Equipment Date Given','Equipment Date Returned',
    'Vehicle Details','Vehicle Status','Vehicle Date Given','Vehicle Date Returned',
    'Security Access Details','Security Access Status','Security Access Type','Security Access Date Given','Security Access Date Returned',
    'Uniform Details','Uniform Status','Uniform Date Given','Uniform Date Returned',
];

$date     = date('Y-m-d');
$filename = "AssetIQ_ADP_Export_{$date}.csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');
header('Pragma: no-cache');

// BOM for Excel UTF-8 compatibility
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, $headers);

foreach ($byUser as $user => $assets) {
    $computers = array_values(array_filter($assets, fn($a) => adpCategory($a['type']) === 'computer'));
    $equipment = array_values(array_filter($assets, fn($a) => adpCategory($a['type']) === 'equipment'));
    $maxRows   = max(count($computers), count($equipment), 1);

    for ($i = 0; $i < $maxRows; $i++) {
        $comp  = $computers[$i] ?? null;
        $equip = $equipment[$i] ?? null;

        fputcsv($out, [
            '',  // Position ID — blank for manual fill
            $i === 0 ? $user : '',
            '','','','','',
            $comp  ? $comp['name']   : '',
            '',
            $comp  ? ($comp['serial'] ?? '') : '',
            $comp  ? ($comp['purchase_date'] ?? '') : '',
            '',
            '','','','','',
            $equip ? $equip['name'] . ' (SN: ' . ($equip['serial'] ?? '') . ')' : '',
            '',
            $equip ? ($equip['purchase_date'] ?? '') : '',
            '',
            '','','','',
            '','','','','',
            '','','','',
        ]);
    }
}

fclose($out);
exit;
