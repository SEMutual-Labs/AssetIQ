<?php
// ─────────────────────────────────────────────────────────────
//  AssetIQ — One-Time Excel Import Script
//  Upload this file alongside import_data.json to your server,
//  then visit: https://yoursite.com/import.php?run=1
//  DELETE BOTH FILES after import is complete!
// ─────────────────────────────────────────────────────────────

require_once __DIR__ . '/db.php';

$run    = isset($_GET['run']) && $_GET['run'] === '1';
$dryRun = !$run;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<title>AssetIQ Import</title>
<style>
  body { font-family: monospace; background: #0a0c10; color: #e8eaf0; padding: 24px; font-size: 13px; }
  h1   { color: #00e5ff; margin-bottom: 4px; }
  .ok  { color: #00ff88; }
  .warn{ color: #ff8c00; }
  .err { color: #ff3b5c; }
  .row { padding: 2px 0; border-bottom: 1px solid #1e2230; }
  .summary { background: #111318; border: 1px solid #1e2230; border-radius: 8px; padding: 16px; margin: 16px 0; }
  a    { color: #00e5ff; }
</style>
</head>
<body>
<h1>AssetIQ — Excel Import</h1>
<?php

$dataFile = __DIR__ . '/import_data.json';
if (!file_exists($dataFile)) {
    echo '<p class="err">ERROR: import_data.json not found. Upload it alongside this file.</p>';
    exit;
}

$assets = json_decode(file_get_contents($dataFile), true);
if (!$assets) {
    echo '<p class="err">ERROR: Could not parse import_data.json.</p>';
    exit;
}

echo "<p>Found <strong>" . count($assets) . "</strong> assets to import.</p>";

if ($dryRun) {
    echo '<p class="warn">⚠ DRY RUN — no data will be written. Add <strong>?run=1</strong> to the URL to execute.</p>';
} else {
    echo '<p class="ok">✓ LIVE RUN — writing to database...</p>';
}

$db = getDB();

// Get existing serials and IDs to avoid duplicates
$existingSerials = $db->query("SELECT serial FROM assets WHERE serial != '' AND serial IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
$existingSerials = array_map('strtolower', array_filter($existingSerials));
$existingIds     = $db->query("SELECT id FROM assets")->fetchAll(PDO::FETCH_COLUMN);
$existingIds     = array_map('strtolower', $existingIds);

$imported = 0;
$skipped  = 0;
$errors   = [];

echo '<div style="max-height:400px;overflow-y:auto;margin:16px 0">';

foreach ($assets as $a) {
    $serial = strtolower(trim($a['serial'] ?? ''));
    $name   = trim($a['name'] ?? '');
    $id     = trim($a['id'] ?? '');

    // Skip if no ID
    if (!$id) {
        echo "<div class='row warn'>SKIP (no ID): {$name}</div>";
        $skipped++;
        continue;
    }

    // Skip if ID already in DB
    if (in_array(strtolower($id), $existingIds)) {
        echo "<div class='row warn'>SKIP (duplicate ID): {$id} — {$name}</div>";
        $skipped++;
        continue;
    }

    // Skip if serial already in DB
    if ($serial && in_array($serial, $existingSerials)) {
        echo "<div class='row warn'>SKIP (duplicate serial): {$a['serial']} — {$name}</div>";
        $skipped++;
        continue;
    }

    $line = "<div class='row'><span class='ok'>" . ($dryRun ? 'WOULD INSERT' : 'INSERTED') . "</span> {$id} — {$name} [{$a['type']}]" . ($a['assigned_to'] ? " → {$a['assigned_to']}" : '') . "</div>";

    if (!$dryRun) {
        try {
            $stmt = $db->prepare("
                INSERT INTO assets (id, name, type, serial, assigned_to, department, status, purchase_date, end_of_life, cost, notes)
                VALUES (:id,:name,:type,:serial,:assigned_to,:dept,:status,:purchase_date,:end_of_life,NULL,:notes)
            ");
            $stmt->execute([
                'id'           => $id,
                'name'         => $name,
                'type'         => $a['type'],
                'serial'       => $a['serial'] ?? '',
                'assigned_to'  => $a['assigned_to'] ?? '',
                'dept'         => $a['department'] ?? '',
                'status'       => 'active',
                'purchase_date'=> $a['purchase_date'] ?? null,
                'end_of_life'  => $a['end_of_life'] ?? null,
                'notes'        => $a['notes'] ?? '',
            ]);
            if ($serial) $existingSerials[] = $serial;
            $imported++;
        } catch (Exception $e) {
            $line = "<div class='row err'>ERROR on {$name}: " . $e->getMessage() . "</div>";
            $errors[] = $name . ': ' . $e->getMessage();
        }
    } else {
        $imported++;
    }

    echo $line;
}

echo '</div>';
echo '<div class="summary">';
echo "<p><strong>Total:</strong> " . count($assets) . "</p>";
echo "<p class='ok'><strong>" . ($dryRun ? 'Would import' : 'Imported') . ":</strong> {$imported}</p>";
if ($skipped) echo "<p class='warn'><strong>Skipped (duplicates):</strong> {$skipped}</p>";
if ($errors)  echo "<p class='err'><strong>Errors:</strong> " . count($errors) . "</p>";
echo '</div>';

if ($dryRun) {
    echo '<p><a href="?run=1">✓ Looks good — run the real import now</a></p>';
} else {
    echo '<p class="ok">✓ Import complete! <a href="/">Go to AssetIQ</a></p>';
    echo '<p class="warn">⚠ <strong>Delete import.php and import_data.json from your server now!</strong></p>';
}
?>
</body>
</html>
