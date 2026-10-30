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
            'status' => ['Active', 'Unassigned', 'In storage', 'Retired', 'Lost', 'repair'],
            'type' => ['Laptop', 'Desktop', 'Monitor', 'Dock'],
            'department' => ['Claims', 'Finance', 'Management', 'Marketing', 'Agent', 'IT', 'Underwriting', 'Not Assigned', 'No Longer At SEM'],
        }],
        'dateTime' => ['format' => $source === 'purchase_date' ? 'dateOnly' : 'dateTime'],
        default => [],
    };
    $definitions[] = ['name' => 'internal_' . $source, 'displayName' => $label, $kind => $facet,
        'required' => in_array($source, ['type', 'serial', 'status', 'purchase_date', 'cost', 'updated_at'], true)];
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
check($result['patch'] === ['internal_status' => 'Unassigned'] && count($result['warnings']) === 4, 'Blank values preserve existing fields while status reflects the missing owner');
check(sharepointTranslate(array_replace($asset, ['cost' => 0]), $fields, $columns)['patch']['internal_cost'] === 0.0, 'Zero is a valid price, not an empty value');
check(sharepointTranslate(array_replace($asset, ['department' => 'claims']), $fields, $columns)['patch']['internal_department'] === 'Claims', 'Department choice matching ignores casing');
rejects(fn() => sharepointTranslate(array_replace($asset, ['department' => 'Unknown Department']), $fields, $columns), 'Unknown department blocks translation rather than guessing');
check(sharepointTranslate(array_replace($asset, ['department' => 'No Longer At SEM']), $fields, $columns)['patch']['internal_department'] === 'No Longer At SEM', 'New department choice is discovered without an alias');
check(sharepointTranslate(array_replace($asset, ['assigned_to' => '  ']), $fields, $columns)['patch']['internal_status'] === 'Unassigned', 'Active asset without owner maps to Unassigned');
check(sharepointTranslate($asset, array_replace($fields, ['internal_status' => 'Unassigned']), $columns)['patch']['internal_status'] === 'Active', 'Assigning an owner changes Unassigned back to Active');
check(sharepointTranslate(array_replace($asset, ['status' => 'retired', 'assigned_to' => '']), $fields, $columns)['patch']['internal_status'] === 'Retired', 'Retired takes priority over missing owner');
check(sharepointTranslate(array_replace($asset, ['assigned_to' => '']), array_replace($fields, ['internal_status' => 'Lost']), $columns)['patch'] === [], 'Lost remains protected even when the owner is absent');
$oldColumns = $columns;
$oldColumns['status']['choice']['choices'] = ['Active', 'Retired'];
rejects(fn() => sharepointTranslate(array_replace($asset, ['assigned_to' => '']), $fields, $oldColumns), 'Missing Unassigned choice blocks rather than falling back');
check(sharepointTranslate(array_replace($asset, ['assigned_to' => '']), [], $columns)['patch']['internal_status'] === 'Unassigned', 'New unowned assets also use Unassigned');
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

$titleDefinitions = $definitions;
$titleDefinitions[0]['name'] = 'Title';
$titleDefinitions[0]['displayName'] = 'Title';
$titleColumns = sharepointColumns($titleDefinitions, ['id' => 'Title']);
check($titleColumns['id']['name'] === 'Title', 'Explicit internal-name mapping supports renamed built-in Title columns');
rejects(fn() => sharepointColumns($titleDefinitions), 'Title is never silently assumed to contain Asset IDs');
$titleItem = ['id' => '43', 'eTag' => '"1"', 'fields' => array_merge($fields, ['Title' => 'SEM-NB01', 'internal_serial' => 'OLD'])];
$titleRow = sharepointPreview([$asset], [$titleItem], $titleColumns, $titleDefinitions)[0];
check($titleRow['status'] === 'ready' && $titleRow['itemId'] === '43', 'Preview matches AssetIQ IDs against the configured Title field');
check(!array_key_exists('Title', $titleRow['patch']), 'Configured Asset ID column remains match-only');
rejects(fn() => sharepointColumns($titleDefinitions, ['id' => 'DoesNotExist']), 'Incorrect explicit names fail without falling back to another column');
rejects(fn() => sharepointColumns($definitions, ['typo' => 'Title']), 'Unknown source keys in column mappings fail explicitly');
rejects(fn() => sharepointColumns($definitions, ['id' => '']), 'Blank internal-name mappings are rejected');
rejects(fn() => sharepointColumns($definitions, ['id' => 123]), 'Non-text internal-name mappings are rejected');
rejects(fn() => sharepointColumns($definitions, ['serial' => 'internal_id']), 'Duplicate mapping targets are rejected');
$titleDefinitions[0]['readOnly'] = true;
rejects(fn() => sharepointColumns($titleDefinitions, ['id' => 'Title']), 'Explicit mappings retain column safety validation');

