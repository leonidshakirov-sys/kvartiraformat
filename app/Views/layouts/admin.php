<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($metaTitle ?? 'Админ-панель') ?></title><link rel="stylesheet" href="<?= asset('css/styles.css') ?>"></head>
<body class="admin-body">
<aside class="admin-sidebar"><a class="logo" href="/admin/"><span>КВАРТИРА</span><b>ФОРМАТ</b><small>админ-панель</small></a><nav>
<?php foreach (['dashboard'=>'Главная','pages'=>'Страницы','texts'=>'Тексты','photos'=>'Фотографии','services'=>'Услуги','portfolio'=>'Портфолио','seo'=>'SEO','calculator'=>'Калькулятор','leads'=>'Заявки','settings'=>'Настройки компании','integrations'=>'Интеграции'] as $key=>$label): ?><a class="<?= active_class($section ?? '', $key) ?>" href="/admin/<?= $key === 'dashboard' ? '' : $key ?>"><?= e($label) ?></a><?php endforeach; ?>
<form method="post" action="/admin/logout"><?= App\Core\Csrf::field() ?><button class="link-button">Выйти</button></form></nav></aside>
<div class="admin-main"><header class="admin-top"><strong><?= e($metaTitle ?? 'Админ-панель') ?></strong><a href="/" target="_blank">Открыть сайт</a></header>
<?php if (!empty($_SESSION['flash_success'])): ?><div class="alert success"><?= e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div><?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?><div class="alert error"><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div><?php endif; ?>
<?php if (!empty($dbError)): ?><div class="alert error"><?= e($dbError) ?></div><?php endif; ?>
<?= $content ?></div></body></html>
