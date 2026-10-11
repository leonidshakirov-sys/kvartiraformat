<?php
use App\Support\HouseCatalog;
$heroImage = $heroImage ?? HouseCatalog::image('hero');
$works = $works ?? HouseCatalog::works();
$tariffs = $tariffs ?? [];
$portfolio = $portfolio ?? [];
$geoPages = $geoPages ?? [];
$settings = $settings ?? [];
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
            <a class="btn btn-outline" href="#house-types">Виды ремонта домов</a>
            <a class="btn btn-ghost" href="#lead-form">Заказать консультацию</a>
        </div>
    </div>
</section>
<section class="section section-muted" id="house-types">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Виды ремонта</span>
            <h2>Ремонт и внутренняя отделка домов</h2>
            <p>Отдельные страницы по типу дома и составу работ. Смета собирается после осмотра, а не по квартирному тарифу.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($houseServices as $item):
                $card = $item;
                $cardImage = ['src' => $item['image_path'], 'alt' => $item['image_alt'] ?? $item['title'], 'width' => 1600, 'height' => 1000];
                $cardHref = $item['url'] ?? HouseCatalog::path((string)$item['slug']);
                $cardCalcHref = $cardHref . '#calculator';
                require __DIR__ . '/../partials/service-card.php';
            endforeach; ?>
        </div>
    </div>
</section>
<section class="section" id="prices">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Стоимость</span>
            <h2>Стоимость ремонтных работ</h2>
            <p>На сайте публикуются только утверждённые стартовые цены за м² работ без материалов. Остальные позиции — индивидуальный расчёт после осмотра дома.</p>
        </div>
        <div class="price-table">
            <table>
                <thead>
                    <tr><th>Вид работ</th><th>Цена</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tariffs as $t): $price = tariff_price($t); ?>
                        <tr>
                            <td><?= e((string)($t['repair_type'] ?? '')) ?></td>
                            <td>
                                <strong><?= e($price['label']) ?></strong>
                                <?php if ($price['note'] !== ''): ?><small class="price-note"><?= e($price['note']) ?></small><?php endif; ?>
                            </td>
                            <td><a class="btn btn-small" href="#calculator">Рассчитать стоимость</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<section class="section section-muted">
    <div class="container article">
        <h2>Перечень работ</h2>
        <p>Выполняем внутренний ремонт существующих домов, коттеджей и таунхаусов. Строительство дома с нуля на этих страницах не предлагается.</p>
        <ul class="check-list">
            <?php foreach ($works as $work): ?><li><?= e($work) ?></li><?php endforeach; ?>
        </ul>
    </div>
</section>
<?php $calcLeadType = 'house'; $lockDirection = true; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section">
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
<section class="section section-muted">
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
<section class="section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Примеры</span>
            <h2>Примеры интерьеров и вариантов отделки</h2>
            <p>Подборка помогает понять характер отделки частных домов. Когда появятся съёмки выполненных объектов, опубликуем их отдельным разделом.</p>
        </div>
        <div class="portfolio-grid">
            <?php
            $examples = $portfolio ?: array_slice($houseServices, 0, 6);
            foreach ($examples as $index => $item):
                if (isset($item['title']) && isset($item['image_path']) && empty($item['repair_type'])) {
                    $item = [
                        'title' => $item['title'],
                        'city' => '',
                        'district' => '',
                        'area' => '',
                        'repair_type' => '',
                        'works' => '',
                        'is_demo' => 0,
                        'images' => [['file_path' => $item['image_path'], 'alt_text' => $item['image_alt'] ?? $item['title']]],
                    ];
                }
                $headingTag = 'h3';
                require __DIR__ . '/../partials/portfolio-card.php';
            endforeach;
            ?>
        </div>
    </div>
</section>
<section class="section section-muted" id="geo">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">География</span>
            <h2>Москва и Московская область</h2>
            <p>Выезжаем на дома в Москве и обслуживаемых городах области. Отдельные массовые страницы по населённым пунктам не создаём.</p>
        </div>
        <div class="geo-list">
            <?php foreach ($geoPages as $geo): ?>
                <span><?= e($geo['name']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
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
<section class="section cta" id="lead-form">
    <div class="container cta-grid">
        <div>
            <span class="eyebrow">Заявка</span>
            <h2>Заявка на ремонт дома</h2>
            <p>Приложите план дома, фотографии помещений или проект — это поможет точнее собрать смету.</p>
        </div>
        <?php $formId = 'lead-form'; $leadType = 'house'; require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
<section class="section contacts-mini">
    <div class="container">
        <h2>Контакты</h2>
        <p>
            <strong>КВАРТИРАФОРМАТ</strong><br>
            <?= e($settings['legal_name'] ?? '') ?><br>
            ИНН <?= e($settings['inn'] ?? '') ?><br>
            <a href="<?= e($settings['phone_href'] ?? '') ?>"><?= e($settings['phone'] ?? '') ?></a><br>
            <?= e($settings['office_address'] ?? '') ?><br>
            Приём звонков: <?= e(working_hours($settings)) ?>. <?= e(working_hours_note($settings)) ?>
        </p>
        <div class="geo-list">
            <a href="/remont-kvartir/">Ремонт квартир</a>
            <a href="/remont-kommercheskih-pomescheniy/">Коммерческий ремонт</a>
            <a href="/contacts/">Все контакты</a>
        </div>
    </div>
</section>
