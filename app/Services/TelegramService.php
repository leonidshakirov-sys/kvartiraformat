<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

final class TelegramService
{
    public const API_HOST = 'api.telegram.org';

    public function __construct(private Config $config, private Database $db)
    {
    }

    public function isConfigured(): bool
    {
        return $this->botToken() !== '' && $this->chatId() !== '';
    }

    public function queue(int $leadId, string $message): void
    {
        $pdo = $this->db->pdo();
        if (!$pdo) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO telegram_queue (lead_id, message, status, attempts, created_at, updated_at) VALUES (?, ?, "pending", 0, NOW(), NOW())');
        $stmt->execute([$leadId, $message]);
    }

    public function sendPending(int $limit = 10): array
    {
        $pdo = $this->db->pdo();
        if (!$pdo) {
            return ['sent' => 0, 'failed' => 0, 'error' => $this->db->error()];
        }
        if (!$this->isConfigured()) {
            return ['sent' => 0, 'failed' => 0, 'error' => 'Telegram token/chat_id не настроены в config/config.php'];
        }
        $stmt = $pdo->prepare("SELECT * FROM telegram_queue WHERE status IN ('pending','failed') AND attempts < 10 ORDER BY created_at LIMIT ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $sent = 0;
        $failed = 0;
        $lastError = null;
        foreach ($stmt->fetchAll() as $row) {
            $result = $this->sendMessage($row['message']);
            if ($result['ok']) {
                $sent++;
                $pdo->prepare('UPDATE telegram_queue SET status="sent", attempts=attempts+1, last_error=NULL, sent_at=NOW(), updated_at=NOW() WHERE id=?')->execute([(int)$row['id']]);
            } else {
                $failed++;
                $lastError = $result['error'];
                $pdo->prepare('UPDATE telegram_queue SET status="failed", attempts=attempts+1, last_error=?, updated_at=NOW() WHERE id=?')->execute([$result['error'], (int)$row['id']]);
            }
        }
        return ['sent' => $sent, 'failed' => $failed, 'error' => $lastError];
    }

    public function sendTest(string $text = ''): array
    {
        $text = $text ?: "🔔 КВАРТИРАФОРМАТ — тестовое уведомление\nИнтеграция настроена, если это сообщение получено в нужном чате.";
        return $this->sendMessage($text);
    }

    /**
     * @return array{ok:bool,error:?string,http_status:?int,stage:?string}
     */
    public function sendMessage(string $message): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Telegram не настроен', 'http_status' => null, 'stage' => 'config'];
        }
        $payload = [
            'chat_id' => $this->chatId(),
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => '1',
        ];
        $response = $this->postOfficial('sendMessage', $payload);
        if ($response['body'] === null) {
            return [
                'ok' => false,
                'error' => $response['error'] ?: 'Не удалось подключиться к Telegram Bot API',
                'http_status' => $response['http_status'],
                'stage' => $response['stage'],
            ];
        }
        $decoded = json_decode((string)$response['body'], true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            return [
                'ok' => false,
                'error' => $this->redact((string)($decoded['description'] ?? 'Telegram вернул ошибку')),
                'http_status' => $response['http_status'],
                'stage' => 'api',
            ];
        }
        return ['ok' => true, 'error' => null, 'http_status' => $response['http_status'], 'stage' => 'ok'];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function recentQueue(int $limit = 8): array
    {
        $pdo = $this->db->pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT id, lead_id, status, attempts, last_error, sent_at, created_at, updated_at FROM telegram_queue ORDER BY id DESC LIMIT ?');
            $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll() ?: [];
            foreach ($rows as &$row) {
                $row['last_error'] = $this->redact((string)($row['last_error'] ?? ''));
            }
            unset($row);
            return $rows;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param array<string, string> $payload
     * @return array{body:?string,error:?string,http_status:?int,stage:?string}
     */
    private function postOfficial(string $method, array $payload): array
    {
        $url = 'https://' . self::API_HOST . '/bot' . $this->botToken() . '/' . $method;
        $timeout = max(5, min(15, (int)$this->config->get('telegram.timeout', 10)));
        $connect = max(3, min(8, (int)$this->config->get('telegram.connect_timeout', 5)));

        if (function_exists('curl_init')) {
            $ipv4 = $this->curlPost($url, $payload, $connect, $timeout, 1);
            if ($ipv4['body'] !== null) {
                return $ipv4;
            }
            $any = $this->curlPost($url, $payload, $connect, $timeout, 0);
            if ($any['body'] !== null) {
                return $any;
            }
            return [
                'body' => null,
                'error' => $this->redact((string)($any['error'] ?: $ipv4['error'] ?: 'Не удалось подключиться к Telegram Bot API')),
                'http_status' => $any['http_status'] ?? $ipv4['http_status'] ?? null,
                'stage' => $any['stage'] ?? $ipv4['stage'] ?? 'connect',
            ];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return [
                'body' => null,
                'error' => 'Не удалось подключиться к Telegram Bot API. На Beget исходящие запросы к api.telegram.org могут быть ограничены.',
                'http_status' => null,
                'stage' => 'connect',
            ];
        }
        return ['body' => $body, 'error' => null, 'http_status' => 200, 'stage' => 'ok'];
    }

    /**
     * @param array<string, string> $payload
     * @return array{body:?string,error:?string,http_status:?int,stage:?string}
     */
    private function curlPost(string $url, array $payload, int $connectTimeout, int $timeout, int $ipResolve): array
    {
        $curl = curl_init($url);
        if ($curl === false) {
            return ['body' => null, 'error' => 'Не удалось инициализировать curl', 'http_status' => null, 'stage' => 'init'];
        }
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_IPRESOLVE => $ipResolve,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        $http = isset($info['http_code']) ? (int)$info['http_code'] : 0;
        if ($body === false) {
            $stage = $this->stageFromCurl((float)($info['namelookup_time'] ?? 0), (float)($info['connect_time'] ?? 0), (float)($info['appconnect_time'] ?? 0), $errno);
            $label = $error !== '' ? ('Telegram: ' . $error) : 'Не удалось подключиться к Telegram Bot API';
            return ['body' => null, 'error' => $this->redact($label), 'http_status' => $http ?: null, 'stage' => $stage];
        }
        if ($http >= 400) {
            return ['body' => (string)$body, 'error' => 'HTTP ' . $http, 'http_status' => $http, 'stage' => 'http'];
        }
        return ['body' => (string)$body, 'error' => null, 'http_status' => $http, 'stage' => 'ok'];
    }

    private function stageFromCurl(float $dns, float $connect, float $tls, int $errno): string
    {
        if ($errno === 6) {
            return 'dns';
        }
        if ($dns <= 0 && $connect <= 0) {
            return 'dns';
        }
        if ($connect <= 0) {
            return 'tcp';
        }
        if ($tls <= 0) {
            return 'tls';
        }
        return 'transfer';
    }

    private function botToken(): string
    {
        return trim((string)$this->config->get('telegram.bot_token', ''));
    }

    private function chatId(): string
    {
        return trim((string)$this->config->get('telegram.chat_id', ''));
    }

    private function redact(string $text): string
    {
        $token = $this->botToken();
        if ($token !== '') {
            $text = str_replace($token, '[redacted-token]', $text);
        }
        return preg_replace('#bot[0-9]+:[A-Za-z0-9_-]+#', 'bot[redacted-token]', $text) ?? $text;
    }
}
