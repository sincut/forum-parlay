<?php
declare(strict_types=1);

$allowed = [
    'app.css' => ['path' => dirname(__DIR__) . '/pecintaparley.com/assets/app.css', 'type' => 'text/css; charset=utf-8'],
    'app.js' => ['path' => dirname(__DIR__) . '/pecintaparley.com/assets/app.js', 'type' => 'application/javascript; charset=utf-8'],
];
$file = (string) ($_GET['file'] ?? '');
if (!isset($allowed[$file]) || !is_file($allowed[$file]['path'])) {
    http_response_code(404);
    exit('Asset tidak ditemukan.');
}

header('Content-Type: ' . $allowed[$file]['type']);
header('Cache-Control: public, max-age=604800, immutable');
header('Access-Control-Allow-Origin: https://forum.pecintaparley.com');
header('X-Content-Type-Options: nosniff');
readfile($allowed[$file]['path']);
