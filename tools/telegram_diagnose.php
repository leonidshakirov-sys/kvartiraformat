<?php

declare(strict_types=1);

/**
 * CLI-диагностика исходящего HTTPS к официальному Telegram Bot API.
 * Запуск на Beget: php ~/kvartiraformat.ru/tools/telegram_diagnose.php
 * Не печатает bot token и chat_id.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Только CLI\n";
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

$host = 'api.telegram.org';
$controlUrl = 'https://example.com/';
$telegramUrl = 'https://api.telegram.org/';
$connectTimeout = 5;
$timeout = 10;

$report = [
    'generated_at' => date('c'),
    'php' => PHP_VERSION,
    'curl_version' => function_exists('curl_version') ? (curl_version()['version'] ?? 'yes') : 'no',
    'openssl' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'no',
    'dns' => [],
    'tcp' => [],
    'tls' => [],
    'curl' => [],
    'control_https' => [],
    'bot_api' => [],
    'summary' => [],
];

$report['dns'] = diagnoseDns($host);
$report['tcp']['ipv4'] = diagnoseTcp($host, 443, STREAM_CLIENT_CONNECT, $connectTimeout, false);
$report['tls']['ipv4'] = diagnoseTls($host, 443, $connectTimeout);
$report['curl']['default'] = diagnoseCurl($telegramUrl, $connectTimeout, $timeout, 0);
$report['curl']['ipv4'] = diagnoseCurl($telegramUrl, $connectTimeout, $timeout, 1);
$report['curl']['ipv6'] = diagnoseCurl($telegramUrl, $connectTimeout, $timeout, 2);
$report['control_https'] = diagnoseCurl($controlUrl, $connectTimeout, $timeout, 1);
$report['bot_api'] = diagnoseBot($root, $connectTimeout, $timeout);

$summary = summarize($report);
$report['summary'] = $summary;

$logDir = $root . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}
$logFile = $logDir . '/telegram-diagnose-' . date('Ymd-His') . '.json';
@file_put_contents($logFile, json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo "=== КвартираФормат — диагностика Telegram ===\n";
echo "DNS: {$summary['dns']}\n";
echo "TCP :443: {$summary['tcp']}\n";
echo "TLS: {$summary['tls']}\n";
echo "IPv4: {$summary['ipv4']}\n";
echo "IPv6: {$summary['ipv6']}\n";
echo "Исходящий HTTPS (контрольный сайт): {$summary['control_https']}\n";
echo "Этап зависания: {$summary['hangs_at']}\n";
echo "Вывод: {$summary['conclusion']}\n";
if (!empty($logFile) && is_file($logFile)) {
    echo "Подробный лог (без токенов): {$logFile}\n";
}
exit($summary['ok'] ? 0 : 2);

/**
 * @return array<string,mixed>
 */
function diagnoseDns(string $host): array
{
    $ipv4 = [];
    $ipv6 = [];
    $error = null;
    $a = @dns_get_record($host, DNS_A) ?: [];
    foreach ($a as $row) {
        if (!empty($row['ip'])) {
            $ipv4[] = $row['ip'];
        }
    }
    $aaaa = @dns_get_record($host, DNS_AAAA) ?: [];
    foreach ($aaaa as $row) {
        if (!empty($row['ipv6'])) {
            $ipv6[] = $row['ipv6'];
        }
    }
    $byName = @gethostbyname($host);
    if ($byName && $byName !== $host && !in_array($byName, $ipv4, true)) {
        $ipv4[] = $byName;
    }
    if (!$ipv4 && !$ipv6) {
        $error = 'DNS не вернул A/AAAA для api.telegram.org';
    }
    return [
        'host' => $host,
        'ok' => $ipv4 || $ipv6,
        'ipv4' => $ipv4,
        'ipv6' => $ipv6,
        'error' => $error,
    ];
}

/**
 * @return array<string,mixed>
 */
