<?php
if (($_GET['token'] ?? '') !== 'uailabs2026') { http_response_code(403); die('Forbidden'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

$today = now()->toDateString();
$user  = DB::table('users')->where('username', 'staffcraic')->first();

if (!$user) { echo "ERR: user not found\n"; exit; }

echo "USER id={$user->id} role={$user->role} company_id={$user->company_id}\n\n";

$userUnits = DB::table('user_units')->where('user_id', $user->id)->pluck('unit_id')->toArray();
echo "USER_UNITS (" . count($userUnits) . "): " . implode(', ', $userUnits) . "\n\n";

$occs = DB::table('task_occurrences')
    ->where('company_id', $user->company_id)
    ->whereDate('period_start', $today)
    ->select('id','unit_id','activity_id','status')
    ->get();
echo "OCCURRENCES today={$today} total=" . $occs->count() . "\n";
foreach ($occs as $o) {
    echo "  occ id={$o->id} unit_id=" . ($o->unit_id ?? 'NULL') . " act={$o->activity_id} status={$o->status}\n";
}

echo "\nACTIVITIES active company={$user->company_id}\n";
$acts = DB::table('activities')->where('company_id', $user->company_id)->where('active', 1)->get();
foreach ($acts as $a) {
    $au = DB::table('activity_unit')->where('activity_id', $a->id)->pluck('unit_id')->implode(',');
    echo "  act id={$a->id} periodicity={$a->periodicity} units=[" . ($au ?: 'Geral') . "] title={$a->title}\n";
}
