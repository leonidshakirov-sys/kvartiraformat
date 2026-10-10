<?php
use App\Support\HouseCatalog;
$heroImage = $heroImage ?? HouseCatalog::image('hero');
?>
<section class="page-hero has-photo">
    <div class="page-hero-media"><?= picture($heroImage, '', 'eager') ?></div>
    <div class="container page-hero-content">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <span>Ремонт домов</span>
        </nav>
        <h1><?= e($h1) ?></h1>
        <p><?= e($intro ?? '') ?></p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="#calculator">Рассчитать стоимость</a>
            <a class="btn btn-outline" href="#house-types">Категории</a>
        </div>
    </div>
</section>
<section class="section section-muted" id="house-types">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Направления</span>
            <h2>Ремонт и внутренняя отделка домов</h2>
            <p>Фотографии показывают характер работ и интерьеров. Они не выдаются за объекты, выполненные компанией КвартираФормат.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($houseServices as $item): $card = $item; $cardImage = ['src' => $item['image_path'], 'alt' => $item['image_alt'] ?? $item['title'], 'width' => 1600, 'height' => 1000]; $cardHref = '/' . $item['slug'] . '/'; $cardCalcHref = '/' . $item['slug'] . '/#calculator'; require __DIR__ . '/../partials/service-card.php'; endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container article">
        <h2>Что входит в ремонт дома</h2>
        <p>Мы выполняем внутренний ремонт существующих домов, коттеджей и таунхаусов: подготовку поверхностей, отделку помещений и санузлов. Строительство дома с нуля на этих страницах не предлагается.</p>
        <p>Стоимость не считается по квартирному тарифу. После заявки уточняем тип дома, площадь, этажность и состояние отделки, затем готовим смету.</p>
    </div>
</section>
<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Почему с нами</span>
            <h2>Как считаем работы в доме</h2>
        </div>
        <div class="feature-grid">
            <?php foreach ($advantages as $adv): ?>
                <article>
                    <h3><?= e($adv['title']) ?></h3>
                    <p><?= e($adv['text']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Этапы</span>
            <h2>Как строим работу</h2>
        </div>
        <ol class="steps">
            <?php foreach ($stages as $stage): ?><li><?= e($stage) ?></li><?php endforeach; ?>
        </ol>
    </div>
</section>
<?php $calcLeadType = 'house'; $lockDirection = true; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">FAQ</span>
            <h2>Вопросы по ремонту домов</h2>
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
            <span class="eyebrow">Заявка</span>
            <h2>Заявка на ремонт дома</h2>
            <p>Приложите план дома, фотографии помещений или проект — это поможет точнее собрать смету.</p>
        </div>
        <?php $leadType = 'house'; require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Другие направления</h2>
        <div class="geo-list">
            <a href="/remont-kvartir/">Ремонт квартир</a>
            <a href="/remont-kommercheskih-pomescheniy/">Коммерческий ремонт</a>
        </div>
    </div>
</section>
