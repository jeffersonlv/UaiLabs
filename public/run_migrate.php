<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (($_GET['token'] ?? '') !== 'uailabs2026') {
    http_response_code(403);
    exit('Forbidden');
}

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre>";
\Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo htmlspecialchars(\Illuminate\Support\Facades\Artisan::output());
echo "</pre>";
