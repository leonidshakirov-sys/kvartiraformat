<section class="admin-panel">
    <h1>Калькулятор и тарифы</h1>
    <p>Публичная цена появляется только после галочки «Утвердить цену». Иначе на сайте — «Индивидуальный расчёт».</p>
    <div class="table-wrap">
        <table>
            <tr><th>ID</th><th>Направление</th><th>Slug</th><th>Вид</th><th>Пакет</th><th>Цена/м²</th><th>Утверждён</th><th>Активен</th></tr>
            <?php foreach (array_merge($tariffs ?? [], $houseTariffs ?? [], $commercialTariffs ?? []) as $t): ?>
                <tr>
                    <td><?= (int)$t['id'] ?></td>
                    <td><?= e((string)($t['category'] ?? '')) ?></td>
                    <td><?= e((string)($t['service_slug'] ?? '')) ?></td>
                    <td><?= e($t['repair_type']) ?></td>
                    <td><?= e($t['package_type']) ?></td>
                    <td><?= e((string)($t['price_per_m2'] ?? '')) ?></td>
                    <td><?= !empty($t['is_approved']) ? 'Да' : 'Нет' ?></td>
                    <td><?= !empty($t['is_active']) ? 'Да' : 'Нет' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <h2>Добавить или обновить тариф</h2>
    <form method="post" class="admin-form grid">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_tariff">
        <label>ID<input name="id" type="number"></label>
        <label>Вид ремонта<input name="repair_type" placeholder="Капитальный" required></label>
        <label>Пакет<input name="package_type" placeholder="Только работы" required></label>
        <label>Цена за м²<input name="price_per_m2" type="number" placeholder="Пусто = индивидуальный расчёт"></label>
        <label>Slug услуги<input name="service_slug" placeholder="kapitalnyy"></label>
        <label>Примечание<input name="note" value="Стоимость работ без материалов"></label>
        <label>Направление
            <select name="category">
                <option value="apartment">Квартиры</option>
                <option value="house">Дома</option>
                <option value="commercial">Коммерция</option>
            </select>
        </label>
        <label>Сортировка<input name="sort_order" type="number" value="100"></label>
        <label><input type="checkbox" name="is_active" value="1" checked> Активен</label>
        <label><input type="checkbox" name="is_approved" value="1"> Утвердить цену для публикации</label>
        <button class="btn btn-primary">Сохранить</button>
    </form>
</section>