function diagnoseTcp(string $host, int $port, int $flags, int $timeout, bool $useSsl): array
{
    $target = ($useSsl ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $errno = 0;
    $errstr = '';
    $start = microtime(true);
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);
    $fp = @stream_socket_client($target, $errno, $errstr, $timeout, $flags, $ctx);
    $elapsed = round(microtime(true) - $start, 3);
    if (is_resource($fp)) {
        fclose($fp);
        return ['ok' => true, 'target' => $target, 'seconds' => $elapsed, 'error' => null];
    }
    return [
        'ok' => false,
        'target' => $target,
        'seconds' => $elapsed,
        'errno' => $errno,
        'error' => $errstr !== '' ? $errstr : 'тайм-аут или отказ соединения',
    ];
}

/**
 * @return array<string,mixed>
 */
function diagnoseTls(string $host, int $port, int $timeout): array
{
    return diagnoseTcp($host, $port, STREAM_CLIENT_CONNECT, $timeout, true);
}

/**
 * @return array<string,mixed>
 */
function diagnoseCurl(string $url, int $connectTimeout, int $timeout, int $ipResolve): array
{
    if (!function_exists('curl_init')) {
        return diagnoseHttpsStream($url, $timeout);
    }
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_IPRESOLVE => $ipResolve,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT => 'KvartiraFormat-TelegramDiagnose/1.0',
    ]);
    $body = curl_exec($curl);
    $info = curl_getinfo($curl) ?: [];
    $errno = curl_errno($curl);
    $error = curl_error($curl);
    curl_close($curl);
    $http = (int)($info['http_code'] ?? 0);
    return [
        'ok' => $body !== false && $http > 0 && $http < 500,
        'url' => $url,
        'ip_resolve' => $ipResolve === 1 ? 'v4' : ($ipResolve === 2 ? 'v6' : 'default'),
        'curl_errno' => $errno,
        'curl_error' => $error !== '' ? $error : null,
        'http_status' => $http ?: null,
        'namelookup_time' => $info['namelookup_time'] ?? null,
        'connect_time' => $info['connect_time'] ?? null,
        'appconnect_time' => $info['appconnect_time'] ?? null,
        'starttransfer_time' => $info['starttransfer_time'] ?? null,
        'total_time' => $info['total_time'] ?? null,
        'primary_ip' => $info['primary_ip'] ?? null,
    ];
}

/**
 * @return array<string,mixed>
 */
function diagnoseHttpsStream(string $url, int $timeout): array
{
    $start = microtime(true);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'ignore_errors' => true,
            'header' => "User-Agent: KvartiraFormat-TelegramDiagnose/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $elapsed = round(microtime(true) - $start, 3);
    $status = null;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int)$m[1];
    }
    return [
        'ok' => $body !== false && $status !== null && $status < 500,
        'url' => $url,
        'ip_resolve' => 'stream',
        'curl_errno' => null,
        'curl_error' => $body === false ? 'ext-curl нет, file_get_contents не получил ответ' : null,
        'http_status' => $status,
        'total_time' => $elapsed,
        'transport' => 'php-stream',
    ];
}

/**
 * @return array<string,mixed>
 */
