<?php
if (($_GET['token'] ?? '') !== 'uailabs2026') { http_response_code(403); die('Forbidden'); }

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Activity;

// ── Dados ─────────────────────────────────────────────────────────
$data = [
    'Abertura Cozinha' => [
        'Ligar máquina café','Ligar máquina de água','Ligar display (3)',
        'Tirar tampa do display','Colocar lixeira p/ fora',
        'Conferir freezer e geladeira','Fazer RH Match',
        'Ligar elevador','Ligar gás','Ligar água','Ligar forno',
    ],
    'Fazer preps' => [
        'Pão de queijo (10 dias)','Croissant (max 2 dias)','Bolo banana (4 dias)',
        'PB bar (4 dias)','Granola (4 dias)','Waffles (4 dias)',
        'Cookies (3 dias)','Scones (2 dias)','Nutella croissant (max 2 dias)',
    ],
    'Repor Squeeze' => [
        'Catupiry','Relchup','Mayo','Blue cheese','Barbecue',
        'Hollandaise (2 dias)','Maionese de ervas','Sriracha',
        'Condensed milk','Doce de leite','Nutella',
    ],
    'Repor Proteínas' => [
        'Chicken','Beef','Sausage','Ham','Meatballs','Bacon',
    ],
    'Repor Cheese' => [
        'Mussarela','Cheddar','Blue cheese',
    ],
    'Repor Geladeiras' => [
        'Saladas (mix)','Berries fruit','Berries jam','Eggs',
        'Table yogurt','Coxinha','Micro leaves',
        'Pães (brioche, misto, sourdough)','Soup',
    ],
    'Repor Salão' => [
        'Guardanapo','TH DP','Papel de sand','Copos',
        'Açúcar','Sal','Pimenta',
    ],
];

// ── Encontra categoria Abertura ────────────────────────────────────
$category = Category::where('name', 'Abertura')->first();

$run = isset($_GET['run']);

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html><head><meta charset="UTF-8">
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:1.5rem;font-size:13px}
h2{color:#60a5fa;margin-bottom:1rem}
.ok{color:#34d399} .warn{color:#fbbf24} .err{color:#f87171} .muted{color:#64748b}
.box{background:#1e293b;border-radius:8px;padding:1rem;margin-bottom:1rem}
a.btn{display:inline-block;padding:.5rem 1.2rem;border-radius:6px;text-decoration:none;font-weight:700;margin-top:1rem}
.btn-green{background:#059669;color:#fff} .btn-red{background:#dc2626;color:#fff}
</style></head><body>
<?php if (!$category): ?>
<h2>❌ Categoria "Abertura" não encontrada na base de dados.</h2>
<p class="warn">Crie a categoria "Abertura" no sistema antes de rodar este script.</p>
<?php else: ?>
<h2><?= $run ? '🚀 Executando...' : '👀 Preview (dry-run)' ?></h2>
<div class="box">
    <span class="ok">Categoria encontrada:</span> <?= htmlspecialchars($category->name) ?>
    (ID <?= $category->id ?>, company_id <?= $category->company_id ?>)
</div>

<?php

$totalActivities = 0;
$order = 1;

if ($run) {
    // Apaga atividades e subcategorias da categoria
    $subIds = Subcategory::where('category_id', $category->id)->pluck('id');
    $deleted = Activity::where('category_id', $category->id)->delete();
    Subcategory::where('category_id', $category->id)->delete();
    echo "<p class='warn'>🗑 Apagadas $deleted atividades e " . count($subIds) . " subcategorias.</p>";
}

foreach ($data as $subcatName => $activities) {
    echo "<div class='box'>";
    echo "<strong class='ok'>📁 " . htmlspecialchars($subcatName) . "</strong><br><br>";

    if ($run) {
        $subcat = Subcategory::create([
            'category_id' => $category->id,
            'name'        => $subcatName,
            'order'       => $order,
        ]);
    }

    foreach ($activities as $i => $title) {
        echo "<span class='muted'>  " . ($i + 1) . ".</span> " . htmlspecialchars($title) . "<br>";
        if ($run) {
            Activity::create([
                'company_id'   => $category->company_id,
                'category_id'  => $category->id,
                'subcategory_id' => $subcat->id,
                'title'        => $title,
                'periodicity'  => 'diario',
                'active'       => true,
                'sequence_required' => false,
                'sequence_order'    => 0,
            ]);
        }
        $totalActivities++;
    }

    echo "</div>";
    $order++;
}

echo "<p><strong>" . ($run ? '<span class="ok">✓ Criados</span>' : 'Serão criados') . ":</strong> $order subcategorias, $totalActivities atividades.</p>";

if (!$run): ?>
    <a href="?token=uailabs2026&run=1" class="btn btn-green">▶ Executar agora</a>
<?php else: ?>
    <p class="ok fw-bold">✅ Pronto! Dados importados com sucesso.</p>
<?php endif; ?>

<?php endif; ?>
</body></html>
