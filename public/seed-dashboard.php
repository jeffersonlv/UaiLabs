<?php
if (($_GET['token'] ?? '') !== 'uailabs2026') { http_response_code(403); die('Forbidden'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Activity;
use App\Models\TaskOccurrence;
use App\Models\TaskOccurrenceLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

const SEED_MARKER = '__seed_dashboard__';

$run     = isset($_GET['run']);
$cleanup = isset($_GET['cleanup']);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:1.5rem;font-size:13px}
h2{color:#60a5fa}.ok{color:#34d399}.warn{color:#fbbf24}.err{color:#f87171}.muted{color:#64748b}
.box{background:#1e293b;border-radius:8px;padding:1rem;margin-bottom:.75rem}
a.btn{display:inline-block;padding:.5rem 1.2rem;border-radius:6px;text-decoration:none;font-weight:700;margin-top:1rem}
.btn-green{background:#059669;color:#fff}.btn-red{background:#dc2626;color:#fff}.btn-gray{background:#334155;color:#cbd5e1;margin-right:.5rem}
</style></head><body>
<?php

// ── Dados ────────────────────────────────────────────────────────────
$companies = DB::table('companies')->get();

foreach ($companies as $company) {

    $users = User::where('company_id', $company->id)
        ->whereIn('role', ['admin','manager','staff'])
        ->where('active', 1)
        ->get();

    if ($users->isEmpty()) continue;

    $activities = Activity::where('company_id', $company->id)
        ->where('active', 1)
        ->where('periodicity', 'diario')
        ->with('units')
        ->get();

    if ($activities->isEmpty()) continue;

    echo "<div class='box'><strong class='ok'>Empresa: {$company->name}</strong>";
    echo "<br>Usuários: " . $users->pluck('name')->implode(', ');
    echo "<br>Atividades diárias: " . $activities->count() . "</div>";

    // Simula 7 dias anteriores (não hoje)
    $days = collect(range(7, 1))->map(fn($d) => Carbon::today()->subDays($d));

    // Taxa de conclusão por dia (varia para parecer real)
    $ratesByDay = [7=>55, 6=>70, 5=>80, 4=>65, 3=>90, 2=>75, 1=>85];

    foreach ($days as $day) {
        $daysAgo = Carbon::today()->diffInDays($day);
        $rate    = $ratesByDay[$daysAgo] ?? 70;
        $dateStr = $day->toDateString();

        // Gera occurrences do dia se não existirem
        foreach ($activities as $act) {
            if ($act->units->isEmpty()) {
                $occData = [
                    'company_id'   => $company->id,
                    'activity_id'  => $act->id,
                    'unit_id'      => null,
                    'period_start' => $dateStr,
                    'period_end'   => $dateStr,
                    'status'       => 'PENDING',
                ];
                TaskOccurrence::firstOrCreate(
                    ['activity_id' => $act->id, 'period_start' => $dateStr, 'unit_id' => null],
                    $occData
                );
            } else {
                foreach ($act->units as $unit) {
                    TaskOccurrence::firstOrCreate(
                        ['activity_id' => $act->id, 'period_start' => $dateStr, 'unit_id' => $unit->id],
                        [
                            'company_id'   => $company->id,
                            'activity_id'  => $act->id,
                            'unit_id'      => $unit->id,
                            'period_start' => $dateStr,
                            'period_end'   => $dateStr,
                            'status'       => 'PENDING',
                        ]
                    );
                }
            }
        }

        $occs = TaskOccurrence::where('company_id', $company->id)
            ->whereDate('period_start', $dateStr)
            ->get();

        $toComplete = $occs->shuffle()->take((int) round($occs->count() * $rate / 100));

        // Distribui entre os usuários de forma variada por role
        $usersArr  = $users->toArray();
        $adminUser = $users->firstWhere('role', 'admin');
        $mgr       = $users->firstWhere('role', 'manager');
        $staff     = $users->firstWhere('role', 'staff');

        foreach ($toComplete as $idx => $occ) {
            // Alterna quem completa: staff faz mais, manager faz alguns, admin faz poucos
            $picker = $idx % 10;
            if ($picker < 6 && $staff)         $actor = $staff;
            elseif ($picker < 9 && $mgr)       $actor = $mgr;
            elseif ($adminUser)                 $actor = $adminUser;
            else                                $actor = $users->first();

            // Hora aleatória entre 08:00 e 17:00
            $completedAt = $day->copy()->setHour(rand(8,17))->setMinute(rand(0,59));

            if ($run) {
                // Só marca se ainda PENDING (evita duplicar)
                if ($occ->status !== 'PENDING') continue;

                $occ->update([
                    'status'       => 'DONE',
                    'completed_by' => $actor->id,
                    'completed_at' => $completedAt,
                ]);

                // Marca com justificativa especial para cleanup
                TaskOccurrenceLog::create([
                    'task_occurrence_id' => $occ->id,
                    'user_id'            => $actor->id,
                    'action'             => 'complete',
                    'justification'      => SEED_MARKER,
                    'done_at'            => $completedAt,
                ]);
            }

            // Pendentes viram OVERDUE (dias passados sem conclusão)
            if ($run) {
                TaskOccurrence::where('company_id', $company->id)
                    ->whereDate('period_start', $dateStr)
                    ->where('status', 'PENDING')
                    ->update(['status' => 'OVERDUE']);
            }
        }

        $symbol = $run ? '✓' : '→';
        echo "<div class='box'>{$symbol} <span class='ok'>{$dateStr}</span> | {$occs->count()} occs | "
           . "<span class='warn'>{$rate}%</span> concluídas por "
           . $users->pluck('name')->implode(', ') . "</div>";
    }
}

if ($cleanup) {
    // Remove logs com marker e as occurrences órfãs de dias passados
    $logIds = DB::table('task_occurrence_logs')
        ->where('justification', SEED_MARKER)
        ->pluck('task_occurrence_id')
        ->unique()->toArray();

    DB::table('task_occurrence_logs')->where('justification', SEED_MARKER)->delete();

    if ($logIds) {
        // Volta occurrences para PENDING (se não tiverem outros logs)
        foreach ($logIds as $occId) {
            $hasOtherLogs = DB::table('task_occurrence_logs')->where('task_occurrence_id', $occId)->exists();
            $occ = TaskOccurrence::find($occId);
            if ($occ && !$hasOtherLogs) {
                $today = Carbon::today()->toDateString();
                if ($occ->period_start < $today) {
                    $occ->delete();
                } else {
                    $occ->update(['status' => 'PENDING', 'completed_by' => null, 'completed_at' => null]);
                }
            }
        }
    }
    echo "<p class='ok'>✅ Dados de seed removidos.</p>";
}

if (!$run && !$cleanup): ?>
    <a href="?token=uailabs2026&run=1" class="btn btn-green">▶ Executar seed</a>
    <a href="?token=uailabs2026&cleanup=1" class="btn btn-red">🗑 Limpar seed</a>
<?php elseif ($run): ?>
    <p class='ok fw-bold'>✅ Seed executado. <a href="?token=uailabs2026&cleanup=1" class='btn btn-red' style='margin-left:1rem'>🗑 Limpar depois</a></p>
<?php endif; ?>
</body></html>
