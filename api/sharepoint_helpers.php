<?php

class SharePointGraph {
    private string $token;

    public function __construct() {
        foreach (['SHAREPOINT_TENANT_ID', 'SHAREPOINT_CLIENT_ID', 'SHAREPOINT_CLIENT_SECRET'] as $name) {
            if (!defined($name) || !is_string(constant($name)) || trim(constant($name)) === '') {
                throw new RuntimeException('Configure ' . $name . ' in the private config before using SharePoint sync.');
            }
        }
        $response = $this->request(
            'POST',
            'https://login.microsoftonline.com/' . rawurlencode(SHAREPOINT_TENANT_ID) . '/oauth2/v2.0/token',
            http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => SHAREPOINT_CLIENT_ID,
                'client_secret' => SHAREPOINT_CLIENT_SECRET,
                'scope' => 'https://graph.microsoft.com/.default',
            ]),
            ['Content-Type: application/x-www-form-urlencoded']
        );
        if (empty($response['access_token'])) throw new RuntimeException('Microsoft did not return a SharePoint access token.');
        $this->token = $response['access_token'];
    }

    private function request(string $method, string $url, ?string $body, array $headers): array {
        if (!function_exists('curl_init')) throw new RuntimeException('SharePoint sync requires the PHP cURL extension.');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            error_log('AssetIQ SharePoint transport error: ' . $error);
            throw new RuntimeException('SharePoint connection failed. If applying, the outcome may be unknown; preview again before retrying.');
        }
        if ($status < 200 || $status >= 300) {
            $hint = match ($status) {
                401, 403 => 'Check application consent and the explicit site/list write grant.',
                404 => 'Check the configured site/list and application access.',
                412 => 'The SharePoint item changed after preview. Preview again.',
                429 => 'Microsoft is throttling requests. Wait, then preview again.',
                default => 'Check the list schema and Microsoft service availability; preview again before retrying.',
            };
            error_log('AssetIQ SharePoint request failed: HTTP ' . $status);
            throw new RuntimeException('Microsoft request failed (HTTP ' . $status . '). ' . $hint);
        }
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('Microsoft returned an invalid response; preview again before retrying.', 0, $error);
        }
        if (!is_array($decoded)) throw new RuntimeException('Microsoft returned an unexpected response.');
        return $decoded;
    }

    public function call(string $method, string $path, ?array $body = null, ?string $etag = null): array {
        $url = str_starts_with($path, 'https://') ? $path : 'https://graph.microsoft.com/v1.0' . $path;
        if (!str_starts_with($url, 'https://graph.microsoft.com/v1.0/')) {
            throw new RuntimeException('Refusing an unexpected Microsoft Graph pagination URL.');
        }
        $headers = ['Authorization: Bearer ' . $this->token, 'Accept: application/json'];
        if ($body !== null) $headers[] = 'Content-Type: application/json';
        if ($etag !== null) {
            if (strpbrk($etag, "\r\n") !== false) throw new RuntimeException('Invalid SharePoint item version.');
            $headers[] = 'If-Match: ' . $etag;
        }
        return $this->request($method, $url, $body === null ? null : json_encode($body, JSON_THROW_ON_ERROR), $headers);
    }

    public function collection(string $path): array {
        $all = [];
        for ($page = 0; $page < 200; $page++) {
            $data = $this->call('GET', $path);
            if (!isset($data['value']) || !is_array($data['value'])) throw new RuntimeException('Microsoft returned an invalid collection.');
            array_push($all, ...$data['value']);
            $path = $data['@odata.nextLink'] ?? '';
            if ($path === '') return $all;
        }
        throw new RuntimeException('SharePoint pagination limit exceeded. No preview was saved.');
    }

    public function listPath(): string {
        $site = $this->call('GET', '/sites/semutual.sharepoint.com:/sites/AssetManagement?$select=id');
        if (empty($site['id'])) throw new RuntimeException('The AssetManagement site could not be resolved.');
        $base = '/sites/' . rawurlencode($site['id']) . '/lists';
        if (defined('SHAREPOINT_LIST_ID') && SHAREPOINT_LIST_ID !== '') return $base . '/' . rawurlencode(SHAREPOINT_LIST_ID);
        $matches = array_values(array_filter($this->collection($base . '?$select=id,displayName'), fn(array $list): bool => ($list['displayName'] ?? '') === 'Asset Register'));
        if (count($matches) !== 1) throw new RuntimeException('Could not uniquely resolve Asset Register. Set SHAREPOINT_LIST_ID in the private config.');
        return $base . '/' . rawurlencode($matches[0]['id']);
    }
}

