<?php
if (($_GET['token'] ?? '') !== 'uailabs2026') { http_response_code(403); die('Forbidden'); }

// Lê .env manualmente
$env = [];
foreach (file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
}

$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD']
);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

header('Content-Type: text/plain; charset=utf-8');

$today = date('Y-m-d');

$user = $pdo->query("SELECT * FROM users WHERE username='staffcraic' LIMIT 1")->fetch();
if (!$user) { echo "ERR: user not found\n"; exit; }

echo "USER id={$user->id} role={$user->role} company_id={$user->company_id}\n\n";

$st = $pdo->prepare("SELECT unit_id FROM user_units WHERE user_id=?");
$st->execute([$user->id]);
$userUnits = $st->fetchAll(PDO::FETCH_COLUMN);
echo "USER_UNITS (" . count($userUnits) . "): " . implode(', ', $userUnits) . "\n\n";

$st = $pdo->prepare("SELECT id,unit_id,activity_id,status FROM task_occurrences WHERE company_id=? AND DATE(period_start)=?");
$st->execute([$user->company_id, $today]);
$occs = $st->fetchAll();
echo "OCCURRENCES today={$today} total=" . count($occs) . "\n";
foreach ($occs as $o) {
    echo "  occ id={$o->id} unit_id=" . ($o->unit_id ?? 'NULL') . " act={$o->activity_id} status={$o->status}\n";
}

echo "\nACTIVITIES active company={$user->company_id}\n";
$st = $pdo->prepare("SELECT id,periodicity,title FROM activities WHERE company_id=? AND active=1");
$st->execute([$user->company_id]);
foreach ($st->fetchAll() as $a) {
    $st2 = $pdo->prepare("SELECT unit_id FROM activity_unit WHERE activity_id=?");
    $st2->execute([$a->id]);
    $au = implode(',', $st2->fetchAll(PDO::FETCH_COLUMN));
    echo "  act id={$a->id} period={$a->periodicity} units=[" . ($au ?: 'Geral') . "] title={$a->title}\n";
}
