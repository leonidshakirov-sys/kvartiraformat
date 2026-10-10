<?php
$heroImage = $heroImage ?? ['src' => $service['image_path'], 'alt' => $service['image_alt'] ?? $service['title'], 'width' => 1600, 'height' => 900];
$processImage = $processImage ?? $heroImage;
?>
<section class="page-hero has-photo">
    <div class="page-hero-media"><?= picture($heroImage, '', 'eager') ?></div>
    <div class="container page-hero-content">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <a href="/remont-kommercheskih-pomescheniy/">Коммерческий ремонт</a>
            <span><?= e($service['title']) ?></span>
        </nav>
        <h1><?= e($h1) ?></h1>
        <p><?= e($service['intro'] ?? $service['short'] ?? '') ?></p>
        <p><strong><?= e(format_price(isset($service['price']) ? (int)$service['price'] : null)) ?></strong></p>
        <a class="btn btn-primary" href="#calculator">Получить смету на ремонт помещения</a>
    </div>
</section>
<section class="section">
    <div class="container article">
        <div class="media-frame article-photo"><?= picture(['src' => $service['image_path'], 'alt' => $service['image_alt'] ?? $service['title'], 'width' => 1600, 'height' => 1068]) ?></div>
        <h2>Особенности ремонта</h2>
        <?= $service['body'] ?? '' ?>
        <?php if (!empty($service['features'])): ?>
            <h2>Что учитываем</h2>
            <ul class="check-list">
                <?php foreach ($service['features'] as $feature): ?><li><?= e($feature) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if (!empty($service['works'])): ?>
            <h2>Перечень работ</h2>
            <ul class="check-list">
                <?php foreach ($service['works'] as $work): ?><li><?= e($work) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p class="muted">Фотография носит иллюстративный характер и не является объектом, выполненным компанией КвартираФормат.</p>
    </div>
</section>
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Этапы</span>
            <h2>Как выполняем работы</h2>
        </div>
        <ol class="steps commercial-steps-list">
            <?php foreach ($stages as $stage): ?><li><?= e($stage) ?></li><?php endforeach; ?>
        </ol>
    </div>
</section>
<?php $calcLeadType = 'commercial'; $lockDirection = true; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">FAQ</span>
            <h2>Вопросы по коммерческому ремонту</h2>
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
            <h2>Заявка на расчёт</h2>
            <p>Опишите помещение и задачу. Подготовим смету после уточнения деталей, без фиктивной цены.</p>
        </div>
        <?php $leadType = 'commercial'; require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Другие коммерческие направления</h2>
        <div class="geo-list">
            <a href="/remont-kommercheskih-pomescheniy/">Все коммерческие помещения</a>
            <?php foreach ($commercialServices as $item): ?>
                <?php if (($item['slug'] ?? '') !== ($service['slug'] ?? '')): ?>
                    <a href="/<?= e($item['slug']) ?>/"><?= e($item['title']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <h2>Ремонт квартир</h2>
        <div class="geo-list">
            <?php foreach ($apartmentServices as $item): ?>
                <a href="/uslugi/<?= e($item['slug']) ?>/"><?= e($item['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