function sharepointMapping(): array {
    return [
        'id' => ['Asset ID', 'text'],
        'type' => ['Asset Type', 'choice'],
        'serial' => ['Serial Number', 'text'],
        'status' => ['Status', 'choice'],
        'name' => ['Make/Model', 'text'],
        'assigned_to' => ['Current Owner', 'text'],
        'department' => ['Department', 'choice'],
        'purchase_date' => ['Purchase Date', 'dateTime'],
        'cost' => ['Purchase Price', 'currency'],
        'notes' => ['Notes', 'text'],
        'updated_at' => ['Last Updated', 'dateTime'],
    ];
}

function sharepointColumns(array $definitions, array $columnMap = []): array {
    $columns = [];
    foreach ($columnMap as $source => $name) {
        if (!array_key_exists($source, sharepointMapping()) || !is_string($name) || trim($name) === '') {
            throw new RuntimeException('SHAREPOINT_COLUMN_MAP must map supported AssetIQ field keys to nonempty SharePoint internal names.');
        }
    }
    $used = [];
    foreach (sharepointMapping() as $source => [$label, $kind]) {
        $internalName = $columnMap[$source] ?? null;
        $matches = array_values(array_filter($definitions, fn(array $column): bool => $internalName !== null
            ? ($column['name'] ?? '') === $internalName
            : ($column['displayName'] ?? '') === $label));
        if (count($matches) !== 1) {
            throw new RuntimeException('Missing or ambiguous SharePoint column: ' . $label
                . ($internalName !== null ? ' (configured internal name: ' . $internalName . ').' : '. Configure SHAREPOINT_COLUMN_MAP with the verified internal name if the display label differs.'));
        }
        $column = $matches[0];
        if (empty($column['name']) || !isset($column[$kind]) || !empty($column['readOnly']) || isset($column['calculated'])) {
            throw new RuntimeException('SharePoint column has an unsupported type or is read-only: ' . $label);
        }
        if ($kind === 'choice' && ($column['choice']['displayAs'] ?? '') === 'checkBoxes') {
            throw new RuntimeException('Multi-select choices are not supported: ' . $label);
        }
        if ($kind === 'text' && !empty($column['text']['allowMultipleLines']) && ($column['text']['textType'] ?? 'plain') !== 'plain') {
            throw new RuntimeException('Rich-text SharePoint columns are not supported: ' . $label);
        }
        if (isset($used[$column['name']])) throw new RuntimeException('Multiple AssetIQ fields target the same SharePoint column: ' . $column['name']);
        $used[$column['name']] = true;
        $columns[$source] = $column;
    }
    return $columns;
}

function sharepointChoice(string $value, array $column): string {
    $matches = array_values(array_filter($column['choice']['choices'] ?? [], fn(string $choice): bool => strcasecmp(trim($choice), trim($value)) === 0));
    if (count($matches) !== 1) throw new InvalidArgumentException('No unique allowed choice for ' . $column['displayName'] . ': ' . $value);
    return $matches[0];
}

function sharepointSameValue(mixed $before, mixed $after, string $kind, array $column): bool {
    if ($kind === 'currency') return is_numeric($before) && (float)$before === (float)$after;
    if ($kind === 'dateTime') {
        if (!is_string($before) || $before === '') return false;
        if (($column['dateTime']['format'] ?? '') === 'dateOnly') return substr($before, 0, 10) === substr($after, 0, 10);
        try {
            return (new DateTimeImmutable($before))->getTimestamp() === (new DateTimeImmutable($after))->getTimestamp();
        } catch (Exception $error) {
            throw new InvalidArgumentException('Invalid existing SharePoint date in ' . $column['displayName'], 0, $error);
        }
    }
    return (string)($before ?? '') === (string)$after;
}

