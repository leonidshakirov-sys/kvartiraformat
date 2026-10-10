<?php
use App\Support\CommercialCatalog;
use App\Support\HouseCatalog;
use App\Support\SiteImages;
$heroImage = SiteImages::hero();
$priceImage = SiteImages::prices();
$houseServices = $houseServices ?? [];
$commercialServices = $commercialServices ?? [];
$commercialTariffs = $commercialTariffs ?? [];
?>
<section class="hero">
    <div class="hero-media"><?= picture($heroImage, '', 'eager') ?></div>
    <div class="container hero-content">
        <span class="eyebrow">Москва и Московская область</span>
        <h1><?= e($h1) ?></h1>
        <p class="lead">Выполняем косметический, капитальный и комплексный ремонт жилых и коммерческих объектов. Подготовим смету с учётом площади, состояния помещения и ваших пожеланий.</p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="#calculator">Рассчитать стоимость</a>
            <a class="btn btn-outline" href="#directions">Наши услуги</a>
            <a class="btn btn-ghost" href="#lead-form">Получить консультацию</a>
        </div>
    </div>
</section>
<section class="section section-surface" id="directions">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Направления</span>
            <h2>Три равнозначных направления ремонта</h2>
            <p>Компания работает с квартирами, частными домами и коммерческими помещениями. Выберите направление — внутри есть услуги, фотографии и форма расчёта.</p>
        </div>
        <div class="direction-grid">
            <a class="direction-card" href="/remont-kvartir/">
                <div class="media-frame"><?= picture(SiteImages::hero()) ?></div>
                <div class="direction-body">
                    <h3>Ремонт квартир</h3>
                    <p>Новостройки и вторичное жильё: косметический, капитальный и ремонт под ключ.</p>
                    <span class="btn btn-small">Перейти</span>
                </div>
            </a>
            <a class="direction-card" href="/remont-domov/">
                <div class="media-frame"><?= picture(HouseCatalog::image('hero')) ?></div>
                <div class="direction-body">
                    <h3>Ремонт домов</h3>
                    <p>Внутренняя отделка частных домов, коттеджей и таунхаусов. Смета после осмотра.</p>
                    <span class="btn btn-small">Перейти</span>
                </div>
            </a>
            <a class="direction-card" href="/remont-kommercheskih-pomescheniy/">
                <div class="media-frame"><?= picture(CommercialCatalog::image('hero')) ?></div>
                <div class="direction-body">
                    <h3>Коммерческий ремонт</h3>
                    <p>Офисы, магазины, салоны, кафе и склады. Расчёт по площади и техническому заданию.</p>
                    <span class="btn btn-small">Перейти</span>
                </div>
            </a>
        </div>
    </div>
</section>
<?php $calcLeadType = 'apartment'; require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section section-muted" id="apartments">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Квартиры</span>
            <h2>Ремонт квартир под ключ</h2>
            <p>Выполняем ремонт квартир в новостройках и вторичном жилье: от косметического обновления до капитального ремонта с заменой инженерных коммуникаций и чистовой отделкой.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($services as $service): $card = $service; $cardImage = SiteImages::serviceCard($service); $cardHref = '/uslugi/' . $service['slug'] . '/'; $cardCalcHref = '/uslugi/' . $service['slug'] . '/#calculator'; require __DIR__ . '/../partials/service-card.php'; endforeach; ?>
        </div>
        <a class="btn btn-outline" href="/remont-kvartir/">Все услуги по квартирам</a>
    </div>
</section>
<section class="section section-surface" id="houses">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Дома</span>
            <h2>Ремонт частных домов и коттеджей под ключ</h2>
            <p>Внутренняя отделка и комплексный ремонт частных домов, коттеджей и таунхаусов в Москве и Подмосковье. Работы от черновой подготовки до чистовой отделки помещений.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($houseServices as $item): $card = $item; $cardImage = ['src' => $item['image_path'], 'alt' => $item['image_alt'] ?? $item['title'], 'width' => 1600, 'height' => 1000]; $cardHref = '/' . $item['slug'] . '/'; $cardCalcHref = '/' . $item['slug'] . '/#calculator'; require __DIR__ . '/../partials/service-card.php'; endforeach; ?>
        </div>
        <a class="btn btn-outline" href="/remont-domov/">Все услуги по домам</a>
    </div>
