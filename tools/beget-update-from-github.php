<?php

declare(strict_types=1);

/**
 * One-time Beget updater. Upload this file into public_html (next to index.php)
 * or into the folder that contains app/, then open:
 * https://kvartiraformat.ru/beget-update-from-github.php?token=kf-update-20261011
 * The script downloads GitHub main, overwrites site files, keeps config.php,
 * and deletes itself.
 */
$token = 'kf-update-20261011';
if (!hash_equals($token, (string)($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit('forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(180);
error_reporting(E_ALL);
ini_set('display_errors', '1');

$web = __DIR__;
$root = is_dir($web . '/app') ? $web : dirname($web);
if (!is_dir($root . '/app')) {
    http_response_code(500);
    exit("app-not-found\nPut this file next to public/index.php (usually public_html) or into the folder that contains app/.\n");
}

$zipUrl = 'https://github.com/leonidshakirov-sys/kvartiraformat/archive/refs/heads/main.zip';
$zipPath = $root . '/storage/beget-update.zip';
$extractDir = $root . '/storage/beget-update-extract';
if (!is_dir($root . '/storage') && !mkdir($root . '/storage', 0750, true) && !is_dir($root . '/storage')) {
    http_response_code(500);
    exit('storage-mkdir-failed');
}

echo "root=$root\nweb=$web\ndownloading main.zip\n";
$zipData = @file_get_contents($zipUrl);
if ($zipData === false || strlen($zipData) < 10000) {
    http_response_code(500);
    exit('download-failed');
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

$entries = array_values(array_filter(scandir($extractDir) ?: [], static fn($item) => $item !== '.' && $item !== '..'));
$source = $extractDir . '/' . ($entries[0] ?? '');
if (!is_dir($source . '/app') || !is_dir($source . '/public')) {
    http_response_code(500);
    exit('source-not-found');
}

$copied = 0;
$copied += copyTree($source . '/app', $root . '/app');
$copied += copyTree($source . '/database', $root . '/database');
$copied += copyTree($source . '/tools', $root . '/tools');
if (is_file($source . '/.htaccess')) {
    copy($source . '/.htaccess', $root . '/.htaccess');
    $copied++;
}

$publicTarget = is_dir($root . '/public_html')
    ? $root . '/public_html'
    : (is_file($web . '/index.php') && !is_dir($web . '/app') ? $web : $root . '/public');
$copied += copyTree($source . '/public', $publicTarget);

$configDir = $root . '/config';
if (!is_dir($configDir)) {
    mkdir($configDir, 0750, true);
}
if (!is_file($configDir . '/config.php') && is_file($source . '/config/config.sample.php')) {
    copy($source . '/config/config.sample.php', $configDir . '/config.sample.php');
}

@unlink($zipPath);
rrmdir($extractDir);

$self = __FILE__;
echo "copied=$copied\npublic_target=$publicTarget\n";
echo "done. Open https://kvartiraformat.ru/ and https://kvartiraformat.ru/remont-domov/\n";
@unlink($self);

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
