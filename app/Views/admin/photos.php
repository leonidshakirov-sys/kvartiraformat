<?php use App\Support\SiteImages; ?>
<section class="admin-panel">
    <h1>Фотографии</h1>
    <p>Загрузите своё изображение, затем вставьте путь в карточку услуги или объекта портфолио. PHP в папке uploads запрещён.</p>
    <form method="post" enctype="multipart/form-data" class="admin-form grid">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="upload_media">
        <label>Файл<input type="file" name="file" accept="image/*" required></label>
        <label>ALT<input name="alt_text"></label>
        <button class="btn btn-primary">Загрузить</button>
    </form>
    <?php if (!empty($media)): ?>
        <h2>Загруженные файлы</h2>
        <div class="admin-thumbs">
            <?php foreach ($media as $file): ?>
                <article>
                    <img src="<?= e(upload_url($file['file_path'])) ?>" alt="<?= e($file['alt_text'] ?? '') ?>">
                    <code><?= e($file['file_path']) ?></code>
                    <small><?= e($file['alt_text'] ?? '') ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <h2>Стандартная библиотека сайта</h2>
    <p>Эти фото уже стоят на публичных страницах. Чтобы заменить услугу, укажите путь в поле «Изображение» или загрузите свой файл.</p>
    <div class="admin-thumbs">
        <?php foreach (SiteImages::catalog() as $image): ?>
            <article>
                <img src="<?= e($image['src']) ?>" alt="<?= e($image['alt']) ?>">
                <code><?= e($image['src']) ?></code>
                <small><?= e($image['alt']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>
