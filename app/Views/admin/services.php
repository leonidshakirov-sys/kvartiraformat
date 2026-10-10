<?php use App\Support\SiteImages; ?>
<section class="admin-panel">
    <h1>Услуги</h1>
    <div class="cards compact">
        <?php foreach ($services as $service): $preview = SiteImages::serviceCard($service); ?>
            <article class="mini-card">
                <img class="service-preview" src="<?= e($preview['src']) ?>" alt="<?= e($preview['alt']) ?>">
                <strong><?= e($service['title']) ?></strong>
                <small>/uslugi/<?= e($service['slug']) ?>/</small>
                <span><?= format_price(isset($service['price']) ? (int)$service['price'] : null) ?></span>
            </article>
        <?php endforeach; ?>
    </div>
    <h2>Добавить/обновить услугу</h2>
    <form method="post" enctype="multipart/form-data" class="admin-form grid wide">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_service">
        <label>ID<input name="id" type="number"></label>
        <label>Название<input name="title" required></label>
        <label>Slug<input name="slug" required></label>
        <label>Кратко<input name="short"></label>
        <label>Цена за м²<input name="price" type="number"></label>
        <label>Изображение path<input name="image_path" placeholder="/assets/images/services/turnkey.webp"></label>
        <label>Загрузить фото<input type="file" name="image" accept="image/*"></label>
        <label>ALT<input name="image_alt"></label>
        <label>Title<input name="meta_title"></label>
        <label>Description<input name="meta_description"></label>
        <label>H1<input name="h1"></label>
        <label>Сортировка<input name="sort_order" type="number" value="100"></label>
        <label><input type="checkbox" name="is_published" value="1" checked> Опубликовать</label>
        <label class="full">Описание HTML<textarea name="body" rows="8"></textarea></label>
        <button class="btn btn-primary">Сохранить</button>
    </form>
</section>
