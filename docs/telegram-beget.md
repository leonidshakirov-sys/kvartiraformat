# Telegram на Beget: диагностика и запасной канал

Заявки сайта сохраняются в MySQL **до** вызова Telegram. Если `api.telegram.org` недоступен, обращения остаются в `/admin/leads`.

## Что уже известно

Ответ поддержки Beget: Telegram блокируется в РФ на ТСПУ, хостинг не может снять блокировку. Типичная ошибка в админке:

`Telegram: Connection timed out after … milliseconds`

Это не доказательство неверного token/chat_id, пока не проверена сеть.

## Диагностика на сервере

Только CLI, без вывода токена:

```bash
cd ~/kvartiraformat.ru
# или каталог, куда загружен проект
php tools/telegram_diagnose.php
```

Скрипт проверяет:

- DNS A/AAAA для `api.telegram.org`
- TCP `:443`
- TLS с проверкой сертификата
- cURL default / IPv4 / IPv6
- контрольный исходящий HTTPS (`example.com`)
- при наличии токена — `getMe` (URL в логе с `[redacted-token]`)

Таймауты: connect 5 с, общий 10 с. `CURLOPT_SSL_VERIFYPEER` включён.

Отчёт: `storage/logs/telegram-diagnose-*.json`

## Если сеть до Telegram закрыта

1. Оставьте заявки в админке — это основной учёт.
2. В `/admin/integrations` включите email и укажите рабочий ящик.
3. Cron раз в 5 минут: `php tools/telegram_retry.php` — очередь уйдёт, если блок снимут.
4. Не отключайте проверку TLS и не подставляйте чужие IP Telegram.

## Конфигурация

В `config/config.php` (не коммитить):

```php
'telegram' => [
    'bot_token' => '...',
    'chat_id' => '...',
    'connect_timeout' => 5,
    'timeout' => 10,
],
```

На Beget не ставьте `timeout` 20+: форма и кнопка теста будут висеть до отсечки без шанса пробить ТСПУ.
