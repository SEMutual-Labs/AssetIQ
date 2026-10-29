<?php

require_once __DIR__ . '/../api/sharepoint_helpers.php';

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: $message\n";
}

function rejects(callable $run, string $message): void {
    try { $run(); } catch (RuntimeException | InvalidArgumentException $error) {
        echo "PASS: $message\n";
        return;
    }
    throw new RuntimeException($message);
}

$definitions = [];
foreach (sharepointMapping() as $source => [$label, $kind]) {
    $facet = match ($kind) {
        'text' => ['maxLength' => $source === 'notes' ? 10000 : 255],
        'choice' => ['choices' => match ($source) {
            'status' => ['Active', 'In storage', 'Retired', 'Lost', 'repair'],
            'type' => ['Laptop', 'Desktop', 'Monitor', 'Dock'],
            'department' => ['Claims', 'Finance', 'Management', 'Marketing', 'Agent', 'IT', 'Underwriting', 'Not Assigned'],
        }],
        'dateTime' => ['format' => $source === 'purchase_date' ? 'dateOnly' : 'dateTime'],
        default => [],
    };
    $definitions[] = ['name' => 'internal_' . $source, 'displayName' => $label, $kind => $facet];
}
$columns = sharepointColumns($definitions);
check($columns['id']['name'] === 'internal_id', 'Internal names are discovered from display names, not guessed');
$asset = [
    'id' => 'SEM-NB01', 'type' => 'Laptop', 'serial' => 'SN001', 'name' => 'Latitude 5440',
    'assigned_to' => 'Test User', 'department' => 'IT', 'status' => 'active',
    'purchase_date' => '2024-01-01', 'cost' => '750.00', 'notes' => 'Inventory note',
    'updated_at' => '2026-10-29 12:00:00', 'archived' => 0,
];
$translated = sharepointTranslate($asset, [], $columns);
check($translated['patch']['internal_status'] === 'Active', 'Active status is translated to the accepted label');
check($translated['patch']['internal_assigned_to'] === 'Test User', 'Owner is translated as text');
check($translated['patch']['internal_name'] === 'Latitude 5440', 'Name maps to Make/Model');
check($translated['patch']['internal_cost'] === 750.0, 'Currency is numeric');
check($translated['patch']['internal_purchase_date'] === '2024-01-01T00:00:00Z', 'Dates use Graph ISO format');
check(!array_key_exists('internal_id', $translated['patch']) && !array_key_exists('internal_updated_at', $translated['patch']), 'Keys and local timestamps are not overwritten');
check(sharepointTranslate(array_replace($asset, ['status' => 'retired']), [], $columns)['patch']['internal_status'] === 'Retired', 'Retired status is translated');

$fields = $translated['patch'] + ['internal_id' => $asset['id'], 'internal_updated_at' => '2026-01-01T00:00:00Z', 'CalculatedDepreciation' => 123];
$fields['internal_cost'] = 750;
check(sharepointTranslate($asset, $fields, $columns)['patch'] === [], 'Equivalent numeric/date values do not produce spurious changes');
foreach (['In storage', 'Lost', 'repair'] as $status) {
    $result = sharepointTranslate($asset, array_replace($fields, ['internal_status' => $status]), $columns);
    check(!isset($result['patch']['internal_status']) && count($result['warnings']) === 1, 'SharePoint-only status is preserved: ' . $status);
}
$empty = array_replace($asset, ['assigned_to' => '', 'cost' => null, 'purchase_date' => null, 'notes' => '']);
$result = sharepointTranslate($empty, $fields, $columns);
check($result['patch'] === [] && count($result['warnings']) === 4, 'Blank/null values preserve existing SharePoint fields with explicit warnings');
check(sharepointTranslate(array_replace($asset, ['cost' => 0]), $fields, $columns)['patch']['internal_cost'] === 0.0, 'Zero is a valid price, not an empty value');
check(sharepointTranslate(array_replace($asset, ['department' => 'claims']), $fields, $columns)['patch']['internal_department'] === 'Claims', 'Department choice matching ignores casing');
rejects(fn() => sharepointTranslate(array_replace($asset, ['department' => 'No Longer At SEM']), $fields, $columns), 'Unknown department blocks translation rather than guessing');
rejects(fn() => sharepointTranslate(array_replace($asset, ['purchase_date' => '2024-02-31']), $fields, $columns), 'Invalid dates are rejected');
rejects(fn() => sharepointTranslate(array_replace($asset, ['cost' => -5]), $fields, $columns), 'Negative price is rejected');
rejects(fn() => sharepointTranslate(array_replace($asset, ['serial' => str_repeat('x', 256)]), $fields, $columns), 'Column length is respected');
$dock = array_replace($asset, ['type' => 'Docking Station']);
rejects(fn() => sharepointTranslate($dock, $fields, $columns), 'Unmapped asset type is rejected');
check(sharepointTranslate($dock, $fields, $columns, ['type' => ['Docking Station' => 'Dock']])['patch']['internal_type'] === 'Dock', 'Explicit type aliases translate existing values');
rejects(fn() => sharepointColumns(array_slice($definitions, 1)), 'Missing column fails explicitly');
rejects(fn() => sharepointColumns(array_merge($definitions, [$definitions[0]])), 'Ambiguous column fails explicitly');
$invalidColumns = $definitions;
$invalidColumns[3]['readOnly'] = true;
rejects(fn() => sharepointColumns($invalidColumns), 'Read-only destinations are rejected');

