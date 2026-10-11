<?php
use App\Support\SiteImages;
$heroImage = SiteImages::geo((string)($geo['slug'] ?? ''));
?>
<section class="page-hero has-photo">
    <div class="page-hero-media"><?= picture($heroImage, '', 'eager') ?></div>
    <div class="container page-hero-content">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <a href="/remont-kvartir/">Ремонт квартир</a>
            <span><?= e($geo['name']) ?></span>
        </nav>
        <h1><?= e($h1) ?></h1>
        <p>Комплексный ремонт квартир, апартаментов и вторичного жилья в локации <?= e($geo['name']) ?>. Рассчитаем стоимость и подготовим смету после замера.</p>
        <a class="btn btn-primary" href="#calculator">Рассчитать стоимость</a>
    </div>
</section>
<?php $calcLeadType = 'apartment'; $lockDirection = true; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section">
    <div class="container article">
        <h2>Ремонт квартир в <?= e($geo['name']) ?></h2>
        <?= !empty($geo['body']) ? $geo['body'] : '<p>Работаем с новостройками и вторичным жильём: от косметического обновления до капитального ремонта под ключ. Состав работ и стоимость зависят от состояния квартиры и согласуются после осмотра.</p>' ?>
        <h2>Виды ремонта</h2>
        <div class="cards compact">
            <?php foreach ($services as $service): ?>
                <a class="mini-card" href="/uslugi/<?= e($service['slug']) ?>/"><?= e($service['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Вопросы по ремонту в <?= e($geo['name']) ?></h2>
        <div class="faq">
            <?php foreach (array_slice($faqs, 0, 6) as $faq): ?>
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
            <h2>Рассчитать ремонт в <?= e($geo['name']) ?></h2>
            <p>Укажите площадь, тип жилья и пожелания.</p>
        </div>
        <?php require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
