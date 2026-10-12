<?php use App\Support\HouseCatalog; $s = $settings ?? []; ?>
<section class="admin-panel">
    <h1>Ремонт домов</h1>
    <p>Раздел использует ту же авторизацию и базу, что и остальной сайт. Публичный URL: <a href="/remont-domov/" target="_blank">/remont-domov/</a>. Цены домов не копируются с квартирных тарифов.</p>
</section>
<section class="admin-panel">
    <h2>SEO и тексты общей страницы</h2>
    <form method="post" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_house_texts">
        <label>Title<input name="house_meta_title" value="<?= e($s['house_meta_title'] ?? '') ?>"></label>
        <label>H1<input name="house_h1" value="<?= e($s['house_h1'] ?? '') ?>"></label>
        <label class="full">Description<input name="house_meta_description" value="<?= e($s['house_meta_description'] ?? '') ?>"></label>
        <label class="full">Подзаголовок / описание<textarea name="house_intro" rows="4"><?= e($s['house_intro'] ?? '') ?></textarea></label>
        <label><input type="checkbox" name="house_hub_published" value="1" <?= ($s['house_hub_published'] ?? '1') !== '0' ? 'checked' : '' ?>> Опубликовать общую страницу</label>
        <button class="btn btn-primary">Сохранить тексты и SEO</button>
    </form>
</section>
<section class="admin-panel">
    <h2>Услуги и фотографии</h2>
    <div class="cards compact">
        <?php foreach ($houseServices ?? [] as $service): ?>
            <article class="mini-card">
                <img class="service-preview" src="<?= e($service['image_path'] ?? '') ?>" alt="<?= e($service['image_alt'] ?? '') ?>">
                <strong><?= e($service['title']) ?></strong>
                <small><?= e($service['url'] ?? \App\Support\HouseCatalog::path((string)$service['slug'])) ?> · <?= !empty($service['is_published']) ? 'опубликовано' : 'черновик' ?></small>
                <span>ID <?= (int)($service['id'] ?? 0) ?> · <?= e(service_price($service)['label']) ?></span>
            </article>
        <?php endforeach; ?>
    </div>
    <h3>Добавить или обновить услугу</h3>
    <form method="post" enctype="multipart/form-data" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_service">
        <input type="hidden" name="category" value="house">
        <label>ID<input name="id" type="number"></label>
        <label>Название<input name="title" required></label>
        <label>Slug<input name="slug" required placeholder="pod-klyuch"></label>
        <label>Кратко<input name="short" maxlength="200"></label>
        <label>Цена за м²<input name="price" type="number" placeholder="Пусто = индивидуальный расчёт"></label>
        <label>Путь к фото<input name="image_path" placeholder="/assets/images/houses/turnkey.webp"></label>
        <label>Загрузить фото<input type="file" name="image" accept="image/*"></label>
        <label>ALT<input name="image_alt"></label>
        <label>Title<input name="meta_title"></label>
        <label>Description<input name="meta_description"></label>
        <label>H1<input name="h1"></label>
        <label>Сортировка<input name="sort_order" type="number" value="100"></label>
        <label><input type="checkbox" name="is_published" value="1" checked> Опубликовать страницу</label>
        <label class="full">Описание HTML<textarea name="body" rows="8"></textarea></label>
        <button class="btn btn-primary">Сохранить услугу</button>
    </form>
</section>
<section class="admin-panel">
    <h2>Библиотека фото домов</h2>
    <div class="admin-thumbs">
        <?php foreach (HouseCatalog::images() as $image): ?>
            <article>
                <img src="<?= e($image['src']) ?>" alt="<?= e($image['alt']) ?>">
                <code><?= e($image['src']) ?></code>
                <small><?= e($image['alt']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<section class="admin-panel">
    <h2>Цены домов</h2>
    <p>Цены домов опубликованы отдельно от квартирных тарифов. Чтобы скрыть сумму с сайта, снимите галочку утверждения и сохраните.</p>
    <div class="table-wrap">
        <table>
            <tr><th>ID</th><th>Slug</th><th>Вид</th><th>Сумма</th><th>Ед.</th><th>На сайте</th><th>Изменён</th></tr>
            <?php foreach ($houseTariffs ?? [] as $t): ?>
                <tr>
                    <td><?= (int)($t['id'] ?? 0) ?></td>
                    <td><?= e((string)($t['service_slug'] ?? $t['slug'] ?? '')) ?></td>
                    <td><?= e($t['repair_type'] ?? '') ?></td>
                    <td><?= e((string)($t['price_per_m2'] ?? '')) ?></td>
                    <td><?= e((string)($t['price_unit'] ?? $t['unit'] ?? 'm2')) ?></td>
                    <td><?= !empty($t['is_approved']) && !empty($t['is_active']) ? 'Да' : 'Нет' ?></td>
                    <td><?= e((string)($t['updated_at'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <form method="post" class="admin-form grid">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_tariff">
        <input type="hidden" name="category" value="house">
        <label>ID<input name="id" type="number"></label>
        <label>Вид ремонта<input name="repair_type" required></label>
        <label>Пакет<input name="package_type" required></label>
        <label>Сумма<input name="price_per_m2" type="number"></label>
        <label>Единица
            <select name="price_unit">
                <option value="m2">₽/м²</option>
                <option value="room">₽ за помещение</option>
            </select>
        </label>
        <label>Slug услуги<input name="service_slug" placeholder="kapitalnyy"></label>
        <label>Примечание<input name="note" value="Стоимость работ без материалов"></label>
        <label>Сортировка<input name="sort_order" type="number" value="100"></label>
        <label><input type="checkbox" name="is_active" value="1" checked> Активен</label>
        <label><input type="checkbox" name="is_approved" value="1"> Утвердить цену для публикации</label>
        <button class="btn btn-primary">Сохранить тариф</button>
    </form>
</section>
<section class="admin-panel">
    <h2>FAQ раздела домов</h2>
    <?php foreach ($houseFaqs ?? [] as $faq): ?>
        <p><strong><?= e($faq['q']) ?></strong><br><?= e($faq['a']) ?></p>
    <?php endforeach; ?>
    <form method="post" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_faq">
        <input type="hidden" name="category" value="house">
        <label>Вопрос<input name="question" required></label>
        <label>Сортировка<input name="sort_order" type="number" value="100"></label>
        <label><input type="checkbox" name="is_published" value="1" checked> Опубликовать</label>
        <label class="full">Ответ<textarea name="answer" rows="4" required></textarea></label>
        <button class="btn btn-primary">Добавить FAQ</button>
    </form>
</section>
<section class="admin-panel">
    <h2>Заявки на ремонт домов</h2>
    <p><a href="/admin/leads?type=house">Открыть фильтр заявок</a></p>
</section>
