<?php
if (($_GET['token'] ?? '') !== 'uailabs2026') { http_response_code(403); die('Forbidden'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$run = isset($_GET['run']);

// Busca tudo exceto categoria "Abertura"
$keep = DB::table('categories')->where('name', 'Abertura')->value('id');

$otherCategories = DB::table('categories')->where('id', '!=', $keep)->get();
$otherActivities = DB::table('activities')->where('category_id', '!=', $keep)->get();
$abertura        = DB::table('activities')->where('category_id', $keep)->count();

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:1.5rem;font-size:13px}
h2{color:#60a5fa}.ok{color:#34d399}.warn{color:#fbbf24}.err{color:#f87171}.muted{color:#64748b}
.box{background:#1e293b;border-radius:8px;padding:1rem;margin-bottom:.75rem}
a.btn{display:inline-block;padding:.5rem 1.2rem;border-radius:6px;text-decoration:none;font-weight:700;margin-top:1rem}
.btn-red{background:#dc2626;color:#fff}.btn-gray{background:#334155;color:#cbd5e1;margin-right:.5rem}
table{width:100%;border-collapse:collapse;margin-top:.5rem}td,th{padding:.25rem .5rem;text-align:left}
th{color:#94a3b8;font-size:.8rem}tr:hover td{background:#334155}
</style></head><body>
<h2><?= $run ? '🗑 Executando limpeza...' : '👀 Preview — o que será apagado' ?></h2>

<div class="box">
    <span class="ok">✓ Mantendo:</span> categoria <strong>Abertura</strong> (ID <?= $keep ?>) com <strong><?= $abertura ?> atividades</strong>
</div>

<div class="box">
    <span class="warn">Categorias a apagar (<?= count($otherCategories) ?>):</span>
    <table><tr><th>ID</th><th>Nome</th><th>company_id</th></tr>
    <?php foreach ($otherCategories as $c): ?>
        <tr><td class="muted"><?= $c->id ?></td><td><?= htmlspecialchars($c->name) ?></td><td><?= $c->company_id ?></td></tr>
    <?php endforeach; ?>
    </table>
</div>

<div class="box">
    <span class="warn">Atividades a apagar (<?= count($otherActivities) ?>):</span>
    <table><tr><th>ID</th><th>Título</th><th>category_id</th></tr>
    <?php foreach ($otherActivities as $a): ?>
        <tr><td class="muted"><?= $a->id ?></td><td><?= htmlspecialchars($a->title) ?></td><td><?= $a->category_id ?></td></tr>
    <?php endforeach; ?>
    </table>
</div>

<?php if ($run):
    // Apaga occurrences ligadas às atividades
    $actIds = DB::table('activities')->where('category_id', '!=', $keep)->pluck('id');
    DB::table('task_occurrence_logs')->whereIn('occurrence_id',
        DB::table('task_occurrences')->whereIn('activity_id', $actIds)->pluck('id')
    )->delete();
    DB::table('task_occurrences')->whereIn('activity_id', $actIds)->delete();
    DB::table('activities')->where('category_id', '!=', $keep)->delete();
    DB::table('subcategories')->whereNotIn('category_id', [$keep])->delete();
    DB::table('categories')->where('id', '!=', $keep)->delete();
    echo "<p class='ok fw-bold'>✅ Limpeza concluída.</p>";
else: ?>
    <a href="?token=uailabs2026" class="btn btn-gray">← Voltar</a>
    <a href="?token=uailabs2026&run=1" class="btn btn-red">🗑 Apagar tudo isso</a>
<?php endif; ?>
</body></html>
