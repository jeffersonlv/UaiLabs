<?php
if (($_GET['token'] ?? '') !== 'uailabs2026') { http_response_code(403); die('Forbidden'); }
error_reporting(E_ALL); ini_set('display_errors', 1);

$envFile = __DIR__ . '/../.env';
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, "\"' \t");
}

$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};port={$env['DB_PORT']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'] ?? ''
);

$today = date('Y-m-d');
$out = "today=$today\n";

$user = $pdo->query("SELECT id,role,company_id FROM users WHERE username='staffcraic' LIMIT 1")->fetch(PDO::FETCH_OBJ);
if (!$user) { file_put_contents(__DIR__.'/dbg.txt', "staffcraic not found\n"); header('Location: dbg.txt'); exit; }

$out .= "USER id={$user->id} role={$user->role} company_id={$user->company_id}\n";

$st = $pdo->prepare("SELECT unit_id FROM user_units WHERE user_id=?");
$st->execute([$user->id]);
$userUnits = $st->fetchAll(PDO::FETCH_COLUMN);
$out .= "USER_UNITS: " . (count($userUnits) ? implode(', ', $userUnits) : '(vazio)') . "\n";

$st = $pdo->prepare("SELECT id,unit_id,activity_id,status FROM task_occurrences WHERE company_id=? AND DATE(period_start)=?");
$st->execute([$user->company_id, $today]);
$occs = $st->fetchAll(PDO::FETCH_OBJ);
$out .= "OCCURRENCES=" . count($occs) . "\n";
foreach ($occs as $o) $out .= "  occ={$o->id} unit=" . ($o->unit_id ?? 'NULL') . " act={$o->activity_id} status={$o->status}\n";

$st = $pdo->prepare("SELECT id,periodicity,title FROM activities WHERE company_id=? AND active=1");
$st->execute([$user->company_id]);
$acts = $st->fetchAll(PDO::FETCH_OBJ);
$out .= "ACTIVITIES=" . count($acts) . "\n";
foreach ($acts as $a) {
    $st2 = $pdo->prepare("SELECT unit_id FROM activity_units WHERE activity_id=?");
    $st2->execute([$a->id]);
    $au = $st2->fetchAll(PDO::FETCH_COLUMN);
    $out .= "  act={$a->id} period={$a->periodicity} units=[" . (count($au) ? implode(',', $au) : 'Geral') . "] {$a->title}\n";
}

file_put_contents(__DIR__ . '/dbg.txt', $out);
header('Location: dbg.txt');
