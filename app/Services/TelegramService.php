<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

final class TelegramService
{
    public function __construct(private Config $config, private Database $db)
    {
    }

    public function isConfigured(): bool
    {
        return (string)$this->config->get('telegram.bot_token', '') !== '' && (string)$this->config->get('telegram.chat_id', '') !== '';
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
        foreach ($stmt->fetchAll() as $row) {
            $result = $this->sendMessage($row['message']);
            if ($result['ok']) {
                $sent++;
                $pdo->prepare('UPDATE telegram_queue SET status="sent", attempts=attempts+1, last_error=NULL, sent_at=NOW(), updated_at=NOW() WHERE id=?')->execute([(int)$row['id']]);
            } else {
                $failed++;
                $pdo->prepare('UPDATE telegram_queue SET status="failed", attempts=attempts+1, last_error=?, updated_at=NOW() WHERE id=?')->execute([$result['error'], (int)$row['id']]);
            }
        }
        return ['sent' => $sent, 'failed' => $failed, 'error' => null];
    }

    public function sendTest(string $text = ''): array
    {
        $text = $text ?: "🔔 КВАРТИРАФОРМАТ — тестовое уведомление\nИнтеграция настроена, если это сообщение получено в нужном чате.";
        return $this->sendMessage($text);
    }

    private function sendMessage(string $message): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Telegram не настроен'];
        }
        $token = (string)$this->config->get('telegram.bot_token');
        $chatId = (string)$this->config->get('telegram.chat_id');
        $url = 'https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage';
        $payload = http_build_query([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => '1',
        ]);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => (int)$this->config->get('telegram.timeout', 6),
                'ignore_errors' => true,
            ],
        ]);
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return ['ok' => false, 'error' => 'Не удалось подключиться к Telegram Bot API'];
        }
        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['ok'])) {
            return ['ok' => false, 'error' => $decoded['description'] ?? 'Telegram вернул ошибку'];
        }
        return ['ok' => true, 'error' => null];
    }
}
