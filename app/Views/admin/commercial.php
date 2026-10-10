<?php use App\Support\CommercialCatalog; $s = $settings ?? []; ?>
<section class="admin-panel">
    <h1>Коммерческий ремонт</h1>
    <p>Раздел использует ту же авторизацию и базу, что и остальной сайт. Публичные URL: <a href="/remont-kommercheskih-pomescheniy/" target="_blank">/remont-kommercheskih-pomescheniy/</a></p>
</section>
<section class="admin-panel">
    <h2>SEO и тексты общей страницы</h2>
    <form method="post" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_commercial_texts">
        <label>Title<input name="commercial_meta_title" value="<?= e($s['commercial_meta_title'] ?? '') ?>"></label>
        <label>H1<input name="commercial_h1" value="<?= e($s['commercial_h1'] ?? '') ?>"></label>
        <label class="full">Description<input name="commercial_meta_description" value="<?= e($s['commercial_meta_description'] ?? '') ?>"></label>
        <label class="full">Подзаголовок / описание<textarea name="commercial_intro" rows="4"><?= e($s['commercial_intro'] ?? '') ?></textarea></label>
        <label><input type="checkbox" name="commercial_hub_published" value="1" <?= ($s['commercial_hub_published'] ?? '1') !== '0' ? 'checked' : '' ?>> Опубликовать общую страницу</label>
        <button class="btn btn-primary">Сохранить тексты и SEO</button>
    </form>
</section>
<section class="admin-panel">
    <h2>Услуги и фотографии</h2>
    <div class="cards compact">
        <?php foreach ($commercialServices as $service): ?>
            <article class="mini-card">
                <img class="service-preview" src="<?= e($service['image_path'] ?? '') ?>" alt="<?= e($service['image_alt'] ?? '') ?>">
                <strong><?= e($service['title']) ?></strong>
                <small>/<?= e($service['slug']) ?>/ · <?= !empty($service['is_published']) ? 'опубликовано' : 'черновик' ?></small>
                <span>ID <?= (int)($service['id'] ?? 0) ?></span>
            </article>
        <?php endforeach; ?>
    </div>
    <h3>Добавить или обновить услугу</h3>
    <form method="post" enctype="multipart/form-data" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_service">
        <input type="hidden" name="category" value="commercial">
        <label>ID<input name="id" type="number"></label>
        <label>Название<input name="title" required></label>
        <label>Slug<input name="slug" required placeholder="remont-ofisov"></label>
        <label>Кратко<input name="short" maxlength="200"></label>
        <label>Путь к фото<input name="image_path" placeholder="/assets/images/commercial/office.webp"></label>
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
    <h2>Библиотека коммерческих фото</h2>
    <div class="admin-thumbs">
        <?php foreach (CommercialCatalog::images() as $image): ?>
            <article>
                <img src="<?= e($image['src']) ?>" alt="<?= e($image['alt']) ?>">
                <code><?= e($image['src']) ?></code>
                <small><?= e($image['alt']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<section class="admin-panel">
    <h2>Цены и калькулятор</h2>
    <p>Если коммерческие тарифы не заданы, на сайте показывается индивидуальный расчёт без фиктивной суммы.</p>
    <div class="table-wrap">
        <table>
            <tr><th>ID</th><th>Вид</th><th>Пакет</th><th>Цена/м²</th><th>Активен</th></tr>
            <?php foreach ($commercialTariffs as $t): ?>
                <tr><td><?= (int)$t['id'] ?></td><td><?= e($t['repair_type']) ?></td><td><?= e($t['package_type']) ?></td><td><?= e((string)$t['price_per_m2']) ?></td><td><?= !empty($t['is_active']) ? 'Да' : 'Нет' ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
    <form method="post" class="admin-form grid">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_tariff">
        <input type="hidden" name="category" value="commercial">
        <label>ID<input name="id" type="number"></label>
        <label>Вид ремонта<input name="repair_type" placeholder="Капитальный" required></label>
        <label>Пакет<input name="package_type" placeholder="Комплексный ремонт" required></label>
        <label>Цена за м²<input name="price_per_m2" type="number" required></label>
        <label>Примечание<input name="note"></label>
        <label>Сортировка<input name="sort_order" type="number" value="100"></label>
        <label><input type="checkbox" name="is_active" value="1" checked> Активен</label>
        <button class="btn btn-primary">Сохранить тариф</button>
    </form>
</section>
<section class="admin-panel">
    <h2>FAQ коммерческого раздела</h2>
    <?php foreach ($commercialFaqs as $faq): ?>
        <p><strong><?= e($faq['q']) ?></strong><br><?= e($faq['a']) ?></p>
    <?php endforeach; ?>
    <form method="post" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_faq">
        <input type="hidden" name="category" value="commercial">
        <label>Вопрос<input name="question" required></label>
        <label>Сортировка<input name="sort_order" type="number" value="100"></label>
        <label><input type="checkbox" name="is_published" value="1" checked> Опубликовать</label>
        <label class="full">Ответ<textarea name="answer" rows="4" required></textarea></label>
        <button class="btn btn-primary">Добавить FAQ</button>
    </form>
</section>
<section class="admin-panel">
    <h2>Портфолио коммерческих объектов</h2>
    <p>Публикуйте только реальные объекты. Иллюстрации помечайте как демо.</p>
    <?php if (!empty($commercialPortfolio)): ?>
        <div class="cards compact">
            <?php foreach ($commercialPortfolio as $item): ?>
                <article class="mini-card"><strong><?= e($item['title']) ?></strong><small><?= e($item['city'] ?? '') ?></small></article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_portfolio">
        <input type="hidden" name="category" value="commercial">
        <label>Название<input name="title" required></label>
        <label>Город<input name="city"></label>
        <label>Тип объекта<input name="repair_type"></label>
        <label>Площадь<input name="area"></label>
        <label>Фото<input type="file" name="image" accept="image/*"></label>
        <label>ALT<input name="image_alt"></label>
        <label><input type="checkbox" name="is_demo" value="1"> Иллюстрация, не наш объект</label>
        <label><input type="checkbox" name="is_published" value="1" checked> Опубликовать</label>
        <label class="full">Описание<textarea name="works" rows="3"></textarea></label>
        <button class="btn btn-primary">Добавить объект</button>
    </form>
</section>
<section class="admin-panel">
    <h2>Заявки на коммерческий ремонт</h2>
    <p><a href="/admin/leads?type=commercial">Открыть фильтр заявок</a></p>
    <?php $leads = array_values(array_filter($leads ?? [], static fn($lead) => ($lead['lead_type'] ?? '') === 'commercial')); require __DIR__ . '/leads-table.php'; ?>
</section>
