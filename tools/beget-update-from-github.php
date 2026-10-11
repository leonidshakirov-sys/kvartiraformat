<?php

declare(strict_types=1);

/**
 * One-time Beget updater. Upload THIS file into
 * /kvartiraformat.ru/public_html/ next to the live index.php (left pane),
 * then open:
 * https://kvartiraformat.ru/beget-update-from-github.php?token=kf-update-20261011
 *
 * It prefers an already unpacked folder (kvartiraformat-main), otherwise
 * downloads GitHub main. It overwrites app/, public/, database/, tools/,
 * root index.php and stale /assets. It does not touch config/config.php
 * or storage/lead-files. It deletes itself after a successful update.
 */
$token = 'kf-update-20261011';
$provided = PHP_SAPI === 'cli' ? (string)($argv[1] ?? '') : (string)($_GET['token'] ?? '');
if (!hash_equals($token, $provided)) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(180);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$web = __DIR__;
$root = is_dir($web . '/app') || is_dir($web . '/public') ? $web : dirname($web);
if (!is_dir($root . '/app') && !is_dir($root . '/public') && !is_file($root . '/index.php')) {
    http_response_code(500);
    exit("site-root-not-found\nPut this file into /kvartiraformat.ru/public_html/ next to index.php, app and public.\n");
}

echo "root=$root\nweb=$web\n";

flattenNestedCopy($root, 'app');
flattenNestedCopy($root, 'public');

$source = findLocalSource($root);
$extractDir = $root . '/storage/beget-update-extract';
$zipPath = $root . '/storage/beget-update.zip';
$cleanupExtract = false;

if ($source !== null) {
    echo "source=local $source\n";
} else {
    echo "source=github-main\n";
    if (!is_dir($root . '/storage') && !mkdir($root . '/storage', 0750, true) && !is_dir($root . '/storage')) {
        http_response_code(500);
        exit('storage-mkdir-failed');
    }
    $zipUrl = 'https://github.com/leonidshakirov-sys/kvartiraformat/archive/refs/heads/main.zip';
    $zipData = downloadUrl($zipUrl);
    if ($zipData === false) {
        http_response_code(500);
        exit("download-failed\nGitHub недоступен с сервера. Оставьте распакованную папку kvartiraformat-main внутри public_html и откройте скрипт снова.\n");
    }
    file_put_contents($zipPath, $zipData);
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        http_response_code(500);
        exit('zip-open-failed');
    }
    rrmdir($extractDir);
    if (!mkdir($extractDir, 0750, true) && !is_dir($extractDir)) {
        http_response_code(500);
        exit('extract-mkdir-failed');
    }
    $zip->extractTo($extractDir);
    $zip->close();
    $source = findLocalSource($extractDir) ?? firstProjectDir($extractDir);
    $cleanupExtract = true;
    if ($source === null) {
        http_response_code(500);
        exit('source-not-found');
    }
    echo "extracted=$source\n";
}

if (!is_dir($source . '/app') || !is_dir($source . '/public')) {
    http_response_code(500);
    exit('source-incomplete');
}

$copied = 0;
$copied += copyTree($source . '/app', $root . '/app');
$copied += copyTree($source . '/database', $root . '/database');
$copied += copyTree($source . '/tools', $root . '/tools');
$copied += copyTree($source . '/public', $root . '/public');

if (is_dir($root . '/assets') || is_dir($source . '/public/assets')) {
    $copied += copyTree($source . '/public/assets', $root . '/assets');
}

if (is_file($source . '/.htaccess')) {
    copy($source . '/.htaccess', $root . '/.htaccess');
    $copied++;
}

$wrapper = "<?php\n\ndeclare(strict_types=1);\n\nrequire __DIR__ . '/public/index.php';\n";
if (file_put_contents($root . '/index.php', $wrapper) === false) {
    http_response_code(500);
    exit('root-index-write-failed');
}
$copied++;
echo "wrote-root-index=1\n";

$configDir = $root . '/config';
if (!is_dir($configDir)) {
    mkdir($configDir, 0750, true);
}
if (!is_file($configDir . '/config.php') && is_file($source . '/config/config.sample.php')) {
    copy($source . '/config/config.sample.php', $configDir . '/config.sample.php');
}

if ($cleanupExtract) {
    @unlink($zipPath);
    rrmdir($extractDir);
}

