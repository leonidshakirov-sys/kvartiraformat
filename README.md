# КВАРТИРАФОРМАТ — сайт ремонта квартир под ключ

PHP/MySQL-сайт под обычный виртуальный хостинг Beget: публичные SEO-страницы, калькулятор, заявки, Telegram-очередь и русскоязычная административная панель.

## Стек

- PHP 8.2+
- MySQL/MariaDB
- Apache + `.htaccess`
- PDO, HTML/CSS/JS без постоянно работающего Node.js

## Структура

- `public/` — web-root, `index.php`, assets, uploads.
- `app/` — контроллеры, сервисы, репозиторий, шаблоны.
- `config/config.sample.php` — пример конфигурации без секретов.
- `database/database.sql` — схема и стартовый контент.
- `storage/` — логи и резервные копии вне web-root.
- `tools/telegram_retry.php` — Cron-повтор отправки Telegram.

## Установка на Beget

1. Создайте сайт и направьте web-root на папку `public`.
2. Создайте MySQL-базу в панели Beget.
3. Импортируйте `database/database.sql` через phpMyAdmin или CLI.
4. Скопируйте `config/config.sample.php` в `config/config.php` и заполните доступы к БД.
5. Укажите `app.base_url`, `app.canonical_host`, `telegram.bot_token`, `telegram.chat_id` при наличии тестового бота.
6. Откройте `/admin/setup` и создайте первого администратора. Пароль не хранится в коде, в БД записывается хеш.
7. Проверьте `/`, `/contacts/`, `/portfolio/`, `/sitemap.xml`, `/robots.txt`.
8. Настройте Cron: `php /path/to/project/tools/telegram_retry.php` каждые 5 минут.
9. После подключения SSL включите canonical HTTPS-редирект в `public/.htaccess`.
10. До финального запуска закройте тестовую среду HTTP-авторизацией или ограничением доступа на уровне хостинга; одного `robots.txt` недостаточно.

## Telegram

- Токен бота и chat_id хранятся только в `config/config.php`.
- Заявка сначала сохраняется в MySQL, затем ставится в `telegram_queue`.
- Если Telegram недоступен, Cron повторит отправку.
- Кнопка тестового уведомления находится в `/admin/integrations`.

## SEO

Реализованы SSR-контент, ЧПУ, canonical, Open Graph, Schema.org организации, динамический `sitemap.xml`, `robots.txt`, хлебные крошки, ALT-поля для медиа и управляемые SEO-поля страниц. Не генерируйте массово страницы улиц/ЖК без полезного локального содержания.

## Персональные данные

Юридические страницы `/privacy-policy/`, `/personal-data-consent/`, `/cookies/` подготовлены как рабочие тексты для проверки. Перед запуском необходимо юридически проверить документы, оценить передачу в Telegram и необходимость уведомления Роскомнадзора.

## Безопасность

- Пароли хешируются `password_hash`.
- Формы защищены CSRF-токенами и honeypot-полем.
- SQL выполняется через PDO prepared statements.
- Uploads проверяются по расширению и MIME, исполнение PHP в `public/uploads` запрещено.
- Лимит попыток входа хранится в `login_attempts`.
- Секреты не коммитятся: `config/config.php` в `.gitignore`.

## Проверка перед публикацией

См. `docs/launch-checklist.md` и `docs/qa-report.md`.
