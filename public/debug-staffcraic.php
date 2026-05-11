<?php
if (($_GET['token'] ?? '') !== 'uailabs2026') { http_response_code(403); die('Forbidden'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

header('Content-Type: text/html; charset=utf-8');

$user = DB::table('users')->where('username', 'staffcraic')->first();
$today = now()->toDateString();

echo "<pre style='font-family:monospace;padding:1rem'>";

if (!$user) { echo "❌ Usuário staffcraic não encontrado.\n"; exit; }

echo "=== USUÁRIO ===\n";
echo "id: {$user->id} | name: {$user->name} | role: {$user->role} | company_id: {$user->company_id}\n\n";

// Unidades atribuídas ao user
$userUnits = DB::table('user_units')->where('user_id', $user->id)->pluck('unit_id')->toArray();
echo "=== UNIDADES ATRIBUÍDAS AO USER (" . count($userUnits) . ") ===\n";
if ($userUnits) {
    $units = DB::table('units')->whereIn('id', $userUnits)->get();
    foreach ($units as $u) echo "  - id:{$u->id} {$u->name} (active:{$u->active})\n";
} else {
    echo "  (nenhuma)\n";
}

echo "\n=== OCCURRENCES HOJE ({$today}) DA EMPRESA {$user->company_id} ===\n";
$occs = DB::table('task_occurrences')
    ->where('company_id', $user->company_id)
    ->whereDate('period_start', $today)
    ->get();
echo "Total: " . count($occs) . "\n";
$unitIds = array_unique(array_column((array)$occs, 'unit_id'));
echo "unit_ids distintos: " . implode(', ', array_map(fn($v) => $v ?? 'NULL', $unitIds)) . "\n\n";

echo "=== ATIVIDADES ATIVAS DA EMPRESA ===\n";
$acts = DB::table('activities')->where('company_id', $user->company_id)->where('active', 1)->get();
echo "Total ativas: " . count($acts) . "\n";
foreach ($acts as $a) {
    $actUnits = DB::table('activity_unit')->where('activity_id', $a->id)->pluck('unit_id')->toArray();
    echo "  - [{$a->id}] {$a->title} | periodicity:{$a->periodicity} | units: " . (count($actUnits) ? implode(',', $actUnits) : 'Geral') . "\n";
}

echo "\n=== DIAGNÓSTICO ===\n";
if (empty($userUnits)) {
    echo "❌ User não tem unidades em user_units — visibleUnitIds() retorna [] — whereIn vazio — sem tarefas.\n";
} else {
    $occUnitIds = array_filter(array_unique(array_column((array)$occs, 'unit_id')));
    $match = array_intersect($userUnits, $occUnitIds);
    if (empty($match) && !in_array(null, array_column((array)$occs, 'unit_id'))) {
        echo "❌ As unidades do user (" . implode(',', $userUnits) . ") não batem com as unit_ids das occurrences (" . implode(',', $occUnitIds) . ").\n";
    } elseif (count($occs) === 0) {
        echo "❌ Nenhuma occurrence gerada hoje. Verifique se há atividades 'diario' ativas.\n";
    } else {
        echo "✅ Unidades batem — deveria funcionar.\n";
    }
}

echo "</pre>";