function diagnoseBot(string $root, int $connectTimeout, int $timeout): array
{
    $config = new App\Core\Config($root);
    $token = trim((string)$config->get('telegram.bot_token', ''));
    $chatId = trim((string)$config->get('telegram.chat_id', ''));
    $result = [
        'token_present' => $token !== '',
        'chat_id_present' => $chatId !== '',
        'getMe' => null,
        'note' => 'Токен и chat_id не выводятся.',
    ];
    if ($token === '' || !function_exists('curl_init')) {
        $result['getMe'] = ['skipped' => true, 'reason' => $token === '' ? 'токен не задан в config/config.php' : 'нет curl'];
        return $result;
    }
    $url = 'https://api.telegram.org/bot' . $token . '/getMe';
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => $connectTimeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_IPRESOLVE => 1,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($curl);
    $info = curl_getinfo($curl) ?: [];
    $errno = curl_errno($curl);
    $error = curl_error($curl);
    curl_close($curl);
    $decoded = is_string($body) ? json_decode($body, true) : null;
    $result['getMe'] = [
        'ok' => is_array($decoded) && !empty($decoded['ok']),
        'http_status' => (int)($info['http_code'] ?? 0) ?: null,
        'curl_errno' => $errno,
        'curl_error' => $error !== '' ? $error : null,
        'namelookup_time' => $info['namelookup_time'] ?? null,
        'connect_time' => $info['connect_time'] ?? null,
        'appconnect_time' => $info['appconnect_time'] ?? null,
        'starttransfer_time' => $info['starttransfer_time'] ?? null,
        'total_time' => $info['total_time'] ?? null,
        'telegram_ok' => is_array($decoded) ? (!empty($decoded['ok'])) : null,
        'telegram_description' => is_array($decoded) ? ($decoded['description'] ?? null) : null,
        'url' => 'https://api.telegram.org/bot[redacted-token]/getMe',
    ];
    return $result;
}

/**
 * @param array<string,mixed> $report
 * @return array<string,mixed>
 */
function summarize(array $report): array
{
    $dnsOk = !empty($report['dns']['ok']);
    $tcpOk = !empty($report['tcp']['ipv4']['ok']);
    $tlsOk = !empty($report['tls']['ipv4']['ok']);
    $v4 = !empty($report['curl']['ipv4']['ok']);
    $v6 = !empty($report['curl']['ipv6']['ok']) && (($report['curl']['ipv6']['ip_resolve'] ?? '') === 'v6');
    $control = !empty($report['control_https']['ok']);
    $telegramHttp = $v4 || !empty($report['curl']['default']['ok']);
    $hangs = 'нет зависания, запрос завершился';
    $ipv4Curl = $report['curl']['ipv4'] ?? [];
    if (!$dnsOk) {
        $hangs = 'DNS';
    } elseif (!$tcpOk) {
        $hangs = 'TCP connect к :443 (часто ТСПУ/блок Telegram)';
    } elseif ($tcpOk && !$tlsOk) {
        $hangs = 'TLS handshake';
    } elseif (!$telegramHttp && (int)($ipv4Curl['curl_errno'] ?? 0) === 28) {
        $hangs = 'тайм-аут HTTP-запроса к api.telegram.org (блок или фильтрация Telegram)';
    } elseif (!$telegramHttp && !$tlsOk) {
        $hangs = 'TCP/TLS до api.telegram.org';
    }
    $conclusion = 'Сеть до официального api.telegram.org с этой машины не подтверждена.';
    if ($telegramHttp || ($tlsOk && $tcpOk)) {
        $conclusion = 'Официальный api.telegram.org с этой машины сетевой доступен. Если sendMessage падает при живом токене — смотрите ответ Bot API (chat_id, права бота), не хостинг.';
    }
    if ($control && !$telegramHttp && !$tlsOk) {
        $conclusion = 'Исходящий HTTPS работает, а api.telegram.org — нет. Это сетевая блокировка Telegram (ТСПУ/RKN), а не ошибка форм сайта. Заявки сохраняются в админке; включите email-уведомления.';
    } elseif (!$control && !$telegramHttp && !$tlsOk && !$tcpOk) {
        $conclusion = 'Исходящий HTTPS с этой машины ограничен шире, чем только Telegram. Проверьте PHP cURL и политику хостинга.';
    }
    return [
        'ok' => $v4,
        'dns' => $dnsOk ? 'работает' : 'не работает',
        'tcp' => $tcpOk ? 'работает' : 'не работает',
        'tls' => $tlsOk ? 'работает' : 'не работает',
        'ipv4' => ($v4 || ($tcpOk && $tlsOk)) ? 'работает' : 'не работает',
        'ipv6' => $v6 ? 'работает' : 'не подтверждён отдельным успешным запросом',
        'control_https' => $control ? 'работает' : 'не работает',
        'hangs_at' => $hangs,
        'conclusion' => $conclusion,
    ];
}
