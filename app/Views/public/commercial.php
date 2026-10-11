<?php
use App\Support\SiteImages;
$heroImage = $heroImage ?? SiteImages::hero();
$processImage = $processImage ?? $heroImage;
?>
<section class="page-hero has-photo">
    <div class="page-hero-media"><?= picture($heroImage, '', 'eager') ?></div>
    <div class="container page-hero-content">
        <nav class="breadcrumbs">
            <a href="/">Главная</a>
            <span>Коммерческий ремонт</span>
        </nav>
        <h1><?= e($h1) ?></h1>
        <p><?= e($intro ?? '') ?></p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="#calculator">Получить смету на ремонт помещения</a>
            <a class="btn btn-outline" href="#commercial-types">Виды помещений</a>
        </div>
    </div>
</section>
<section class="section section-muted" id="commercial-types">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Направления</span>
            <h2>Основные виды коммерческих помещений</h2>
            <p>Офисы, магазины, салоны, кафе, склады и помещения свободного назначения.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($commercialServices as $item): $card = $item; $cardImage = ['src' => $item['image_path'], 'alt' => $item['image_alt'] ?? $item['title'], 'width' => 1600, 'height' => 1000]; $cardHref = '/' . $item['slug'] . '/'; $cardCalcHref = '/' . $item['slug'] . '/#calculator'; require __DIR__ . '/../partials/service-card.php'; endforeach; ?>
        </div>
    </div>
</section>
<section class="section section-muted">
    <div class="container split-media">
        <div>
            <span class="eyebrow">Состав работ</span>
            <h2>Перечень выполняемых работ</h2>
            <ul class="check-list">
                <?php foreach ($works as $work): ?><li><?= e($work) ?></li><?php endforeach; ?>
            </ul>
        </div>
        <div class="media-frame article-photo"><?= picture($processImage) ?></div>
    </div>
</section>
<section class="section">
    <div class="container article">
        <h2>Особенности ремонта коммерческих объектов</h2>
        <p>Коммерческий ремонт отличается от квартирного тем, что помещение должно начать работать в конкретную дату: открытие магазина, въезд сотрудников, запуск кухни. На сайте указаны стартовые цены за м², а точная смета собирается по площади, состоянию помещения и техническому заданию.</p>
        <p>Для арендатора важны правила управляющей компании, возможность шумных работ и согласование перегородок. Для собственника — сохранение конструкции и понятный объём, который можно передать следующему арендатору. На сайте указаны стартовые цены, итог подтверждаем после осмотра.</p>
        <p>Отделка офиса, торгового зала, салона или склада требует разного набора работ. Общая страница помогает выбрать направление, а точный перечень появляется после осмотра объекта в Москве или Московской области.</p>
    </div>
</section>
<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Почему с нами</span>
            <h2>Преимущества сотрудничества</h2>
        </div>
        <div class="feature-grid commercial-features">
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
        <ol class="steps commercial-steps-list">
            <?php foreach ($stages as $stage): ?><li><?= e($stage) ?></li><?php endforeach; ?>
        </ol>
    </div>
</section>
<?php $calcLeadType = 'commercial'; $lockDirection = true; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Примеры</span>
            <h2>Примеры интерьеров и вариантов отделки</h2>
            <p>Примеры помещений разного назначения. Когда появятся съёмки выполненных объектов, опубликуем раздел «Наши работы».</p>
        </div>
        <?php if (!empty($portfolio)): ?>
            <div class="portfolio-grid">
                <?php foreach ($portfolio as $index => $item): $headingTag = 'h3'; require __DIR__ . '/../partials/portfolio-card.php'; endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cards service-cards">
                <?php foreach (array_slice($commercialServices, 0, 3) as $item): $cardImage = ['src' => $item['image_path'], 'alt' => $item['image_alt'] ?? $item['title'], 'width' => 1600, 'height' => 1068]; ?>
                    <article class="card">
                        <div class="media-frame card-image"><?= picture($cardImage) ?></div>
                        <h3><?= e($item['title']) ?></h3>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<section class="section" id="geo">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">География</span>
            <h2>Москва и Московская область</h2>
            <p>Продвижение начинаем с общей коммерческой страницы. Отдельные страницы по городам появятся только с уникальным локальным содержанием.</p>
        </div>
        <div class="geo-list">
            <?php foreach ($cities as $city): ?><span><?= e($city) ?></span><?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section section-muted">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">FAQ</span>
            <h2>Частые вопросы</h2>
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
            <h2>Оставьте заявку на смету коммерческого ремонта</h2>
            <p>Опишите помещение — подготовим расчёт после уточнения площади, состояния и технического задания.</p>
        </div>
        <?php $formId = 'lead-form'; $leadType = 'commercial'; require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Другие услуги</h2>
        <div class="geo-list">
            <a href="/remont-kvartir/">Ремонт квартир</a>
            <a href="/remont-domov/">Ремонт домов</a>
            <?php foreach ($apartmentServices as $item): ?>
                <a href="/uslugi/<?= e($item['slug']) ?>/"><?= e($item['title']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
