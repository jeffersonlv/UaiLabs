<?php
/**
 * deploy.php — UaiLabs deployment helper
 * Protected by token. Access: /deploy.php?token=uailabs2026
 *
 * Optional query params (combine freely):
 *   &pull        → git pull origin main
 *   &migrate     → php artisan migrate --force
 *   &seed        → php artisan db:seed --force
 *   &config      → php artisan config:cache
 *   &route       → php artisan route:cache
 *   &view        → php artisan view:cache
 *   &optimize    → php artisan optimize
 *   &clear       → php artisan optimize:clear
 *   &all         → pull + migrate + optimize (common deploy sequence)
 */

define('SECRET', 'uailabs2026');
define('BASE',   dirname(__DIR__));

// Try to resolve php82 absolute path
function findPhpBin(): string {
    $candidates = [
        '/usr/bin/php82',
        '/usr/local/bin/php82',
        '/usr/bin/php8.2',
        '/usr/local/bin/php8.2',
        '/opt/cpanel/ea-php82/root/usr/bin/php',
        '/usr/local/lsws/lsphp82/bin/php',
        '/opt/alt/php82/usr/bin/php',
    ];
    // try which via env -i to avoid needing .bashrc
    $which = trim((string) shell_exec('which php82 2>/dev/null'));
    if ($which) return $which;
    $which = trim((string) shell_exec('/usr/bin/which php82 2>/dev/null'));
    if ($which) return $which;
    // search common dirs
    $found = trim((string) shell_exec('find /usr /opt -name "php82" -type f 2>/dev/null | head -1'));
    if ($found) return $found;
    foreach ($candidates as $p) {
        if (is_executable($p)) return $p;
    }
    return 'php82'; // fallback
}

define('PHP_BIN', findPhpBin());
define('ARTISAN', PHP_BIN . ' ' . BASE . '/artisan');

