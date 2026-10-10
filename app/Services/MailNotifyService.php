<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\SiteRepository;

final class MailNotifyService
{
    public function __construct(private Config $config, private SiteRepository $repo)
    {
    }

    public function isConfigured(): bool
    {
        $settings = $this->repo->settings();
        if (($settings['notify_email_enabled'] ?? '0') !== '1') {
            return false;
        }
        return $this->toAddress($settings) !== '';
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public function sendLeadCopy(string $subject, string $text): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Email-уведомления не включены'];
        }
        return $this->send($subject, $text);
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public function sendTest(): array
    {
        return $this->send(
            'КвартираФормат — тестовое письмо',
            "Это проверка запасного канала уведомлений.\nЕсли письмо пришло, почта на хостинге работает.\nTelegram при этом может оставаться недоступен с серверов Beget."
        );
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    private function send(string $subject, string $text): array
    {
        $settings = $this->repo->settings();
        $to = $this->toAddress($settings);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Укажите корректный email для уведомлений'];
        }
        $from = trim((string)($settings['notify_email_from'] ?? ''));
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $domain = preg_replace('/^www\./', '', (string)($settings['domain'] ?? 'kvartiraformat.ru'));
            $from = 'noreply@' . $domain;
        }
        $plain = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', "\n"], "\n", $text)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $plain = trim(preg_replace("/[ \t]+/", ' ', $plain) ?? $plain);
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: КвартираФормат <' . $from . '>',
            'Reply-To: ' . $from,
            'X-Mailer: KvartiraFormat',
        ];
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $ok = @mail($to, $encodedSubject, $plain, implode("\r\n", $headers));
        if (!$ok) {
            return ['ok' => false, 'error' => 'Функция mail() на хостинге вернула ошибку. Проверьте почту домена в панели Beget.'];
        }
        return ['ok' => true, 'error' => null];
    }

    /**
     * @param array<string,mixed> $settings
     */
    private function toAddress(array $settings): string
    {
        $fromSettings = trim((string)($settings['notify_email'] ?? ''));
        if ($fromSettings !== '') {
            return $fromSettings;
        }
        return trim((string)$this->config->get('mail.to', ''));
    }
}
