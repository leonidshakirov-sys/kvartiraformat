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
<td><?= e(trim(($lead['property_type'] ?? '') . ' ' . ($lead['repair_type'] ?? $lead['premise_condition'] ?? ''))) ?></td>
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