// ── Auth ─────────────────────────────────────────────────────────────────────
if (($_GET['token'] ?? '') !== SECRET) {
    http_response_code(403);
    die('Forbidden');
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function run(string $cmd): array {
    $output = [];
    $code   = 0;
    exec($cmd . ' 2>&1', $output, $code);
    return ['cmd' => $cmd, 'output' => implode("\n", $output), 'code' => $code];
}

function artisan(string $args): array {
    return run(ARTISAN . ' ' . $args);
}

// ── Resolve which commands to run ─────────────────────────────────────────────
$jobs = [];

if (isset($_GET['all'])) {
    $_GET['pull']     = 1;
    $_GET['migrate']  = 1;
    $_GET['optimize'] = 1;
}

if (isset($_GET['pull']))     $jobs[] = ['label' => 'git pull',           'result' => run('cd ' . BASE . ' && git pull origin main')];
if (isset($_GET['migrate']))  $jobs[] = ['label' => 'migrate --force',    'result' => artisan('migrate --force')];
if (isset($_GET['seed']))     $jobs[] = ['label' => 'db:seed --force',    'result' => artisan('db:seed --force')];
if (isset($_GET['clear']))    $jobs[] = ['label' => 'optimize:clear',     'result' => artisan('optimize:clear')];
if (isset($_GET['config']))   $jobs[] = ['label' => 'config:cache',       'result' => artisan('config:cache')];
if (isset($_GET['route']))    $jobs[] = ['label' => 'route:cache',        'result' => artisan('route:cache')];
if (isset($_GET['view']))     $jobs[] = ['label' => 'view:cache',         'result' => artisan('view:cache')];
if (isset($_GET['optimize'])) $jobs[] = ['label' => 'optimize',           'result' => artisan('optimize')];

// ── Collect status info ───────────────────────────────────────────────────────
$gitLog    = run('cd ' . BASE . ' && git log --oneline -5');
$gitStatus = run('cd ' . BASE . ' && git status --short');
$phpVer    = PHP_VERSION;
$laravelVer = '';
try {
    $composer = json_decode(file_get_contents(BASE . '/composer.json'), true);
    $laravelVer = $composer['require']['laravel/framework'] ?? '?';
} catch (\Throwable) {}

?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Deploy · UaiLabs</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#0f172a;color:#e2e8f0;font-family:system-ui,sans-serif;font-size:14px;padding:1.5rem}
h1{font-size:1.3rem;font-weight:700;color:#60a5fa;margin-bottom:1.25rem}
h2{font-size:.85rem;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;margin:.1rem 0 .6rem}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem}
@media(max-width:600px){.grid{grid-template-columns:1fr}}
.card{background:#1e293b;border-radius:10px;padding:1rem}
pre{background:#0f172a;border-radius:6px;padding:.75rem;font-size:.78rem;overflow-x:auto;white-space:pre-wrap;word-break:break-all;color:#94a3b8;margin-top:.4rem}
.ok{color:#34d399} .fail{color:#f87171} .badge{display:inline-block;padding:.15rem .5rem;border-radius:4px;font-size:.72rem;font-weight:700}
.badge-ok{background:rgba(52,211,153,.15);color:#34d399} .badge-fail{background:rgba(248,113,113,.15);color:#f87171}
.actions{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.5rem}
.btn{display:inline-block;padding:.5rem 1rem;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;transition:opacity .15s}
.btn:hover{opacity:.85}
.btn-blue{background:#2563eb;color:#fff}
.btn-green{background:#059669;color:#fff}
.btn-yellow{background:#d97706;color:#fff}
.btn-red{background:#dc2626;color:#fff}
.btn-gray{background:#334155;color:#cbd5e1}
.sep{color:#475569;align-self:center}
.result-block{margin-bottom:1rem}
.result-block h3{font-size:.82rem;font-weight:600;margin-bottom:.3rem}
.info-row{display:flex;gap:2rem;margin-bottom:1rem;flex-wrap:wrap}
.info-item{font-size:.8rem;color:#64748b}
.info-item span{color:#e2e8f0;font-weight:600}
</style>
</head>
<body>

<h1>Deploy · UaiLabs</h1>

<div class="info-row">
    <div class="info-item">PHP web <span><?= htmlspecialchars($phpVer) ?></span></div>
    <div class="info-item">PHP CLI <span><?= htmlspecialchars(trim((string)shell_exec(PHP_BIN . ' -r "echo phpversion();" 2>/dev/null')) ?: '?') ?></span></div>
    <div class="info-item">PHP bin <span><?= htmlspecialchars(PHP_BIN) ?></span></div>
    <div class="info-item">Laravel <span><?= htmlspecialchars($laravelVer) ?></span></div>
    <div class="info-item">Servidor <span><?= htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'n/a') ?></span></div>
    <div class="info-item">Data/hora <span><?= date('d/m/Y H:i:s') ?></span></div>
</div>

<?php $token = '?token=' . SECRET; ?>

<!-- ── Action buttons ──────────────────────────────────────────────────────── -->
<div class="actions">
    <a href="<?= $token ?>&all" class="btn btn-green">Deploy completo (pull + migrate + optimize)</a>
    <span class="sep">|</span>
    <a href="<?= $token ?>&pull" class="btn btn-blue">git pull</a>
    <a href="<?= $token ?>&migrate" class="btn btn-blue">migrate</a>
    <a href="<?= $token ?>&optimize" class="btn btn-blue">optimize</a>
    <span class="sep">|</span>
    <a href="<?= $token ?>&config" class="btn btn-gray">config:cache</a>
    <a href="<?= $token ?>&route" class="btn btn-gray">route:cache</a>
    <a href="<?= $token ?>&view" class="btn btn-gray">view:cache</a>
    <span class="sep">|</span>
    <a href="<?= $token ?>&clear" class="btn btn-yellow">optimize:clear</a>
    <a href="<?= $token ?>&migrate&seed" class="btn btn-yellow">migrate + seed</a>
    <span class="sep">|</span>
    <a href="<?= $token ?>" class="btn btn-gray">Só status</a>
</div>

<!-- ── Command results ─────────────────────────────────────────────────────── -->
<?php if ($jobs): ?>
<div class="card" style="margin-bottom:1.5rem">
    <h2>Resultado</h2>
    <?php foreach ($jobs as $job): ?>
    <div class="result-block">
        <h3>
            <span class="badge <?= $job['result']['code'] === 0 ? 'badge-ok' : 'badge-fail' ?>">
                <?= $job['result']['code'] === 0 ? '✓' : '✗' ?>
            </span>
            &nbsp;<?= htmlspecialchars($job['label']) ?>
        </h3>
        <pre><?= htmlspecialchars($job['result']['output'] ?: '(sem output)') ?></pre>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Status info ─────────────────────────────────────────────────────────── -->
<div class="grid">
    <div class="card">
        <h2>git log (últimos 5)</h2>
        <pre><?= htmlspecialchars($gitLog['output'] ?: 'N/A') ?></pre>
    </div>
    <div class="card">
        <h2>git status</h2>
        <pre><?= htmlspecialchars($gitStatus['output'] ?: 'Limpo') ?></pre>
    </div>
</div>

<?php
// ── Pending migrations ────────────────────────────────────────────────────────
$pending = artisan('migrate:status');
?>
<div class="card" style="margin-bottom:1.5rem">
    <h2>Status das migrations</h2>
    <pre><?= htmlspecialchars($pending['output']) ?></pre>
</div>

<?php
// ── .env key vars (safe subset) ───────────────────────────────────────────────
$envFile = BASE . '/.env';
$envVars = [];
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if (!$line || str_starts_with($line, '#')) continue;
        [$key] = explode('=', $line) + ['', ''];
        $safe = ['APP_NAME','APP_ENV','APP_DEBUG','APP_URL','DB_CONNECTION','DB_HOST','DB_DATABASE','CACHE_DRIVER','SESSION_DRIVER','QUEUE_CONNECTION'];
        if (in_array($key, $safe)) {
            $envVars[$key] = parse_ini_string($line)[$key] ?? '?';
        }
    }
}
?>
<div class="card">
    <h2>.env (variáveis seguras)</h2>
    <pre><?php foreach ($envVars as $k => $v): ?><?= htmlspecialchars("{$k}={$v}") ?>
<?php endforeach; ?></pre>
</div>

</body>
</html>
