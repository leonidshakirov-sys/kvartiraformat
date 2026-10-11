<?php
use App\Support\SiteImages;
$heroImage = SiteImages::serviceHero($service);
?>
<section class="page-hero has-photo">
    <div class="page-hero-media"><?= picture($heroImage, '', 'eager') ?></div>
    <div class="container page-hero-content">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <a href="/remont-kvartir/">Ремонт квартир</a>
            <span><?= e($service['title']) ?></span>
        </nav>
        <h1><?= e($h1) ?></h1>
        <p><?= e($service['short'] ?? '') ?></p>
        <?php $price = service_price($service); ?>
        <p><strong class="card-price"><?= e($price['label']) ?></strong><?php if ($price['note'] !== ''): ?> <small class="price-note"><?= e($price['note']) ?></small><?php endif; ?></p>
        <a class="btn btn-primary" href="#calculator">Рассчитать стоимость</a>
    </div>
</section>
<?php $calcLeadType = 'apartment'; $lockDirection = true; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section">
    <div class="container article">
        <div class="media-frame article-photo">
            <?= picture(SiteImages::serviceCard($service)) ?>
        </div>
        <h2>Что входит в услугу</h2>
        <?= !empty($service['body']) ? $service['body'] : '<p>Состав работ уточняется после осмотра квартиры: состояние помещений, инженерия, материалы и пожелания по срокам. Оставьте заявку — подготовим смету без неподтверждённых обещаний.</p>' ?>
        <h2>Другие виды ремонта</h2>
        <div class="geo-list">
            <?php foreach ($services as $item): ?>
                <a href="/uslugi/<?= e($item['slug']) ?>/"><?= e($item['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section cta">
    <div class="container cta-grid">
        <div>
            <h2>Получите расчёт по квартире</h2>
            <p>Опишите параметры квартиры, чтобы подготовить смету без неподтверждённых обещаний.</p>
        </div>
        <?php require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
