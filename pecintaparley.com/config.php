<?php
declare(strict_types=1);

function forum_env(string $key, string $fallback = ''): string
{
    static $values;
    if ($values === null) {
        $values = [];
        $path = __DIR__ . '/.env';
        if (is_file($path)) {
            $loaded = parse_ini_file($path, false, INI_SCANNER_RAW);
            if (is_array($loaded)) {
                $values = $loaded;
            }
        }
    }
    $value = $_ENV[$key] ?? getenv($key) ?: $values[$key] ?? $fallback;
    return is_string($value) ? trim($value) : $fallback;
}

function forum_asset_url(string $file): string
{
    $assetOrigin = rtrim(forum_env('ASSET_ORIGIN'), '/');
    if ($assetOrigin === '') {
        return 'assets/' . rawurlencode($file) . '?v=1.1.0';
    }
    return $assetOrigin . '/forum-assets.php?file=' . rawurlencode($file) . '&v=1.1.0';
}

return [
    'site_name' => 'Forum Chat',
    'timezone' => 'Asia/Jakarta',
    'database' => [
        'driver' => strtolower(forum_env('DB_DRIVER', 'mysql')),
        'host' => forum_env('DB_HOST', 'localhost'),
        'port' => max(1, (int) forum_env('DB_PORT', '3306')),
        'name' => forum_env('DB_NAME'),
        'user' => forum_env('DB_USER'),
        'password' => forum_env('DB_PASSWORD'),
        'charset' => forum_env('DB_CHARSET', 'utf8mb4'),
        'sqlite_path' => __DIR__ . '/data/forum.sqlite',
    ],
    'poll_interval_ms' => 3000,
    'max_message_length' => 1200,
    'send_rate_limit' => ['max' => 8, 'window_seconds' => 20],
    'asset_origin' => rtrim(forum_env('ASSET_ORIGIN'), '/'),
];
