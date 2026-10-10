<div class="table-wrap"><table><thead><tr><th>ID</th><th>Тип</th><th>Дата</th><th>Имя</th><th>Телефон</th><th>Город</th><th>Объект / ремонт</th><th>Оценка</th><th>Файл</th><th>Статус</th></tr></thead><tbody><?php foreach(array_slice($leads,0,40) as $lead): ?><tr>
<td><?= e($lead['public_id'] ?? (string)$lead['id']) ?></td>
<td><?= e(($lead['lead_type'] ?? 'apartment') === 'commercial' ? 'Коммерция' : 'Квартира') ?></td>
<td><?= e($lead['created_at'] ?? '') ?></td>
<td><?= e($lead['name'] ?? '') ?></td>
<td><?= e($lead['phone'] ?? '') ?></td>
<td><?= e($lead['city'] ?? '') ?></td>
<td><?= e(trim(($lead['property_type'] ?? '') . ' ' . ($lead['repair_type'] ?? $lead['premise_condition'] ?? ''))) ?></td>
<td><?= e($lead['estimate_text'] ?? '') ?></td>
<td><?php foreach (($lead['files'] ?? []) as $file): ?><a href="/admin/leads/file?id=<?= (int)$file['id'] ?>">скачать</a><?php endforeach; ?></td>
<td><?= e($lead['status'] ?? '') ?></td>
</tr><?php endforeach; ?></tbody></table></div>