function sharepointTranslate(array $asset, array $fields, array $columns, array $valueMaps = [], bool $creating = false): array {
    $patch = [];
    $changes = [];
    $warnings = [];
    foreach (sharepointMapping() as $source => [$label, $kind]) {
        if (($source === 'id' && !$creating) || $source === 'updated_at') continue;
        $column = $columns[$source];
        $internal = $column['name'];
        $before = $fields[$internal] ?? null;
        $value = $asset[$source] ?? null;
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $warnings[] = $label . ($creating
                ? ': empty AssetIQ value; optional column omitted.'
                : ': empty AssetIQ value; existing SharePoint value preserved.');
            continue;
        }
        if ($source === 'status') {
            if (!in_array($value, ['active', 'retired'], true)) throw new InvalidArgumentException('Unsupported AssetIQ status.');
            if ($before !== null && trim((string)$before) !== '' && !in_array(strtolower(trim((string)$before)), ['active', 'retired'], true)) {
                $warnings[] = 'Status preserved (' . $before . '): AssetIQ has no equivalent.';
                continue;
            }
            $value = $value === 'active' ? 'Active' : 'Retired';
        }
        if ($kind === 'choice') {
            $value = $valueMaps[$source][$value] ?? $value;
            $value = sharepointChoice($value, $column);
        } elseif ($kind === 'dateTime') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$value, new DateTimeZone('UTC'));
            if (!$date || $date->format('Y-m-d') !== $value) throw new InvalidArgumentException('Invalid AssetIQ date for ' . $label);
            $value = $date->format('Y-m-d\T00:00:00\Z');
        } elseif ($kind === 'currency') {
            if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < 0) throw new InvalidArgumentException('Invalid purchase price.');
            $value = (float)$value;
        } else {
            $value = (string)$value;
            $limit = $column['text']['maxLength'] ?? null;
            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if ($limit !== null && $length > $limit) throw new InvalidArgumentException($label . ' exceeds the SharePoint column length.');
        }
        if (!sharepointSameValue($before, $value, $kind, $column)) {
            $patch[$internal] = $value;
            $changes[] = ['column' => $label, 'before' => $before, 'after' => $value];
        }
    }
    return ['patch' => $patch, 'changes' => $changes, 'warnings' => $warnings];
}

function sharepointAssetHash(array $asset): string {
    return hash('sha256', json_encode($asset, JSON_THROW_ON_ERROR));
}

function sharepointCreateFields(array $asset, array $columns, array $definitions, array $valueMaps = []): array {
    $sources = [];
    foreach ($columns as $source => $column) $sources[$column['name']] = $source;
    $missing = [];
    foreach ($definitions as $column) {
        if (empty($column['required']) || !empty($column['readOnly']) || isset($column['calculated'])) continue;
        $source = $sources[$column['name']] ?? null;
        if ($source === 'updated_at') continue;
        $value = $source === null ? null : ($asset[$source] ?? null);
        if ($value === null || (is_string($value) && trim($value) === '')) {
            $missing[] = ($column['displayName'] ?? $column['name'])
                . ($source === null ? ' (no AssetIQ mapping)' : '');
        }
    }
    if (trim((string)($asset['id'] ?? '')) === '') $missing[] = 'Asset ID';
    if ($missing !== []) {
        throw new InvalidArgumentException('Cannot create: missing required values: ' . implode(', ', array_unique($missing))
            . '. Fill mapped values in AssetIQ; unmapped required columns need a supported mapping before creation.');
    }
    return sharepointTranslate($asset, [], $columns, $valueMaps, true);
}

