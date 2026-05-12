<?php
if (($_GET['token'] ?? '') !== 'fixenv2026') { http_response_code(403); die('Forbidden'); }

$envPath = __DIR__ . '/../.env';
$content = file_get_contents($envPath);

$replacements = [
    '/^MAIL_MAILER=.*/m'   => 'MAIL_MAILER=log',
    '/^MAIL_HOST=.*/m'     => 'MAIL_HOST=127.0.0.1',
    '/^MAIL_PORT=.*/m'     => 'MAIL_PORT=2525',
    '/^MAIL_USERNAME=.*/m' => 'MAIL_USERNAME=null',
    '/^MAIL_PASSWORD=.*/m' => 'MAIL_PASSWORD=null',
    '/^MAIL_ENCRYPTION=.*/m' => 'MAIL_ENCRYPTION=null',
];

foreach ($replacements as $pattern => $replacement) {
    $content = preg_replace($pattern, $replacement, $content);
}

file_put_contents($envPath, $content);
echo "OK — MAIL_MAILER=log configurado.\n";
