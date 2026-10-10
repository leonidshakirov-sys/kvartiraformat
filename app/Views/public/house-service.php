<?php
$heroImage = $heroImage ?? ['src' => $service['image_path'], 'alt' => $service['image_alt'] ?? $service['title'], 'width' => 1600, 'height' => 900];
?>
<section class="page-hero has-photo">
    <div class="page-hero-media"><?= picture($heroImage, '', 'eager') ?></div>
    <div class="container page-hero-content">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <a href="/remont-domov/">Ремонт домов</a>
            <span><?= e($service['title']) ?></span>
        </nav>
        <h1><?= e($h1) ?></h1>
        <p><?= e($service['intro'] ?? $service['short'] ?? '') ?></p>
        <p><strong><?= e(format_price(null)) ?></strong></p>
        <a class="btn btn-primary" href="#calculator">Рассчитать стоимость</a>
    </div>
</section>
<section class="section">
    <div class="container article">
        <div class="media-frame article-photo"><?= picture(['src' => $service['image_path'], 'alt' => $service['image_alt'] ?? $service['title'], 'width' => 1600, 'height' => 1068]) ?></div>
        <h2>Состав работ</h2>
        <?= $service['body'] ?? '' ?>
        <p class="muted">Фотография носит иллюстративный характер и не является объектом, выполненным компанией КвартираФормат.</p>
    </div>
</section>
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Этапы</span>
            <h2>Как выполняем работы</h2>
        </div>
        <ol class="steps">
            <?php foreach ($stages as $stage): ?><li><?= e($stage) ?></li><?php endforeach; ?>
        </ol>
    </div>
</section>
<?php $calcLeadType = 'house'; $lockDirection = true; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">FAQ</span>
            <h2>Вопросы по ремонту дома</h2>
        </div>
        <div class="faq">
            <?php foreach ($faqs as $faq): ?>
                <details>
                    <summary><?= e($faq['q']) ?></summary>
                    <p><?= e($faq['a']) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section cta">
    <div class="container cta-grid">
        <div>
            <h2>Заявка на эту услугу</h2>
            <p>Можно приложить план, фотографии или техническое задание.</p>
        </div>
        <?php $leadType = 'house'; require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Другие услуги по домам</h2>
        <div class="geo-list">
            <?php foreach ($houseServices as $item): ?>
                <a href="/<?= e($item['slug']) ?>/"><?= e($item['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
