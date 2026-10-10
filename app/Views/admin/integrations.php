<?php $s = $settings ?? []; ?>
<section class="admin-panel">
    <h1>Интеграции</h1>
    <p>Заявки сначала сохраняются в MySQL и видны в разделе «Заявки». Telegram — отдельный канал уведомления. Токен бота и chat_id хранятся только в <code>config/config.php</code> и не показываются здесь.</p>
    <p><strong>Статус Telegram:</strong> <?= !empty($telegramConfigured) ? 'токен и chat_id заданы в конфигурации сервера.' : 'токен или chat_id не заданы в config/config.php.' ?></p>
    <form method="post" class="admin-form inline">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="telegram_test">
        <button class="btn btn-primary">Отправить тестовое уведомление в Telegram</button>
    </form>
    <p class="muted">Если тест завершается фразой Connection timed out, это отказ сети до api.telegram.org, а не ошибка формы заявки. Поддержка Beget не снимает блокировку Telegram на ТСПУ.</p>
</section>
<section class="admin-panel">
    <h2>Очередь Telegram</h2>
    <p>Неуспешные сообщения остаются в очереди. Cron <code>php tools/telegram_retry.php</code> повторит отправку, если доступ к API появится.</p>
    <div class="table-wrap">
        <table>
            <tr><th>ID</th><th>Заявка</th><th>Статус</th><th>Попытки</th><th>Ошибка</th><th>Обновлено</th></tr>
            <?php foreach (($telegramQueue ?? []) as $row): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= (int)($row['lead_id'] ?? 0) ?></td>
                    <td><?= e((string)$row['status']) ?></td>
                    <td><?= (int)$row['attempts'] ?></td>
                    <td><?= e((string)($row['last_error'] ?? '')) ?></td>
                    <td><?= e((string)($row['updated_at'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<section class="admin-panel">
    <h2>Запасной канал: email</h2>
    <p>Пока Telegram с Beget недоступен, копия заявки уходит на почту через <code>mail()</code> хостинга. Это не заменяет заявки в админке.</p>
    <form method="post" class="admin-form grid">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_notify">
        <label>Email для заявок<input type="email" name="notify_email" value="<?= e($s['notify_email'] ?? '') ?>" placeholder="owner@example.com"></label>
        <label>От кого (необязательно)<input type="email" name="notify_email_from" value="<?= e($s['notify_email_from'] ?? '') ?>" placeholder="noreply@kvartiraformat.ru"></label>
        <label><input type="checkbox" name="notify_email_enabled" value="1" <?= ($s['notify_email_enabled'] ?? '0') === '1' ? 'checked' : '' ?>> Включить email при недоступном Telegram</label>
        <button class="btn btn-primary">Сохранить почту</button>
    </form>
    <form method="post" class="admin-form inline">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="email_test">
        <input type="hidden" name="notify_email" value="<?= e($s['notify_email'] ?? '') ?>">
        <input type="hidden" name="notify_email_from" value="<?= e($s['notify_email_from'] ?? '') ?>">
        <input type="hidden" name="notify_email_enabled" value="1">
        <button class="btn btn-outline" type="submit">Отправить тестовое письмо</button>
    </form>
    <p><?= !empty($mailConfigured) ? 'Email-уведомления включены.' : 'Email-уведомления выключены.' ?></p>
</section>
<section class="admin-panel">
    <h2>Диагностика сети на Beget</h2>
    <p>По SSH или в «Cron / PHP» панели Beget выполните в каталоге сайта:</p>
    <p><code>php tools/telegram_diagnose.php</code></p>
    <p>Скрипт проверяет DNS, TCP :443, TLS и cURL к официальному <code>https://api.telegram.org/</code> с проверкой сертификата. Токен в вывод не попадает. Подробный JSON пишется в <code>storage/logs/</code>.</p>
</section>
<section class="admin-panel">
    <h2>Яндекс Метрика</h2>
    <form method="post" class="admin-form grid">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_settings">
        <label>Яндекс Метрика ID<input name="ym_counter_id" value="<?= e($s['ym_counter_id'] ?? '') ?>"></label>
        <button class="btn btn-primary">Сохранить Метрику</button>
    </form>
</section>