$item = ['id' => '42', 'eTag' => '"version-1"', 'fields' => array_replace($fields, ['internal_serial' => 'OLD', 'internal_id' => ' sem-nb01 '])];
$rows = sharepointPreview([$asset, array_replace($asset, ['id' => 'SEM-NB02'])], [$item], $columns, $definitions);
check($rows[0]['status'] === 'ready' && $rows[0]['operation'] === 'update' && $rows[1]['operation'] === 'create', 'Matching uses normalized Asset ID; missing assets are previewed for creation');
check(count($rows[0]['patch']) === 1 && $rows[0]['patch']['internal_serial'] === 'SN001', 'Only changed mapped columns are patched');
check(sharepointPreview([$asset], [$item, $item], $columns, $definitions)[0]['status'] === 'blocked', 'Duplicate SharePoint Asset IDs block writes');
check(sharepointPreview([$asset], [array_replace($item, ['eTag' => ''])], $columns, $definitions)[0]['status'] === 'blocked', 'Missing item version blocks unsafe writes');
check(sharepointPreview([$asset], [['id' => '42', 'fields' => $fields]], $columns, $definitions)[0]['status'] === 'unchanged', 'Unchanged rows need no item version or write');
check(sharepointPreview([array_replace($asset, ['type' => 'Unknown'])], [$item], $columns, $definitions)[0]['status'] === 'blocked', 'Invalid choice blocks the entire row');

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
rejects(fn() => sharepointApplyRow($graph, '/list', $rows[1], $asset, 'internal_updated_at', 'now'), 'Create candidates cannot be applied as updates');
check(count($calls) === 1, 'Rejected applies never contact Graph');
rejects(fn() => sharepointApplyRow(function (): array { throw new RuntimeException('HTTP 412'); }, '/list', $rows[0], $asset, 'internal_updated_at', 'now'), 'Remote conflicts propagate without a retry or success fallback');

