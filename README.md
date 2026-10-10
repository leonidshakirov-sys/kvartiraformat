# КВАРТИРАФОРМАТ — сайт ремонта квартир под ключ

PHP/MySQL-сайт под обычный виртуальный хостинг Beget: публичные SEO-страницы, калькулятор, заявки, Telegram-очередь и русскоязычная административная панель.

## Стек

- PHP 8.2+
- PHP-расширения: PDO, pdo_mysql, mbstring, fileinfo/GD желательно для расширенной обработки изображений
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
   - Если на виртуальном хостинге Beget web-root уже фиксирован как `public_html`, загрузите проект целиком в `public_html`: корневой `.htaccess` перенаправит публичные запросы в `/public` и запретит доступ к закрытым папкам.
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
- С Beget исходящий HTTPS к `api.telegram.org` часто блокируется ТСПУ. Это не ломает сохранение заявок.
- Диагностика на сервере: `php tools/telegram_diagnose.php` (см. `docs/telegram-beget.md`).
- Запасной канал: email в `/admin/integrations`.
- Cron: `php tools/telegram_retry.php` каждые 5 минут.

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

## Коммерческий ремонт

Новое направление встроено в текущий сайт, без отдельной админки и без удаления квартирных страниц.

- Общая страница: `/remont-kommercheskih-pomescheniy/`
- Услуги: `/remont-ofisov/`, `/remont-magazinov/`, `/remont-salonov-krasoty/`, `/remont-kafe-restoranov/`, `/remont-skladskih-pomescheniy/`, `/remont-pomescheniy-svobodnogo-naznacheniya/`
- Калькулятор коммерческого режима с вложением ТЗ/плана (файлы в `storage/lead-files/`, скачивание только из админки)
- Тип заявки «Коммерческий ремонт», фильтр в `/admin/leads` и раздел `/admin/commercial`
- Фото: `public/assets/images/commercial/*.webp`

На уже работающей базе колонки добавляются автоматически при первом запросе к сайту (с резервной копией в `storage/backups/`). Для ручного импорта есть `database/commercial-repair.sql`.

## Проверка перед публикацией

См. `docs/launch-checklist.md` и `docs/qa-report.md`.
