<?php

function loadFunction(string $file, string $name): void {
    $tokens = token_get_all(file_get_contents(__DIR__ . '/../' . $file));
    $capture = false;
    $foundBody = false;
    $depth = 0;
    $code = '';
    foreach ($tokens as $index => $token) {
        if (!$capture && is_array($token) && $token[0] === T_FUNCTION) {
            for ($next = $index + 1; $next < count($tokens); $next++) {
                if (is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) continue;
                $capture = is_array($tokens[$next]) && $tokens[$next][1] === $name;
                break;
            }
        }
        if (!$capture) continue;
        $code .= is_array($token) ? $token[1] : $token;
        if ($token === '{') { $foundBody = true; $depth++; }
        if ($token === '}') $depth--;
        if ($foundBody && $depth === 0) { eval($code); return; }
    }
    throw new RuntimeException('Function not found: ' . $name);
}

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: $message\n";
}

class IdStatement extends PDOStatement {
    public array $parameters = [];
    public function execute(?array $params = null): bool {
        $this->parameters = $params ?? [];
        return true;
    }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array {
        return ['SEM-NB01', 'SEM-NB09'];
    }
}

class IdDatabase extends PDO {
    public string $query = '';
    public IdStatement $statement;
    public function __construct() { $this->statement = new IdStatement(); }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        $this->query = $query;
        return $this->statement;
    }
}

loadFunction('api/assets.php', 'nextId');
loadFunction('api/assets.php', 'sanitizeDate');
loadFunction('api/assets.php', 'sanitizeAsset');
loadFunction('api/settings.php', 'publicSettings');
loadFunction('api/ai_price.php', 'validEstimate');

$database = new IdDatabase();
check(nextId($database, 'Laptop') === 'SEM-NB10', 'Next ID follows all reserved IDs');
check(!str_contains($database->query, 'status'), 'Retired IDs are not excluded');
check($database->statement->parameters === ['^SEM-NB[0-9]'], 'ID query remains parameterized');
check(!array_key_exists('eol_override', sanitizeAsset(['name' => 'Laptop', 'eol_override' => 1])), 'Legacy acknowledgement input is ignored');
$settings = publicSettings(['anthropic_api_key' => 'test-secret', 'alerts_enabled' => '1']);
check(!array_key_exists('anthropic_api_key', $settings), 'Settings response never includes stored API key');
check($settings['anthropic_api_key_configured'] === true, 'Configured flag replaces stored secret');
check(publicSettings([])['anthropic_api_key_configured'] === false, 'Missing API key is unconfigured');
check($settings['alerts_enabled'] === '1', 'Non-secret settings are preserved');

$estimate = ['low' => 10, 'high' => 30, 'midpoint' => 20, 'currency' => 'CAD', 'confidence' => 'high', 'reasoning' => 'Used resale value', 'caveat' => null];
check(validEstimate($estimate), 'Valid complete AI estimate is accepted');
check(!validEstimate(['low' => 10]), 'Incomplete AI estimate is rejected');
check(!validEstimate(array_replace($estimate, ['midpoint' => "0';alert(1)"])), 'Executable text in numeric estimate is rejected');
check(!validEstimate(array_replace($estimate, ['midpoint' => 100])), 'Inconsistent AI estimate range is rejected');
check(!validEstimate(array_replace($estimate, ['low' => -1])), 'Negative AI prices are rejected');
check(!validEstimate(array_replace($estimate, ['reasoning' => []])), 'Non-text AI explanation is rejected');