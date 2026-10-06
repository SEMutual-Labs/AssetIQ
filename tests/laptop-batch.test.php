<?php
require __DIR__ . '/backend.test.php';
loadFunction('api/assets.php', 'createLaptopBatch');
loadFunction('api/assets.php', 'writeLog');

class BatchStatement extends PDOStatement {
    private array $parameters = [];
    public function __construct(private BatchDatabase $database, private string $sql) {}
    public function execute(?array $params = null): bool {
        $this->parameters = $params ?? [];
        if (str_starts_with($this->sql, 'INSERT INTO assets')) {
            if ($this->database->failSerial === $params['serial']) throw new PDOException('Injected insertion failure');
            $this->database->assets[$params['id']] = $params;
        } elseif (str_starts_with($this->sql, 'INSERT INTO asset_logs')) {
            $this->database->logs[] = $params;
        } elseif (str_starts_with($this->sql, 'INSERT INTO custom_field_values')) {
            $this->database->values[] = $params;
        }
        return true;
    }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array {
        return str_contains($this->sql, 'custom_field_defs') ? ['cf_warranty'] : array_keys($this->database->assets);
    }
    public function fetchColumn(int $column = 0): mixed {
        foreach ($this->database->assets as $id => $asset) {
            if (strtolower(trim($asset['serial'])) === strtolower($this->parameters[0])) return $id;
        }
        return false;
    }
}

class BatchDatabase extends PDO {
    public array $assets = [];
    public array $logs = [];
    public array $values = [];
    public ?string $failSerial = null;
    private bool $active = false;
    private array $snapshot = [];
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return new BatchStatement($this, $query);
    }
    public function beginTransaction(): bool {
        $this->snapshot = [$this->assets, $this->logs, $this->values];
        $this->active = true;
        return true;
    }
    public function inTransaction(): bool { return $this->active; }
    public function commit(): bool { $this->active = false; return true; }
    public function rollBack(): bool {
        [$this->assets, $this->logs, $this->values] = $this->snapshot;
        $this->active = false;
        return true;
    }
}

$database = new BatchDatabase();
$data = ['name' => 'Latitude 5440', 'type' => 'Laptop', 'serials' => ['SN001', 'SN002'], 'cost' => 500, 'custom_fields' => ['cf_warranty' => '2028-01-01']];
$created = createLaptopBatch($database, $data, 'Test user');
check(array_column($created, 'id') === ['SEM-NB01', 'SEM-NB02'], 'Each laptop receives a distinct generated asset ID');
check(count($database->assets) === 2 && count($database->logs) === 2 && count($database->values) === 2, 'Batch commits assets, audits and custom values together');
check($database->assets['SEM-NB02']['cost'] === 500.0, 'Shared cost applies per laptop');
check(!$database->inTransaction(), 'Successful batch closes the transaction');

try {
    createLaptopBatch($database, array_replace($data, ['serials' => ['NEW', 'sn001']]), 'Test user');
    throw new RuntimeException('Existing serial was accepted');
} catch (InvalidArgumentException $error) {
    check(count($database->assets) === 2 && !$database->inTransaction(), 'Existing serial rejects the entire batch without writes');
}

$database = new BatchDatabase();
$database->failSerial = 'SN002';
try {
    createLaptopBatch($database, $data, 'Test user');
    throw new RuntimeException('Injected failure was ignored');
} catch (PDOException $error) {
    check($database->assets === [] && $database->logs === [] && $database->values === [], 'Second insertion failure rolls back all batch data');
    check(!$database->inTransaction(), 'Failed batch closes the transaction');
}

try {
    createLaptopBatch($database, array_replace($data, ['custom_fields' => ['cf_unknown' => '2028-01-01']]), 'Test user');
    throw new RuntimeException('Unknown field was accepted');
} catch (InvalidArgumentException $error) {
    check($database->assets === [] && !$database->inTransaction(), 'Unknown custom fields are rejected before writing');
}