<?php use App\Support\SiteImages; ?>
<section class="page-hero has-photo">
    <div class="page-hero-media"><?= picture(SiteImages::hero(), '', 'eager') ?></div>
    <div class="container page-hero-content">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <span>Ремонт квартир</span>
        </nav>
        <h1><?= e($h1) ?></h1>
        <p><?= e($intro ?? '') ?></p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="#calculator">Рассчитать стоимость</a>
            <a class="btn btn-outline" href="#apartment-types">Виды ремонта</a>
        </div>
    </div>
</section>
<section class="section section-muted" id="apartment-types">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Услуги</span>
            <h2>Категории ремонта квартир</h2>
            <p>Косметический, капитальный и ремонт под ключ, а также отдельные страницы по типу жилья и санузлам.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($services as $service): $card = $service; $cardImage = SiteImages::serviceCard($service); $cardHref = '/uslugi/' . $service['slug'] . '/'; $cardCalcHref = '/uslugi/' . $service['slug'] . '/#calculator'; require __DIR__ . '/../partials/service-card.php'; endforeach; ?>
        </div>
    </div>
</section>
<?php $calcLeadType = 'apartment'; $lockDirection = true; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Другие направления</span>
            <h2>Дома и коммерческие помещения</h2>
        </div>
        <div class="geo-list">
            <a href="/remont-domov/">Ремонт домов</a>
            <a href="/remont-kommercheskih-pomescheniy/">Коммерческий ремонт</a>
            <?php foreach ($houseServices ?? [] as $item): ?><a href="<?= e($item['url'] ?? \App\Support\HouseCatalog::path((string)$item['slug'])) ?>"><?= e($item['title']) ?></a><?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">FAQ</span>
            <h2>Вопросы по ремонту квартир</h2>
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
            <h2>Заявка на ремонт квартиры</h2>
            <p>Приложите план, фотографии или пожелания — подготовим расчёт после уточнения деталей.</p>
        </div>
        <?php $leadType = 'apartment'; require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
