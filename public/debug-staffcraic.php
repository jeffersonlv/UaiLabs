<?php
if (($_GET['token'] ?? '') !== 'uailabs2026') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain');

$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) { echo "ERR: .env not found at $envFile\n"; exit; }

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, "\"' \t");
}

echo "DB_HOST=" . ($env['DB_HOST'] ?? '?') . "\n";
echo "DB_DATABASE=" . ($env['DB_DATABASE'] ?? '?') . "\n";
echo "DB_USERNAME=" . ($env['DB_USERNAME'] ?? '?') . "\n";
echo "PHP=" . phpversion() . "\n";

try {
    $dsn = "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
    $pdo = new PDO($dsn, $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? '');
    echo "DB: connected\n";
} catch (Exception $e) {
    echo "DB ERR: " . $e->getMessage() . "\n";
    exit;
}

$today = date('Y-m-d');
$user = $pdo->query("SELECT id,role,company_id FROM users WHERE username='staffcraic' LIMIT 1")->fetch(PDO::FETCH_OBJ);
if (!$user) { echo "ERR: staffcraic not found\n"; exit; }

echo "\nUSER id={$user->id} role={$user->role} company_id={$user->company_id}\n";

$st = $pdo->prepare("SELECT unit_id FROM user_units WHERE user_id=?");
$st->execute([$user->id]);
$userUnits = $st->fetchAll(PDO::FETCH_COLUMN);
echo "USER_UNITS: " . (count($userUnits) ? implode(', ', $userUnits) : '(vazio)') . "\n";

$st = $pdo->prepare("SELECT COUNT(*) FROM task_occurrences WHERE company_id=? AND DATE(period_start)=?");
$st->execute([$user->company_id, $today]);
echo "OCCURRENCES hoje: " . $st->fetchColumn() . "\n";

$st = $pdo->prepare("SELECT id,periodicity,title FROM activities WHERE company_id=? AND active=1");
$st->execute([$user->company_id]);
$acts = $st->fetchAll(PDO::FETCH_OBJ);
echo "ACTIVITIES ativas: " . count($acts) . "\n";
foreach ($acts as $a) {
    $st2 = $pdo->prepare("SELECT unit_id FROM activity_unit WHERE activity_id=?");
    $st2->execute([$a->id]);
    $au = $st2->fetchAll(PDO::FETCH_COLUMN);
    echo "  [{$a->id}] {$a->periodicity} units=[" . (count($au) ? implode(',', $au) : 'Geral') . "] {$a->title}\n";
}
