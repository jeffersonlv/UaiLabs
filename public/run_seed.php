<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

if (($_GET['token'] ?? '') !== 'seed2026retro') { http_response_code(403); die('Forbidden'); }

try {
    define('LARAVEL_START', microtime(true));
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $seeder = new Database\Seeders\RetroactiveDataSeeder;
    $seeder->setContainer($app);
    $seeder->run();
    echo "OK — Dados retroativos gerados com sucesso.\n";
} catch (\Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Em: " . $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