function sharepointPreview(array $assets, array $items, array $columns, array $definitions, array $valueMaps = []): array {
    $index = [];
    $key = $columns['id']['name'];
    foreach ($items as $item) {
        $id = strtolower(trim((string)($item['fields'][$key] ?? '')));
        if ($id !== '') $index[$id][] = $item;
    }
    $rows = [];
    foreach ($assets as $asset) {
        $row = ['assetId' => $asset['id'], 'status' => 'blocked', 'operation' => 'update', 'changes' => [], 'warnings' => []];
        $matches = $index[strtolower(trim($asset['id']))] ?? [];
        if (count($matches) > 1) {
            $row['status'] = 'blocked';
            $row['error'] = 'Multiple SharePoint rows have this Asset ID.';
        } elseif ($matches === []) {
            $row['operation'] = 'create';
            try {
                $row = array_merge($row, sharepointCreateFields($asset, $columns, $definitions, $valueMaps));
                $row['status'] = 'ready';
                $row['assetHash'] = sharepointAssetHash($asset);
            } catch (InvalidArgumentException $error) {
                $row['status'] = 'blocked';
                $row['error'] = $error->getMessage();
            }
        } elseif (count($matches) === 1) {
            $item = $matches[0];
            try {
                $translated = sharepointTranslate($asset, $item['fields'] ?? [], $columns, $valueMaps);
                $row = array_merge($row, $translated);
                $row['status'] = $row['patch'] === [] ? 'unchanged' : 'ready';
                $row['itemId'] = $item['id'];
                $row['etag'] = $item['eTag'] ?? $item['@odata.etag'] ?? '';
                if ($row['status'] === 'ready' && $row['etag'] === '') throw new InvalidArgumentException('SharePoint item version missing; cannot safely update.');
                $row['assetHash'] = sharepointAssetHash($asset);
            } catch (InvalidArgumentException $error) {
                $row['status'] = 'blocked';
                $row['error'] = $error->getMessage();
                unset($row['patch']);
            }
        }
        $rows[] = $row;
    }
    return $rows;
}

function sharepointApplyRow(callable $graph, string $listPath, array $row, array $asset, string $lastUpdatedColumn, string $syncTime): array {
    if (($row['status'] ?? '') !== 'ready' || ($row['operation'] ?? 'update') !== 'update' || empty($row['patch']) || empty($row['etag'])) throw new InvalidArgumentException('This preview row is not ready to apply.');
    if (!hash_equals($row['assetHash'], sharepointAssetHash($asset))) throw new RuntimeException('AssetIQ record changed after preview. Preview again.');
    $patch = $row['patch'];
    $patch[$lastUpdatedColumn] = $syncTime;
    $graph('PATCH', $listPath . '/items/' . rawurlencode($row['itemId']) . '/fields', $patch, $row['etag']);
    return $patch;
}

function sharepointCreateRow(SharePointGraph $graph, string $listPath, array $row, array $asset, array $columns, array $definitions, array $valueMaps, string $lastUpdatedColumn, string $syncTime): array {
    if (($row['status'] ?? '') !== 'ready' || ($row['operation'] ?? '') !== 'create' || empty($row['patch'])) {
        throw new InvalidArgumentException('This preview row is not ready to create.');
    }
    if (!hash_equals($row['assetHash'], sharepointAssetHash($asset))) throw new RuntimeException('AssetIQ record changed after preview. Preview again.');
    $translated = sharepointCreateFields($asset, $columns, $definitions, $valueMaps);
    if ($translated['patch'] !== $row['patch'] || $columns['updated_at']['name'] !== $lastUpdatedColumn) {
        throw new RuntimeException('SharePoint mapping or schema changed after preview. Preview again.');
    }
    $key = $columns['id']['name'];
    foreach ($graph->collection($listPath . '/items?$expand=fields&$top=200') as $item) {
        if (strcasecmp(trim((string)($item['fields'][$key] ?? '')), trim($asset['id'])) === 0) {
            throw new RuntimeException('This Asset ID now exists in SharePoint. Nothing was created or updated. Preview again.');
        }
    }
    $patch = $row['patch'];
    $patch[$lastUpdatedColumn] = $syncTime;
    $created = $graph->call('POST', $listPath . '/items', ['fields' => $patch]);
    if (empty($created['id']) || !is_string($created['id'])) {
        throw new RuntimeException('SharePoint may have created the item, but did not return its ID. Preview again before retrying.');
    }
    return ['itemId' => $created['id'], 'patch' => $patch];
}

function sharepointWithCreateLock(PDO $db, string $listPath, string $assetId, callable $create): array {
    $lockName = hash('sha256', 'sharepoint-create:' . $listPath . ':' . strtolower(trim($assetId)));
    $lock = $db->prepare('SELECT GET_LOCK(?, 0)');
    $lock->execute([$lockName]);
    if ((int)$lock->fetchColumn() !== 1) throw new RuntimeException('Another creation for this Asset ID is in progress, or the database cannot acquire the sync lock. Preview again later.');
    try {
        return $create();
    } finally {
        $release = $db->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
        if ((int)$release->fetchColumn() !== 1) error_log('AssetIQ SharePoint creation lock could not be released: ' . $lockName);
    }
}
