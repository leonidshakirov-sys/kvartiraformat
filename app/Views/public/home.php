<?php
use App\Support\SiteImages;
$heroImage = SiteImages::hero();
$priceImage = SiteImages::prices();
?>
<section class="hero">
    <div class="hero-media"><?= picture($heroImage, '', 'eager') ?></div>
    <div class="container hero-content">
        <span class="eyebrow">Москва и Московская область</span>
        <h1><?= e($h1) ?></h1>
        <p class="lead">Косметический, капитальный и дизайнерский ремонт квартир. Рассчитаем стоимость, подготовим смету и проведём работы от черновой отделки до готового интерьера.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="#calculator">Рассчитать стоимость</a>
            <a class="btn btn-outline" href="#lead-form">Заказать замер</a>
            <a class="btn btn-ghost" href="/portfolio/">Примеры интерьеров</a>
        </div>
        <ul class="hero-points">
            <li>Стартовые цены на сайте, точная смета после замера</li>
            <li>Работаем с новостройками и вторичным жильём</li>
            <li>Ремонт квартир и коммерческих помещений под ключ</li>
        </ul>
    </div>
</section>
<?php require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section section-muted" id="services">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Виды ремонта</span>
            <h2>Ремонт квартир под ваши задачи</h2>
            <p>Выберите подходящий формат: от косметического обновления до комплексного ремонта под ключ. На каждой странице — состав работ и заявка на расчёт.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($services as $service): $card = $service; $cardImage = SiteImages::serviceCard($service); $cardHref = '/uslugi/' . $service['slug'] . '/'; $cardCalcHref = '#calculator'; require __DIR__ . '/../partials/service-card.php'; endforeach; ?>
        </div>
    </div>
</section>
<section class="section section-surface commercial-home" id="commercial">
    <div class="container">
        <div class="commercial-spotlight">
            <div class="media-frame commercial-photo">
                <?= picture(\App\Support\CommercialCatalog::image('hero')) ?>
            </div>
            <div>
                <span class="eyebrow">Коммерческий ремонт</span>
                <h2>Ремонт коммерческих помещений под ключ</h2>
                <p>Офисы, магазины, салоны красоты, кафе и склады в Москве и области. Собираем смету по площади, состоянию помещения и техническому заданию.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="/remont-kommercheskih-pomescheniy/">Подробнее</a>
                    <a class="btn btn-outline" href="/remont-kommercheskih-pomescheniy/#calculator">Рассчитать стоимость</a>
                </div>
            </div>
        </div>
        <div class="cards service-cards commercial-cards">
            <?php foreach ($commercialServices as $item): $card = $item; $cardImage = ['src' => $item['image_path'], 'alt' => $item['image_alt'] ?? $item['title'], 'width' => 1600, 'height' => 1000]; $cardHref = '/' . $item['slug'] . '/'; $cardCalcHref = '/' . $item['slug'] . '/#calculator'; require __DIR__ . '/../partials/service-card.php'; endforeach; ?>
        </div>
    </div>
</section>
<section class="section section-muted" id="prices">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Стоимость</span>
            <h2>Стартовые цены на ремонт</h2>
            <p>Цены указаны «от» за квадратный метр работ. Итоговая смета зависит от состояния квартиры, состава работ и материалов и готовится после замера.</p>
        </div>
        <div class="media-frame price-photo">
            <?= picture($priceImage) ?>
        </div>
        <div class="price-table">
            <table>
                <thead>
                    <tr><th>Вид работ</th><th>Что входит</th><th>Цена за м²</th><th></th></tr>
                </thead>
                <tbody>
                    <?php
                    $priceRows = [
                        'Косметический' => 'Обновление стен, пола, потолков и отдельных элементов без капитальной переделки',
                        'Капитальный' => 'Демонтаж, черновые работы, инженерия и финишная отделка',
                        'Дизайнерский' => 'Комплекс работ с расширенной проработкой решений и материалов',
                    ];
                    foreach ($priceRows as $type => $what):
                        $price = 'По расчёту';
                        foreach ($tariffs as $t) {
                            if (($t['repair_type'] ?? '') === $type && ($t['package_type'] ?? '') === 'Только работы') {
                                $price = format_price((int)$t['price_per_m2']);
                                break;
                            }
                        }
                        if ($price === 'По расчёту') {
                            foreach ($tariffs as $t) {
                                if (($t['repair_type'] ?? '') === $type) {
                                    $price = format_price((int)$t['price_per_m2']);
                                    break;
                                }
                            }
                        }
                    ?>
                        <tr>
                            <td><?= e($type) ?></td>
                            <td><?= e($what) ?></td>
                            <td><?= e($price) ?></td>
                            <td><a class="btn btn-small" href="#calculator">Рассчитать</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<section class="section section-dark" id="about">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Почему выбирают нас</span>
            <h2>Прозрачный процесс и понятная смета</h2>
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
            <span class="eyebrow">Наши работы</span>
            <h2>Примеры интерьеров после ремонта</h2>
            <p>Показываем качественные интерьерные фото как ориентир по стилю. Когда появятся съёмки выполненных объектов, заменим иллюстрации реальными работами.</p>
        </div>
        <div class="portfolio-grid">
            <?php foreach ($portfolio as $index => $item): $headingTag = 'h3'; require __DIR__ . '/../partials/portfolio-card.php'; endforeach; ?>
        </div>
        <a class="btn btn-outline" href="/portfolio/">Смотреть портфолио</a>
    </div>
</section>
<section class="section section-surface">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Этапы</span>
            <h2>Как строится работа</h2>
        </div>
        <ol class="steps">
            <li>Получение заявки</li>
            <li>Консультация</li>
            <li>Выезд и замер</li>
            <li>Подготовка сметы</li>
            <li>Согласование договора</li>
            <li>Выполнение ремонта</li>
            <li>Сдача объекта</li>
        </ol>
    </div>
</section>
<section class="section section-muted" id="geo">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Где работаем</span>
            <h2>Москва и Московская область</h2>
            <p>Выберите город или район, чтобы перейти на страницу с расчётом стоимости ремонта.</p>
        </div>
        <div class="geo-list">
            <?php foreach ($geoPages as $geo): ?>
                <a href="/remont-kvartir/<?= e($geo['slug']) ?>/"><?= e($geo['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section section-surface">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">FAQ</span>
            <h2>Ответы на частые вопросы</h2>
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
<section class="section cta section-dark">
    <div class="container cta-grid">
        <div>
            <span class="eyebrow">Заявка</span>
            <h2>Узнайте стоимость ремонта вашей квартиры</h2>
            <p>Оставьте контакты и параметры объекта — мы свяжемся, уточним детали и подготовим индивидуальный расчёт.</p>
        </div>
        <?php $formId = 'lead-form'; require __DIR__ . '/../partials/lead-form.php'; ?>
    </div>
</section>
<section class="section section-surface contacts-mini">
    <div class="container">
        <h2>Контакты</h2>
        <p>
            <strong>КВАРТИРАФОРМАТ</strong><br>
            <?= e($settings['legal_name'] ?? '') ?><br>
            ИНН <?= e($settings['inn'] ?? '') ?><br>
            <a href="<?= e($settings['phone_href'] ?? '') ?>"><?= e($settings['phone'] ?? '') ?></a><br>
            <?= e($settings['office_address'] ?? '') ?>
        </p>
        <a class="btn btn-primary" href="/contacts/">Открыть контакты</a>
    </div>
</section>
