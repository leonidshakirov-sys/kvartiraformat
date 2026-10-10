<?php
use App\Support\SiteImages;
$heroImage = SiteImages::hero();
$priceImage = SiteImages::prices();
?>
<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="eyebrow">Москва и Московская область</span>
            <h1><?= e($h1) ?></h1>
            <p class="lead">Косметический, капитальный и дизайнерский ремонт квартир. Рассчитаем стоимость работ, подготовим смету и поможем организовать ремонт от начала до завершения.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="#calculator">Рассчитать стоимость</a>
                <a class="btn btn-outline" href="#lead-form">Заказать замер</a>
                <a class="btn btn-ghost" href="/portfolio/">Примеры интерьеров</a>
            </div>
            <ul class="hero-points">
                <li>Смета после замера, без неподтверждённых цен</li>
                <li>Работаем с новостройками и вторичным жильём</li>
                <li>Ремонт квартир в Москве и Московской области</li>
            </ul>
        </div>
        <div class="hero-card">
            <div class="media-frame hero-photo">
                <?= picture($heroImage, '', 'eager') ?>
            </div>
            <p>От черновой отделки до готового интерьера. Рассчитаем стоимость по вашим размерам и пожеланиям.</p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/../partials/calculator.php'; ?>
<section class="section" id="services">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Виды ремонта</span>
            <h2>Ремонт квартир под ваши задачи</h2>
            <p>Каждая услуга открывается на отдельной странице с описанием и заявкой на расчёт.</p>
        </div>
        <div class="cards service-cards">
            <?php foreach ($services as $service): $cardImage = SiteImages::serviceCard($service); ?>
                <article class="card service-card">
                    <div class="media-frame card-image">
                        <?= picture($cardImage) ?>
                    </div>
                    <h3><?= e($service['title']) ?></h3>
                    <p><?= e($service['short'] ?? '') ?></p>
                    <strong><?= format_price(isset($service['price']) ? (int)$service['price'] : null) ?></strong>
                    <div class="card-actions">
                        <a class="btn btn-small" href="/uslugi/<?= e($service['slug']) ?>/">Подробнее</a>
                        <a class="btn btn-small btn-outline" href="#calculator">Рассчитать</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section alt" id="prices">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Стоимость</span>
            <h2>Ориентиры по видам ремонта</h2>
            <p>Если тариф ещё не подтверждён, в таблице указано «По расчёту». Итоговая смета готовится после замера.</p>
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
                    <?php foreach (['Косметический' => 'Финишное обновление стен, пола, потолков и отдельных элементов', 'Капитальный' => 'Демонтаж, черновые работы, инженерные системы и финишная отделка', 'Дизайнерский' => 'Комплекс работ с расширенной проработкой решений и материалов'] as $type => $what): ?>
                        <tr>
                            <td><?= e($type) ?></td>
                            <td><?= e($what) ?></td>
                            <td><?php $price = 'По расчёту'; foreach ($tariffs as $t) { if (($t['repair_type'] ?? '') === $type) { $price = format_price((int)$t['price_per_m2']); break; } } echo e($price); ?></td>
                            <td><a class="btn btn-small" href="#calculator">Рассчитать</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<section class="section" id="about">
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
<section class="section alt">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Наши работы</span>
            <h2>Примеры интерьеров после ремонта</h2>
            <p>Пока в портфолио нет фотографий выполненных объектов, публикуем качественные иллюстративные интерьеры. Они не выдаются за работы компании.</p>
        </div>
        <div class="portfolio-grid">
            <?php foreach ($portfolio as $index => $item): $headingTag = 'h3'; require __DIR__ . '/../partials/portfolio-card.php'; endforeach; ?>
        </div>
        <a class="btn btn-outline" href="/portfolio/">Смотреть портфолио</a>
    </div>
</section>
<section class="section">
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
<section class="section alt" id="geo">
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
<section class="section">
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
<section class="section cta">
    <div class="container cta-grid">
        <div>
            <span class="eyebrow">Заявка</span>
            <h2>Узнайте стоимость ремонта вашей квартиры</h2>
            <p>Отправьте параметры квартиры — мы свяжемся, уточним детали и подготовим индивидуальный расчёт.</p>
        </div>
        <?php $formId = 'lead-form'; require __DIR__ . '/../partials/lead-form.php'; ?>
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
            <?= e($settings['office_address'] ?? '') ?>
        </p>
        <a class="btn btn-primary" href="/contacts/">Открыть контакты</a>
    </div>
</section>