$item = ['id' => '42', 'eTag' => '"version-1"', 'fields' => array_replace($fields, ['internal_serial' => 'OLD', 'internal_id' => ' sem-nb01 '])];
$rows = sharepointPreview([$asset, array_replace($asset, ['id' => 'SEM-NB02'])], [$item], $columns);
check($rows[0]['status'] === 'ready' && $rows[1]['status'] === 'unmatched', 'Matching uses normalized Asset ID; unmatched assets are never created');
check(count($rows[0]['patch']) === 1 && $rows[0]['patch']['internal_serial'] === 'SN001', 'Only changed mapped columns are patched');
check(sharepointPreview([$asset], [$item, $item], $columns)[0]['status'] === 'blocked', 'Duplicate SharePoint Asset IDs block writes');
check(sharepointPreview([$asset], [array_replace($item, ['eTag' => ''])], $columns)[0]['status'] === 'blocked', 'Missing item version blocks unsafe writes');
check(sharepointPreview([$asset], [['id' => '42', 'fields' => $fields]], $columns)[0]['status'] === 'unchanged', 'Unchanged rows need no item version or write');
check(sharepointPreview([array_replace($asset, ['type' => 'Unknown'])], [$item], $columns)[0]['status'] === 'blocked', 'Invalid choice blocks the entire row');

$calls = [];
$graph = function (string $method, string $path, array $patch, string $etag) use (&$calls): array {
    $calls[] = compact('method', 'path', 'patch', 'etag');
    return [];
};
$patch = sharepointApplyRow($graph, '/sites/site/lists/list', $rows[0], $asset, 'internal_updated_at', '2026-10-29T17:00:00Z');
check(count($calls) === 1 && $calls[0]['method'] === 'PATCH' && $calls[0]['path'] === '/sites/site/lists/list/items/42/fields', 'Apply issues exactly one PATCH to an existing item');
check($calls[0]['etag'] === '"version-1"', 'Apply forwards the preview ETag for concurrency protection');
check($patch === ['internal_serial' => 'SN001', 'internal_updated_at' => '2026-10-29T17:00:00Z'], 'Apply writes only preview changes plus sync timestamp');
rejects(fn() => sharepointApplyRow($graph, '/list', $rows[0], array_replace($asset, ['name' => 'Changed']), 'internal_updated_at', 'now'), 'Changed local snapshot is rejected');
rejects(fn() => sharepointApplyRow($graph, '/list', $rows[0], array_replace($asset, ['archived' => 1]), 'internal_updated_at', 'now'), 'Archiving after preview is detected');
rejects(fn() => sharepointApplyRow($graph, '/list', $rows[1], $asset, 'internal_updated_at', 'now'), 'Unmatched rows cannot be applied');
check(count($calls) === 1, 'Rejected applies never contact Graph');
rejects(fn() => sharepointApplyRow(function (): array { throw new RuntimeException('HTTP 412'); }, '/list', $rows[0], $asset, 'internal_updated_at', 'now'), 'Remote conflicts propagate without a retry or success fallback');

class FixtureSharePointGraph extends SharePointGraph {
    public array $requests = [];
    public array $responses = [];
    public function __construct() {}
    public function call(string $method, string $path, ?array $body = null, ?string $etag = null): array {
        $this->requests[] = [$method, $path];
        if (!isset($this->responses[$path])) throw new RuntimeException('Unexpected fixture request.');
        return $this->responses[$path];
    }
}
$client = new FixtureSharePointGraph();
$client->responses = [
    '/collection' => ['value' => [['id' => '1']], '@odata.nextLink' => 'https://graph.microsoft.com/v1.0/next'],
    'https://graph.microsoft.com/v1.0/next' => ['value' => [['id' => '2']]],
];
check($client->collection('/collection') === [['id' => '1'], ['id' => '2']], 'Collection reads include every paginated item');
check(count($client->requests) === 2, 'Pagination follows each continuation exactly once');
$client->responses = ['/broken' => []];
rejects(fn() => $client->collection('/broken'), 'Malformed collections cannot silently produce an empty preview');
$client->responses = ['/loop' => ['value' => [], '@odata.nextLink' => '/loop']];
rejects(fn() => $client->collection('/loop'), 'Pagination limit explicitly fails instead of returning partial data');