</section>
<section class="section section-muted" id="commercial">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Коммерция</span>
            <h2>Ремонт коммерческих помещений под ключ</h2>
            <p>Офисы, магазины, салоны красоты, кафе, склады и помещения свободного назначения. Смета по площади, состоянию и техническому заданию.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($commercialServices as $item): $card = $item; $cardImage = ['src' => $item['image_path'], 'alt' => $item['image_alt'] ?? $item['title'], 'width' => 1600, 'height' => 1000]; $cardHref = '/' . $item['slug'] . '/'; $cardCalcHref = '/' . $item['slug'] . '/#calculator'; require __DIR__ . '/../partials/service-card.php'; endforeach; ?>
        </div>
        <a class="btn btn-outline" href="/remont-kommercheskih-pomescheniy/">Все коммерческие услуги</a>
    </div>
</section>
<section class="section section-surface" id="prices">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Стоимость</span>
            <h2>Стоимость ремонтных работ</h2>
            <p>Для квартир и коммерции показаны утверждённые стартовые цены «от» за м². Для домов цена не копируется с квартирного тарифа: на сайте — индивидуальный расчёт после осмотра.</p>
        </div>
        <div class="media-frame price-photo">
            <?= picture($priceImage) ?>
        </div>
        <div class="price-table">
            <table>
                <thead>
                    <tr><th>Направление</th><th>Вид работ</th><th>Цена</th><th></th></tr>
                </thead>
                <tbody>
                    <?php
                    $priceRows = [
                        ['Квартиры', 'Косметический', $tariffs, 'apartment', '#calculator'],
                        ['Квартиры', 'Капитальный', $tariffs, 'apartment', '#calculator'],
                        ['Квартиры', 'Дизайнерский', $tariffs, 'apartment', '#calculator'],
                    ];
                    foreach ($priceRows as [$dir, $type, $list, $mode, $href]):
                        $price = 'Индивидуальный расчёт';
                        $approved = true;
                        foreach ($list as $t) {
                            if (($t['repair_type'] ?? '') === $type && (int)($t['price_per_m2'] ?? 0) > 0) {
                                $approved = !array_key_exists('is_approved', $t) || (int)$t['is_approved'] === 1;
                                $price = format_price((int)$t['price_per_m2'], $approved);
                                break;
                            }
                        }
                    ?>
                        <tr>
                            <td><?= e($dir) ?></td>
                            <td><?= e($type) ?></td>
                            <td><?= e($price) ?></td>
                            <td><a class="btn btn-small" href="<?= e($href) ?>">Рассчитать</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td>Дома</td>
                        <td>Внутренний ремонт и отделка</td>
                        <td>Индивидуальный расчёт</td>
                        <td><a class="btn btn-small" href="/remont-domov/#calculator">Рассчитать</a></td>
                    </tr>
                    <?php foreach (array_slice($commercialTariffs, 0, 3) as $t): ?>
                        <tr>
                            <td>Коммерция</td>
                            <td><?= e($t['repair_type'] ?? '') ?></td>
                            <td><?= e(format_price((int)($t['price_per_m2'] ?? 0), !array_key_exists('is_approved', $t) || (int)$t['is_approved'] === 1)) ?></td>
                            <td><a class="btn btn-small" href="/remont-kommercheskih-pomescheniy/#calculator">Рассчитать</a></td>
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
            <span class="eyebrow">Преимущества компании</span>
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
            <p>Показываем качественные интерьерные фото как ориентир по стилю. Это иллюстрации, а не съёмка объектов компании. Когда появятся реальные фотографии, заменим их в админ-панели.</p>
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
            <span class="eyebrow">Этапы сотрудничества</span>
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
            <span class="eyebrow">География обслуживания</span>
            <h2>Москва и Московская область</h2>
            <p>Городские страницы по ремонту квартир сохранены. Отдельные массовые страницы по улицам не создаём.</p>
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
            <span class="eyebrow">Ответы на вопросы</span>
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
            <h2>Оставьте заявку на расчёт</h2>
            <p>Укажите контакты и при необходимости приложите план, фотографии или техническое задание.</p>
        </div>
        <?php $formId = 'lead-form'; $leadType = 'apartment'; require __DIR__ . '/../partials/lead-form.php'; ?>
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
