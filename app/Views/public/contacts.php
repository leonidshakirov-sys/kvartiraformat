<?php
use App\Support\SiteImages;
$photo = SiteImages::contacts();
?>
<section class="page-hero has-photo">
    <div class="page-hero-media"><?= picture($photo, '', 'eager') ?></div>
    <div class="container page-hero-content">
        <nav class="breadcrumbs"><a href="/">Главная</a><span>Контакты</span></nav>
        <h1><?= e($h1) ?></h1>
        <p>КВАРТИРАФОРМАТ — ремонт квартир, домов и коммерческих помещений в Москве и Московской области.</p>
    </div>
</section>
<section class="section">
    <div class="container contact-grid">
        <div class="contact-card">
            <h2>Реквизиты</h2>
            <p><strong>КВАРТИРАФОРМАТ</strong><br><?= e($settings['brand_tagline'] ?? '') ?></p>
            <p><?= e($settings['legal_name'] ?? '') ?><br>ИНН <?= e($settings['inn'] ?? '') ?></p>
            <p><a class="big-phone" href="<?= e($settings['phone_href'] ?? '') ?>"><?= e($settings['phone'] ?? '') ?></a></p>
            <p>Адрес: <?= e($settings['office_address'] ?? '') ?></p>
            <p>Приём звонков: <?= e(working_hours($settings)) ?><br><small><?= e(working_hours_note($settings)) ?></small></p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e($settings['phone_href'] ?? '') ?>">Позвонить</a>
                <a class="btn btn-outline" href="<?= e($settings['route_url'] ?? '#') ?>" target="_blank" rel="nofollow noopener">Построить маршрут</a>
            </div>
        </div>
        <div>
            <?php if (!empty($settings['map_embed_url'])): ?>
                <iframe class="map" src="<?= e($settings['map_embed_url']) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php else: ?>
                <div class="map-placeholder">Карта появится после подтверждения точного расположения офиса. Маршрут можно построить по кнопке слева.</div>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="section cta">
    <div class="container cta-grid">
        <div>
            <h2>Оставить заявку</h2>
            <p>Заполните форму, и мы подготовим индивидуальный расчёт.</p>
        </div>
        <?php require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
