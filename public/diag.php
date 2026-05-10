<?php
/**
 * UaiLabs — Diagnóstico de ambiente
 * Acesso: /diag.php?token=uailabs2026
 * REMOVA este arquivo após uso.
 */

define('DIAG_TOKEN', 'uailabs2026');
define('APP_ROOT',   dirname(__DIR__));
define('MAX_LOG_LINES', 120);

// ── Autenticação ──────────────────────────────────────────────────────────────
if (($_GET['token'] ?? '') !== DIAG_TOKEN) {
    http_response_code(403);
    exit('403 Forbidden');
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function mask(string $value): string
{
    if ($value === '') return '(vazio)';
    $len = strlen($value);
    if ($len <= 4) return str_repeat('*', $len);
    return substr($value, 0, 2) . str_repeat('*', $len - 4) . substr($value, -2);
}

function env_val(array $env, string $key, bool $sensitive = false): string
{
    $v = $env[$key] ?? '—';
    return $sensitive ? mask($v) : htmlspecialchars($v);
}

function badge(bool $ok, string $yes = 'OK', string $no = 'FALHOU'): string
{
    $color = $ok ? '#22c55e' : '#ef4444';
    $label = $ok ? $yes : $no;
    return "<span style='background:{$color};color:#fff;padding:2px 8px;border-radius:4px;font-size:.8rem;font-weight:700'>{$label}</span>";
}

function section(string $title): void
{
    echo "<h2 style='margin:2rem 0 .75rem;font-size:1.1rem;color:#60a5fa;border-bottom:1px solid #334155;padding-bottom:.4rem'>{$title}</h2>";
}

function row(string $label, string $value): void
{
    echo "<tr>
        <td style='padding:.35rem .6rem;color:#94a3b8;white-space:nowrap;width:220px'>{$label}</td>
        <td style='padding:.35rem .6rem;color:#e2e8f0;word-break:break-all'>{$value}</td>
    </tr>";
}

// ── Leitura do .env ───────────────────────────────────────────────────────────
$envFile = APP_ROOT . '/.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

// ── Conexão MySQL ─────────────────────────────────────────────────────────────
$dbOk     = false;
$dbError  = '';
$tables   = [];
$dbVersion = '';

try {
    $dsn  = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST']     ?? '127.0.0.1',
        $env['DB_PORT']     ?? '3306',
        $env['DB_DATABASE'] ?? 'laravel'
    );
    $pdo  = new PDO($dsn, $env['DB_USERNAME'] ?? 'root', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $dbOk      = true;
    $dbVersion = $pdo->query('SELECT VERSION()')->fetchColumn();

    $stmt = $pdo->query("
        SELECT table_name, table_rows, ROUND((data_length+index_length)/1024,1) AS kb
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// ── Log do Laravel ────────────────────────────────────────────────────────────
$logPath  = APP_ROOT . '/storage/logs/laravel.log';
$logLines = [];
$logSize  = 0;

if (file_exists($logPath)) {
    $logSize  = filesize($logPath);
    $all      = file($logPath, FILE_IGNORE_NEW_LINES);
    $logLines = array_slice($all, -MAX_LOG_LINES);
}

// ── Permissões de diretório ───────────────────────────────────────────────────
$dirs = [
    'storage/logs'          => APP_ROOT . '/storage/logs',
    'storage/framework'     => APP_ROOT . '/storage/framework',
    'storage/app'           => APP_ROOT . '/storage/app',
    'bootstrap/cache'       => APP_ROOT . '/bootstrap/cache',
];

// ── Extensões PHP requeridas ──────────────────────────────────────────────────
$requiredExt = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer',
                 'xml', 'ctype', 'json', 'bcmath', 'fileinfo', 'curl'];

// ── Resposta HTML ─────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>UaiLabs — Diagnóstico</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body   { background: #0f172a; color: #e2e8f0; font: 14px/1.6 'Segoe UI', system-ui, sans-serif; padding: 1.5rem; }
  h1     { font-size: 1.4rem; font-weight: 800; margin-bottom: .25rem;
           background: linear-gradient(135deg,#60a5fa,#a78bfa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
  .meta  { color: #475569; font-size: .8rem; margin-bottom: 2rem; }
  table  { width: 100%; border-collapse: collapse; background: #1e293b; border-radius: 10px; overflow: hidden; margin-bottom: .5rem; }
  pre    { background: #1e293b; border-radius: 10px; padding: 1rem; overflow-x: auto;
           font-size: .78rem; line-height: 1.5; white-space: pre-wrap; word-break: break-all; max-height: 500px; overflow-y: auto; }
  .warn  { color: #fbbf24; }
  .err   { color: #f87171; }
  .ok    { color: #34d399; }
  .grid  { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: .5rem; }
  .card  { background: #1e293b; border-radius: 10px; padding: 1rem 1.25rem; }
  .card-title { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: #64748b; margin-bottom: .4rem; }
  .card-value { font-size: 1.3rem; font-weight: 700; }
</style>
</head>
<body>

<h1>UaiLabs — Diagnóstico</h1>
<div class="meta">Gerado em <?= date('d/m/Y H:i:s') ?> &nbsp;|&nbsp; <?= php_uname('n') ?> &nbsp;|&nbsp;
<strong style="color:<?= $dbOk ? '#22c55e' : '#ef4444' ?>"><?= $dbOk ? 'DB conectado' : 'DB offline' ?></strong></div>

<?php /* ── Cards de resumo ──────────────────────────────────────────────── */ ?>
<div class="grid">
    <div class="card">
        <div class="card-title">PHP</div>
        <div class="card-value <?= version_compare(PHP_VERSION,'8.2','>=') ? 'ok' : 'warn' ?>"><?= PHP_VERSION ?></div>
    </div>
    <div class="card">
        <div class="card-title">Banco de dados</div>
        <div class="card-value <?= $dbOk ? 'ok' : 'err' ?>"><?= $dbOk ? $dbVersion : 'Offline' ?></div>
    </div>
    <div class="card">
        <div class="card-title">APP_ENV</div>
        <div class="card-value"><?= env_val($env, 'APP_ENV') ?></div>
    </div>
    <div class="card">
        <div class="card-title">Log Laravel</div>
        <div class="card-value"><?= $logSize ? number_format($logSize/1024, 1) . ' KB' : 'Vazio' ?></div>
    </div>
</div>

<?php /* ── Sistema ──────────────────────────────────────────────────────── */ ?>
<?php section('Sistema') ?>
<table>
<?php
row('OS',              php_uname());
row('SAPI',            php_sapi_name());
row('PHP versão',      PHP_VERSION);
row('Timezone',        date_default_timezone_get());
row('memory_limit',    ini_get('memory_limit'));
row('max_exec_time',   ini_get('max_execution_time') . 's');
row('upload_max',      ini_get('upload_max_filesize'));
row('post_max',        ini_get('post_max_size'));
row('display_errors',  ini_get('display_errors') ? 'on' : 'off');
?>
</table>

<?php /* ── Extensões PHP ────────────────────────────────────────────────── */ ?>
<?php section('Extensões PHP') ?>
<table>
<?php foreach ($requiredExt as $ext): ?>
<?php $loaded = extension_loaded($ext); ?>
<?php row(htmlspecialchars($ext), badge($loaded)) ?>
<?php endforeach ?>
</table>

<?php /* ── Variáveis de Ambiente ────────────────────────────────────────── */ ?>
<?php section('Variáveis de Ambiente (.env)') ?>
<table>
<?php
row('APP_NAME',    env_val($env, 'APP_NAME'));
row('APP_ENV',     env_val($env, 'APP_ENV'));
row('APP_DEBUG',   env_val($env, 'APP_DEBUG'));
row('APP_URL',     env_val($env, 'APP_URL'));
row('DB_CONNECTION', env_val($env, 'DB_CONNECTION'));
row('DB_HOST',     env_val($env, 'DB_HOST'));
row('DB_PORT',     env_val($env, 'DB_PORT'));
row('DB_DATABASE', env_val($env, 'DB_DATABASE'));
row('DB_USERNAME', env_val($env, 'DB_USERNAME'));
row('DB_PASSWORD', env_val($env, 'DB_PASSWORD', true));
row('CACHE_DRIVER',  env_val($env, 'CACHE_DRIVER'));
row('SESSION_DRIVER', env_val($env, 'SESSION_DRIVER'));
row('MAIL_MAILER',   env_val($env, 'MAIL_MAILER'));
row('QUEUE_CONNECTION', env_val($env, 'QUEUE_CONNECTION'));
?>
</table>

<?php /* ── Banco de dados ───────────────────────────────────────────────── */ ?>
<?php section('Banco de Dados') ?>
<?php if (!$dbOk): ?>
<pre class="err"><?= htmlspecialchars($dbError) ?></pre>
<?php else: ?>
<table>
    <thead>
        <tr style="background:#0f172a">
            <th style="padding:.4rem .6rem;text-align:left;color:#64748b;font-size:.78rem">Tabela</th>
            <th style="padding:.4rem .6rem;text-align:right;color:#64748b;font-size:.78rem">Linhas ~</th>
            <th style="padding:.4rem .6rem;text-align:right;color:#64748b;font-size:.78rem">Tamanho</th>
        </tr>
    </thead>
<?php foreach ($tables as $t): ?>
    <tr>
        <td style="padding:.35rem .6rem;color:#e2e8f0"><?= htmlspecialchars($t['table_name']) ?></td>
        <td style="padding:.35rem .6rem;color:#94a3b8;text-align:right"><?= number_format((int)$t['table_rows']) ?></td>
        <td style="padding:.35rem .6rem;color:#94a3b8;text-align:right"><?= $t['kb'] ?> KB</td>
    </tr>
<?php endforeach ?>
</table>
<?php endif ?>

<?php /* ── Permissões de diretório ──────────────────────────────────────── */ ?>
<?php section('Permissões de Diretório') ?>
<table>
<?php foreach ($dirs as $label => $path): ?>
<?php
$exists   = file_exists($path);
$writable = $exists && is_writable($path);
$perms    = $exists ? substr(sprintf('%o', fileperms($path)), -4) : '—';
$status   = !$exists ? badge(false, '', 'NÃO EXISTE') : badge($writable, 'Gravável', 'SEM PERMISSÃO');
row(htmlspecialchars($label), $status . " <span style='color:#475569;font-size:.8rem;margin-left:.5rem'>{$perms}</span>");
?>
<?php endforeach ?>
</table>

<?php /* ── Log do Laravel ───────────────────────────────────────────────── */ ?>
<?php section('Log do Laravel (últimas ' . MAX_LOG_LINES . ' linhas)') ?>
<?php if (empty($logLines)): ?>
<p style="color:#475569;padding:.5rem 0">Log vazio ou inexistente.</p>
<?php else: ?>
<pre><?php
foreach ($logLines as $line) {
    $class = '';
    if (str_contains($line, '.ERROR') || str_contains($line, 'Exception') || str_contains($line, 'SQLSTATE')) {
        $class = 'err';
    } elseif (str_contains($line, '.WARNING') || str_contains($line, 'deprecated')) {
        $class = 'warn';
    }
    $escaped = htmlspecialchars($line);
    echo $class ? "<span class='{$class}'>{$escaped}</span>\n" : $escaped . "\n";
}
?></pre>
<?php endif ?>

<?php /* ── Rodapé ───────────────────────────────────────────────────────── */ ?>
<p style="margin-top:2rem;color:#1e293b;font-size:.75rem;text-align:center">
  🔒 Remova <code style="color:#475569">public/diag.php</code> após uso.
</p>

</body>
</html>