class FixtureSharePointGraph extends SharePointGraph {
    public array $requests = [];
    public array $responses = [];
    public function __construct() {}
    public function call(string $method, string $path, ?array $body = null, ?string $etag = null): array {
        $this->requests[] = [$method, $path, $body, $etag];
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

$createRow = sharepointPreview([$asset], [], $columns, $definitions)[0];
check($createRow['status'] === 'ready' && $createRow['operation'] === 'create', 'Missing asset with complete data is ready to create');
check($createRow['patch']['internal_id'] === 'SEM-NB01', 'Create payload includes the Asset ID');
check(!isset($createRow['etag'], $createRow['itemId']), 'Creation needs no existing item ID or ETag');
$titleDefinitions[0]['readOnly'] = false;
$createTitle = sharepointPreview([$asset], [], sharepointColumns($titleDefinitions, ['id' => 'Title']), $titleDefinitions)[0];
check($createTitle['patch']['Title'] === 'SEM-NB01', 'Creation honors explicit Title mapping');
$missingRow = sharepointPreview([array_replace($asset, ['serial' => '', 'purchase_date' => null, 'cost' => null])], [], $columns, $definitions)[0];
check($missingRow['status'] === 'blocked' && !isset($missingRow['patch']), 'Missing required data blocks the entire new item');
check(str_contains($missingRow['error'], 'Serial Number') && str_contains($missingRow['error'], 'Purchase Date') && str_contains($missingRow['error'], 'Purchase Price'), 'All missing required fields are named together');
check(sharepointPreview([array_replace($asset, ['cost' => 0])], [], $columns, $definitions)[0]['status'] === 'ready', 'Zero purchase price satisfies required currency');
$optionalRow = sharepointPreview([array_replace($asset, ['assigned_to' => '', 'notes' => ''])], [], $columns, $definitions)[0];
check($optionalRow['status'] === 'ready' && !isset($optionalRow['patch']['internal_assigned_to']) && count($optionalRow['warnings']) === 2, 'Blank optional fields are omitted with creation-specific warnings');
check(str_contains($optionalRow['warnings'][0], 'optional column omitted'), 'Create warnings do not claim to preserve a nonexistent item');
foreach (['serial' => ' ', 'type' => '', 'status' => '', 'purchase_date' => '', 'cost' => null, 'id' => ''] as $source => $emptyValue) {
    check(sharepointPreview([array_replace($asset, [$source => $emptyValue])], [], $columns, $definitions)[0]['status'] === 'blocked', 'Required create value blocks when empty: ' . $source);
}
foreach (['type' => 'Unknown', 'department' => 'Unmapped', 'cost' => -1, 'purchase_date' => '2024-02-31', 'serial' => str_repeat('s', 256)] as $source => $invalidValue) {
    check(sharepointPreview([array_replace($asset, [$source => $invalidValue])], [], $columns, $definitions)[0]['status'] === 'blocked', 'Invalid create value blocks: ' . $source);
}
$extraRequired = array_merge($definitions, [['name' => 'InvoiceNumber', 'displayName' => 'Invoice Number', 'required' => true, 'text' => []]]);
$extraRow = sharepointPreview([$asset], [], $columns, $extraRequired)[0];
check($extraRow['status'] === 'blocked' && str_contains($extraRow['error'], 'Invoice Number (no AssetIQ mapping)'), 'Unmapped required columns block creation, not guessed defaults');
$systemDefinitions = array_merge($definitions, [
    ['name' => 'Created', 'required' => true, 'readOnly' => true],
    ['name' => 'Depreciation', 'required' => true, 'calculated' => []],
]);
check(sharepointPreview([$asset], [], $columns, $systemDefinitions)[0]['status'] === 'ready', 'Required server-managed and calculated columns do not block creation');
$updateMissing = sharepointPreview([array_replace($asset, ['serial' => '', 'purchase_date' => null, 'cost' => null])], [$item], $columns, $definitions)[0];
check($updateMissing['status'] === 'unchanged', 'Updates retain existing blank-value protection despite creation requirements');
$allChanges = $createRow['changes'];
check(count(array_filter($allChanges, fn(array $change): bool => $change['before'] !== null)) === 0, 'New-item preview shows values as additions, not replacements');

$createClient = new FixtureSharePointGraph();
$listPath = '/sites/site/lists/list';
$itemsPath = $listPath . '/items?$expand=fields&$top=200';
$createClient->responses = [$itemsPath => ['value' => []], $listPath . '/items' => ['id' => '44']];
$createResult = sharepointCreateRow($createClient, $listPath, $createRow, $asset, $columns, $definitions, [], 'internal_updated_at', '2026-10-29T20:00:00Z');
check($createResult['itemId'] === '44', 'Creation returns the new item ID for audit logging');
check(count($createClient->requests) === 2 && $createClient->requests[0][0] === 'GET' && $createClient->requests[1][0] === 'POST', 'Creation checks for existing IDs immediately before a single POST');
check($createClient->requests[1][1] === $listPath . '/items' && $createClient->requests[1][2] === ['fields' => $createResult['patch']], 'Creation uses the Graph items endpoint with a fields wrapper');
check($createResult['patch']['internal_updated_at'] === '2026-10-29T20:00:00Z' && $createResult['patch']['internal_cost'] === 750.0, 'Create payload contains typed price and generated sync time');
check($createClient->requests[1][3] === null, 'Create does not send an update ETag');
$createClient->requests = [];
$createClient->responses[$itemsPath] = ['value' => [], '@odata.nextLink' => 'https://graph.microsoft.com/v1.0/page-two'];
$createClient->responses['https://graph.microsoft.com/v1.0/page-two'] = ['value' => [['fields' => ['internal_id' => ' sem-nb01 ']]]];
rejects(fn() => sharepointCreateRow($createClient, $listPath, $createRow, $asset, $columns, $definitions, [], 'internal_updated_at', 'now'), 'An item created since preview blocks POST, including normalized IDs on later pages');
check(count(array_filter($createClient->requests, fn(array $request): bool => $request[0] !== 'GET')) === 0, 'Duplicate detection never silently updates or creates');
$createClient->requests = [];
rejects(fn() => sharepointCreateRow($createClient, $listPath, $createRow, array_replace($asset, ['cost' => 900]), $columns, $definitions, [], 'internal_updated_at', 'now'), 'Changed local snapshot blocks creation');
rejects(fn() => sharepointCreateRow($createClient, $listPath, $createRow, array_replace($asset, ['archived' => 1]), $columns, $definitions, [], 'internal_updated_at', 'now'), 'Archiving since preview blocks creation');
rejects(fn() => sharepointCreateRow($createClient, $listPath, $createRow, $asset, $columns, $extraRequired, [], 'internal_updated_at', 'now'), 'New required columns are revalidated before creation');
rejects(fn() => sharepointCreateRow($createClient, $listPath, $rows[0], $asset, $columns, $definitions, [], 'internal_updated_at', 'now'), 'An update plan cannot be treated as creation');
check($createClient->requests === [], 'Rejected snapshots and schemas make no remote writes');
$createClient->responses[$itemsPath] = ['value' => []];
$createClient->responses[$listPath . '/items'] = [];
rejects(fn() => sharepointCreateRow($createClient, $listPath, $createRow, $asset, $columns, $definitions, [], 'internal_updated_at', 'now'), 'Missing create response ID reports an unknown outcome rather than success');
check(count(array_filter($createClient->requests, fn(array $request): bool => $request[0] === 'POST')) === 1, 'Unknown create outcome is never automatically retried');

class LockFixtureStatement extends PDOStatement {
    public function __construct(private LockFixtureDatabase $db, private string $query) {}
    public function execute(?array $params = null): bool { $this->db->calls[] = [$this->query, $params]; return true; }
    public function fetchColumn(int $column = 0): mixed { return str_contains($this->query, 'GET_LOCK') ? $this->db->available : 1; }
}
class LockFixtureDatabase extends PDO {
    public array $calls = [];
    public int $available = 1;
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false { return new LockFixtureStatement($this, $query); }
}
$lockDb = new LockFixtureDatabase();
check(sharepointWithCreateLock($lockDb, $listPath, 'SEM-NB01', fn(): array => ['created']) === ['created'], 'Creation executes under a database advisory lock');
check(count($lockDb->calls) === 2 && str_contains($lockDb->calls[1][0], 'RELEASE_LOCK'), 'Create lock is released after success');
$firstLock = $lockDb->calls[0][1][0];
$lockDb->calls = [];
rejects(fn() => sharepointWithCreateLock($lockDb, $listPath, ' sem-nb01 ', function (): array { throw new RuntimeException('Timeout'); }), 'Failed creation propagates its error');
check(count($lockDb->calls) === 2 && $lockDb->calls[0][1][0] === $firstLock, 'Failed creation releases the same normalized asset lock');
$lockDb->available = 0;
$attempts = 0;
rejects(fn() => sharepointWithCreateLock($lockDb, $listPath, 'SEM-NB01', function () use (&$attempts): array { $attempts++; return []; }), 'Concurrent creation is explicitly blocked when the lock is unavailable');
check($attempts === 0, 'A refused lock never executes the create operation');
