<?php
if (($_GET['token'] ?? '') !== 'seed2026retro') { http_response_code(403); die('Forbidden'); }

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

try {
    $seeder = new Database\Seeders\RetroactiveDataSeeder;
    $seeder->setContainer($app)->setCommand(null)->run();
    echo "OK — Dados retroativos gerados com sucesso.\n";
} catch (\Throwable $e) {
    http_response_code(500);
    echo "ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
