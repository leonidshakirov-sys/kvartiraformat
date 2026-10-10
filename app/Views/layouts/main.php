<?php use App\Core\Csrf; $s = $settings ?? []; $canonicalUrl = 'https://' . ($s['domain'] ?? 'kvartiraformat.ru') . ($canonical ?? ($currentPath ?? '/')); ?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($metaTitle ?? 'КвартираФормат') ?></title>
    <meta name="description" content="<?= e($metaDescription ?? '') ?>">
    <link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($metaTitle ?? 'КвартираФормат') ?>">
    <meta property="og:description" content="<?= e($metaDescription ?? '') ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <meta property="og:site_name" content="КвартираФормат">
    <meta property="og:image" content="<?= e('https://' . ($s['domain'] ?? 'kvartiraformat.ru') . ($ogImage ?? '/assets/images/hero/living-room.webp')) ?>">
    <meta property="og:image:alt" content="Современный интерьер квартиры после ремонта">
    <?php if (!empty($preloadImage)): ?>
        <link rel="preload" as="image" href="<?= e($preloadImage) ?>" type="image/webp">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
    <script defer src="<?= asset('js/app.js') ?>"></script>
    <?php if (!empty($s['ym_counter_id'])): ?>
        <script>window.KF_METRIKA_ID = '<?= e($s['ym_counter_id']) ?>';</script>
    <?php endif; ?>
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'HomeAndConstructionBusiness',
        'name' => $s['brand_name'] ?? 'КвартираФормат',
        'description' => 'Ремонт квартир под ключ в Москве и Московской области',
        'telephone' => $s['phone'] ?? '',
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s['office_address'] ?? '', 'addressLocality' => 'Москва', 'addressCountry' => 'RU'],
        'areaServed' => $s['service_area'] ?? '',
        'image' => 'https://' . ($s['domain'] ?? 'kvartiraformat.ru') . '/assets/images/hero/living-room.webp',
        'url' => 'https://' . ($s['domain'] ?? 'kvartiraformat.ru'),
        'legalName' => $s['legal_name'] ?? '',
        'taxID' => $s['inn'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body>
<header class="site-header">
    <div class="container header-row">
        <a class="logo" href="/" aria-label="КвартираФормат — главная"><span>КВАРТИРА</span><b>ФОРМАТ</b><small><?= e($s['brand_tagline'] ?? 'Ремонт квартир под ключ') ?></small></a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-label="Открыть меню">☰</button>
        <nav class="nav" data-nav>
            <div class="nav-item has-menu">
                <button class="nav-link" type="button" data-submenu-toggle>Услуги</button>
                <div class="nav-submenu">
                    <a href="/#services">Ремонт квартир</a>
                    <a href="/remont-kommercheskih-pomescheniy/">Коммерческий ремонт</a>
                </div>
            </div>
            <a href="/#prices">Цены</a>
            <a href="/portfolio/">Портфолио</a>
            <a href="/#about">О компании</a>
            <a href="/contacts/">Контакты</a>
        </nav>
        <a class="header-phone" data-goal="phone_click" href="<?= e($s['phone_href'] ?? 'tel:+79162659262') ?>"><?= e($s['phone'] ?? '+7 (916) 265-92-62') ?></a>
    </div>
</header>
<?php if (!empty($dbError)): ?><div class="notice">Режим предпросмотра: <?= e($dbError) ?></div><?php endif; ?>
<main><?= $content ?></main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div><a class="logo logo-footer" href="/"><span>КВАРТИРА</span><b>ФОРМАТ</b><small>Ремонт квартир под ключ</small></a><p>Москва и Московская область. Комплексный ремонт квартир, апартаментов и вторичного жилья.</p></div>
        <div><h3>Разделы</h3><a href="/#services">Ремонт квартир</a><a href="/remont-kommercheskih-pomescheniy/">Коммерческий ремонт</a><a href="/#prices">Цены</a><a href="/portfolio/">Портфолио</a><a href="/contacts/">Контакты</a></div>
        <div><h3>Коммерческий ремонт</h3><a href="/remont-ofisov/">Офисы</a><a href="/remont-magazinov/">Магазины</a><a href="/remont-salonov-krasoty/">Салоны красоты</a><a href="/remont-kafe-restoranov/">Кафе и рестораны</a><a href="/remont-skladskih-pomescheniy/">Склады</a><a href="/remont-pomescheniy-svobodnogo-naznacheniya/">Свободное назначение</a></div>
        <div><h3>Реквизиты</h3><p><?= e($s['legal_name'] ?? '') ?><br>ИНН <?= e($s['inn'] ?? '') ?><br><a href="<?= e($s['phone_href'] ?? '') ?>"><?= e($s['phone'] ?? '') ?></a><br><?= e($s['office_address'] ?? '') ?></p></div>
        <div><h3>Документы</h3><a href="/privacy-policy/">Политика обработки персональных данных</a><a href="/personal-data-consent/">Согласие на обработку персональных данных</a><a href="/cookies/">Cookies</a></div>
    </div>
</footer>
<nav class="mobile-actions" aria-label="Быстрые действия"><a data-goal="phone_click" href="<?= e($s['phone_href'] ?? '') ?>">Позвонить</a><a href="<?= e($calcHref ?? '/#calculator') ?>" data-goal="calculator_start">Рассчитать</a><a href="<?= e($leadHref ?? '/#lead-form') ?>" data-goal="callback_request">Заявка</a></nav>
</body>
</html>
