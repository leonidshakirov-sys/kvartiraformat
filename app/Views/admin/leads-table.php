<?php
$leadLabels = ['apartment' => 'Квартира', 'house' => 'Дом', 'commercial' => 'Коммерция'];
?>
<div class="table-wrap"><table><thead><tr><th>ID</th><th>Тип</th><th>Дата</th><th>Имя</th><th>Телефон</th><th>Город</th><th>Объект / ремонт</th><th>Оценка</th><th>Прикреплённые файлы</th><th>Статус</th></tr></thead><tbody><?php foreach(array_slice($leads,0,40) as $lead): ?>
<tr>
<td><?= e($lead['public_id'] ?? (string)$lead['id']) ?></td>
<td><?= e($leadLabels[$lead['lead_type'] ?? 'apartment'] ?? 'Квартира') ?></td>
<td><?= e($lead['created_at'] ?? '') ?></td>
<td><?= e($lead['name'] ?? '') ?></td>
<td><?= e($lead['phone'] ?? '') ?></td>
<td><?= e($lead['city'] ?? '') ?></td>
<td>
    <?php
        $payload = json_decode((string)($lead['quiz_payload'] ?? ''), true);
        $lines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];
        $utm = array_filter([
            $lead['utm_source'] ?? '',
            $lead['utm_medium'] ?? '',
            $lead['utm_campaign'] ?? '',
            $lead['utm_content'] ?? '',
            $lead['utm_term'] ?? '',
        ], static fn($value) => trim((string)$value) !== '');
    ?>
    <dl class="lead-quiz">
        <dt>Объект</dt><dd><?= e(trim((string)($lead['property_type'] ?? '')) !== '' ? (string)$lead['property_type'] : 'не указан') ?></dd>
        <dt>Ремонт</dt><dd><?= e(trim((string)($lead['repair_type'] ?? '')) !== '' ? (string)$lead['repair_type'] : 'не указан') ?></dd>
        <dt>Площадь</dt><dd><?= isset($lead['area']) && $lead['area'] !== '' && $lead['area'] !== null ? e((string)$lead['area'] . ' м²') : 'не указана' ?></dd>
        <dt>Состояние</dt><dd><?= e(trim((string)($lead['premise_condition'] ?? '')) !== '' ? (string)$lead['premise_condition'] : 'не указано') ?></dd>
        <dt>Дополнительно</dt><dd><?= e(trim((string)($lead['extras'] ?? '')) !== '' ? (string)$lead['extras'] : 'не указано') ?></dd>
        <?php if (trim((string)($lead['rooms'] ?? '')) !== ''): ?><dt>Комнаты или этажи</dt><dd><?= e((string)$lead['rooms']) ?></dd><?php endif; ?>
        <?php if (trim((string)($lead['has_project'] ?? '')) !== ''): ?><dt>Проект</dt><dd><?= e((string)$lead['has_project']) ?></dd><?php endif; ?>
        <dt>Источник</dt><dd><?= e(trim((string)($lead['source'] ?? '')) !== '' ? (string)$lead['source'] : 'сайт') ?></dd>
        <dt>UTM</dt><dd><?= e($utm ? implode(' / ', $utm) : 'нет') ?></dd>
        <?php if (trim((string)($lead['comment'] ?? '')) !== ''): ?><dt>Комментарий</dt><dd><?= e((string)$lead['comment']) ?></dd><?php endif; ?>
        <?php if (!empty($payload['summary'])): ?><dt>Расчёт квиза</dt><dd><?= e((string)$payload['summary']) ?></dd><?php endif; ?>
        <?php foreach ($lines as $label => $value): if (trim((string)$value) === '') continue; ?>
            <dt><?= e((string)$label) ?></dt><dd><?= e((string)$value) ?></dd>
        <?php endforeach; ?>
    </dl>
</td>
<td><?= e($lead['estimate_text'] ?? '') ?></td>
<td>
    <?php $files = $lead['files'] ?? []; if (!$files): ?>
        <span class="muted">Файлов нет</span>
    <?php else: ?>
        <div class="lead-attachments">
            <?php foreach ($files as $file):
                $mime = strtolower((string)($file['mime_type'] ?? ''));
                $isImage = in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
                $size = (int)($file['size_bytes'] ?? 0);
            ?>
                <article>
                    <strong><?= e((string)$file['original_name']) ?></strong>
                    <small><?= e($mime !== '' ? $mime : 'файл') ?> · <?= $size > 0 ? e((string)round($size / 1024)) . ' КБ' : 'размер неизвестен' ?> · <?= e((string)($file['created_at'] ?? '')) ?></small>
                    <?php if ($isImage): ?>
                        <img class="attachment-preview" src="/admin/leads/file?id=<?= (int)$file['id'] ?>&preview=1" alt="<?= e((string)$file['original_name']) ?>">
                    <?php endif; ?>
                    <a href="/admin/leads/file?id=<?= (int)$file['id'] ?>">Скачать</a>
                    <form method="post" class="inline-delete" onsubmit="return confirm('Удалить файл из заявки?');">
                        <?= App\Core\Csrf::field() ?>
                        <input type="hidden" name="action" value="delete_lead_file">
                        <input type="hidden" name="id" value="<?= (int)$file['id'] ?>">
                        <button class="link-button" type="submit">Удалить</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</td>
<td><?= e($lead['status'] ?? '') ?></td>
</tr>
<?php endforeach; ?></tbody></table></div>