$ok = verifyUpdate($root);
echo "copied=$copied\n";
foreach ($ok['lines'] as $line) {
    echo $line . "\n";
}

if (!$ok['ok']) {
    http_response_code(500);
    exit("verify-failed\nФайлы домов не найдены после копирования. Проверьте, что скрипт лежит в public_html, а не внутри kvartiraformat-main.\n");
}

echo "done. Open https://kvartiraformat.ru/ and https://kvartiraformat.ru/remont-domov/\n";
@unlink(__FILE__);

function findLocalSource(string $root): ?string
{
    $matches = [];
    foreach (scandir($root) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $root . '/' . $item;
        if (!is_dir($path) || !str_contains($item, 'kvartiraformat')) {
            continue;
        }
        $resolved = firstProjectDir($path);
        if ($resolved !== null) {
            $matches[] = $resolved;
        }
    }
    usort($matches, static function (string $a, string $b): int {
        return sourceScore($a) <=> sourceScore($b);
    });
    return $matches[0] ?? null;
}

function firstProjectDir(string $dir): ?string
{
    if (is_dir($dir . '/app') && is_dir($dir . '/public')) {
        return $dir;
    }
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..' || !str_contains($item, 'kvartiraformat')) {
            continue;
        }
        $nested = $dir . '/' . $item;
        if (is_dir($nested . '/app') && is_dir($nested . '/public')) {
            return $nested;
        }
    }
    return null;
}

function sourceScore(string $path): int
{
    $base = basename($path);
    if ($base === 'kvartiraformat-main' || str_ends_with($base, '-main')) {
        return 0;
    }
    if (str_contains($base, 'three-directions')) {
        return 1;
    }
    return 5;
}

function flattenNestedCopy(string $root, string $folder): void
{
    $nested = $root . '/' . $folder . '/' . $folder;
    $marker = $folder === 'app' ? $nested . '/Support/HouseCatalog.php' : $nested . '/index.php';
    $live = $folder === 'app' ? $root . '/app/Support/HouseCatalog.php' : $root . '/public/index.php';
    if (is_dir($nested) && is_file($marker) && !is_file($live)) {
        echo "flatten-$folder\n";
        copyTree($nested, $root . '/' . $folder);
    }
}

function copyTree(string $from, string $to): int
{
    if (!is_dir($from)) {
        return 0;
    }
    if (!is_dir($to) && !mkdir($to, 0755, true) && !is_dir($to)) {
        throw new RuntimeException('mkdir-failed ' . $to);
    }
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        $relative = substr($item->getPathname(), strlen($from) + 1);
        if ($relative === '' || str_starts_with($relative, 'kvartiraformat')) {
            continue;
        }
        $target = $to . '/' . $relative;
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
            continue;
        }
        $dir = dirname($target);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!copy($item->getPathname(), $target)) {
            throw new RuntimeException('copy-failed ' . $relative);
        }
        $count++;
    }
    return $count;
}

function rrmdir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($dir);
}

function downloadUrl(string $url): string|false
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_USERAGENT => 'kvartiraformat-beget-update',
        ]);
        $data = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (is_string($data) && $code >= 200 && $code < 300 && strlen($data) > 10000) {
            return $data;
        }
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => 120, 'follow_location' => 1, 'user_agent' => 'kvartiraformat-beget-update'],
        'https' => ['timeout' => 120],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    return is_string($data) && strlen($data) > 10000 ? $data : false;
}

function verifyUpdate(string $root): array
{
    $checks = [
        'house-catalog' => is_file($root . '/app/Support/HouseCatalog.php'),
        'houses-view' => is_file($root . '/app/Views/public/houses.php'),
        'public-index' => is_file($root . '/public/index.php') && str_contains((string) file_get_contents($root . '/public/index.php'), 'remont-domov'),
        'root-index' => is_file($root . '/index.php') && str_contains((string) file_get_contents($root . '/index.php'), "public/index.php"),
        'house-hero' => is_file($root . '/public/assets/images/houses/hero.webp'),
    ];
    $lines = [];
    $ok = true;
    foreach ($checks as $name => $pass) {
        $lines[] = $name . '=' . ($pass ? 'ok' : 'MISSING');
        $ok = $ok && $pass;
    }
    return ['ok' => $ok, 'lines' => $lines];
}
