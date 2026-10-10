<?php use App\Support\SiteImages; ?>
<section class="admin-panel">
    <h1>Портфолио</h1>
    <p>Иллюстративные карточки отмечены. Когда появятся реальные объекты, добавьте их здесь и снимите галочку «Демонстрационный объект».</p>
    <div class="portfolio-grid">
        <?php foreach ($portfolio as $index => $item): $preview = SiteImages::portfolio($item, $index); ?>
            <article class="portfolio-card">
                <img class="service-preview" src="<?= e($preview['src']) ?>" alt="<?= e($preview['alt']) ?>">
                <h3><?= e($item['title']) ?></h3>
                <p><?= e($item['city'] ?? '') ?> <?= e($item['district'] ?? '') ?></p>
                <?php if (!empty($item['is_demo'])): ?><span class="badge">Иллюстрация</span><?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <h2>Добавить объект</h2>
    <form method="post" enctype="multipart/form-data" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_portfolio">
        <label>Название<input name="title" required></label>
        <label>Город<input name="city"></label>
        <label>Район/ЖК<input name="district"></label>
        <label>Площадь<input name="area"></label>
        <label>Вид ремонта<input name="repair_type"></label>
        <label>Стоимость<input name="cost"></label>
        <label>Срок<input name="duration"></label>
        <label>Сортировка<input name="sort_order" type="number" value="100"></label>
        <label>Фото объекта<input type="file" name="image" accept="image/*"></label>
        <label>ALT фото<input name="image_alt"></label>
        <label><input type="checkbox" name="is_demo" value="1"> Демонстрационный объект</label>
        <label><input type="checkbox" name="is_published" value="1" checked> Опубликовать</label>
        <label class="full">Перечень работ<textarea name="works" rows="5"></textarea></label>
        <button class="btn btn-primary">Сохранить</button>
    </form>
</section>
