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
    <link rel="preload" as="font" href="<?= asset('fonts/manrope-cyrillic.woff2') ?>" type="font/woff2" crossorigin>
    <link rel="preload" as="font" href="<?= asset('fonts/manrope-latin.woff2') ?>" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
    <script defer src="<?= asset('js/app.js') ?>"></script>
    <?php
        $ymId = preg_replace('/\D+/', '', (string)($s['ym_counter_id'] ?? '')) ?: '113599005';
    ?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        window.KF_METRIKA_ID = <?= (int)$ymId ?>;
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=<?= (int)$ymId ?>', 'ym');

        ym(<?= (int)$ymId ?>, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/<?= (int)$ymId ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <script type="application/ld+json"><?= json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'HomeAndConstructionBusiness',
        'name' => $s['brand_name'] ?? 'КвартираФормат',
        'description' => 'Ремонт квартир, домов и коммерческих помещений под ключ в Москве и Московской области',
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
        <a class="logo" href="/" aria-label="КвартираФормат — главная"><span>КВАРТИРА</span><b>ФОРМАТ</b><small><?= e($s['brand_tagline'] ?? 'Ремонт квартир, домов и коммерции') ?></small></a>
        <button class="nav-toggle" type="button" data-nav-toggle aria-label="Открыть меню">☰</button>
        <nav class="nav" data-nav>
            <a href="/">Главная</a>
            <div class="nav-item has-menu">
                <a class="nav-link" href="/remont-kvartir/">Ремонт квартир</a>
                <button class="nav-more" type="button" data-submenu-toggle aria-label="Категории ремонта квартир">▾</button>
                <div class="nav-submenu">
                    <a href="/remont-kvartir/">Все услуги</a>
                    <a href="/uslugi/kosmeticheskiy-remont/">Косметический</a>
                    <a href="/uslugi/kapitalnyy-remont/">Капитальный</a>
                    <a href="/uslugi/remont-kvartir-pod-klyuch/">Под ключ</a>
                </div>
            </div>
            <div class="nav-item has-menu">
                <a class="nav-link" href="/remont-domov/">Ремонт домов</a>
                <button class="nav-more" type="button" data-submenu-toggle aria-label="Категории ремонта домов">▾</button>
                <div class="nav-submenu">
                    <a href="/remont-domov/">Все услуги</a>
                    <a href="/remont-domov-pod-klyuch/">Дома под ключ</a>
                    <a href="/kapitalnyy-remont-domov/">Капитальный</a>
                    <a href="/remont-taunhausov/">Таунхаусы</a>
                </div>
            </div>
            <div class="nav-item has-menu">
                <a class="nav-link" href="/remont-kommercheskih-pomescheniy/">Коммерческий ремонт</a>
                <button class="nav-more" type="button" data-submenu-toggle aria-label="Категории коммерческого ремонта">▾</button>
                <div class="nav-submenu">
                    <a href="/remont-kommercheskih-pomescheniy/">Все услуги</a>
                    <a href="/remont-ofisov/">Офисы</a>
                    <a href="/remont-magazinov/">Магазины</a>
                    <a href="/remont-kafe-restoranov/">Кафе</a>
                </div>
            </div>
            <a href="/#prices">Цены</a>
            <a href="/portfolio/">Наши работы</a>
            <a href="/contacts/">Контакты</a>
        </nav>
        <a class="header-phone" data-goal="phone_click" href="<?= e($s['phone_href'] ?? 'tel:+79162659262') ?>"><?= e($s['phone'] ?? '+7 (916) 265-92-62') ?></a>
    </div>
</header>
<?php if (!empty($dbError)): ?><div class="notice">Режим предпросмотра: <?= e($dbError) ?></div><?php endif; ?>
<main><?= $content ?></main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div><a class="logo logo-footer" href="/"><span>КВАРТИРА</span><b>ФОРМАТ</b><small>Квартиры, дома, коммерция</small></a><p>Москва и Московская область. Ремонт квартир, частных домов и коммерческих помещений.</p></div>
        <div><h3>Направления</h3><a href="/remont-kvartir/">Ремонт квартир</a><a href="/remont-domov/">Ремонт домов</a><a href="/remont-kommercheskih-pomescheniy/">Коммерческий ремонт</a><a href="/#prices">Цены</a><a href="/portfolio/">Наши работы</a><a href="/contacts/">Контакты</a></div>
        <div><h3>Популярные услуги</h3><a href="/uslugi/remont-kvartir-pod-klyuch/">Квартиры под ключ</a><a href="/remont-domov-pod-klyuch/">Дома под ключ</a><a href="/remont-ofisov/">Офисы</a><a href="/remont-magazinov/">Магазины</a><a href="/remont-taunhausov/">Таунхаусы</a></div>
        <div><h3>Реквизиты</h3><p><?= e($s['legal_name'] ?? '') ?><br>ИНН <?= e($s['inn'] ?? '') ?><br><a href="<?= e($s['phone_href'] ?? '') ?>"><?= e($s['phone'] ?? '') ?></a><br><?= e($s['office_address'] ?? '') ?></p></div>
        <div><h3>Документы</h3><a href="/privacy-policy/">Политика обработки персональных данных</a><a href="/personal-data-consent/">Согласие на обработку персональных данных</a><a href="/cookies/">Cookies</a></div>
    </div>
</footer>
<nav class="mobile-actions" aria-label="Быстрые действия"><a data-goal="phone_click" href="<?= e($s['phone_href'] ?? '') ?>">Позвонить</a><a href="<?= e($calcHref ?? '/#calculator') ?>" data-goal="calculator_start">Рассчитать</a><a href="<?= e($leadHref ?? '/#lead-form') ?>" data-goal="callback_request">Заявка</a></nav>
</body>
</html>
