<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/helpers.php';
require dirname(__DIR__) . '/app/Support/StartingPrices.php';

use App\Support\StartingPrices;

$expected = [
    'apartment:kosmeticheskiy-remont' => [8000, 'm2', 'от 8 000 ₽/м²'],
    'apartment:kapitalnyy-remont' => [15000, 'm2', 'от 15 000 ₽/м²'],
    'apartment:remont-kvartir-pod-klyuch' => [16000, 'm2', 'от 16 000 ₽/м²'],
    'apartment:dizaynerskiy-remont' => [22000, 'm2', 'от 22 000 ₽/м²'],
    'apartment:remont-novostroek' => [14000, 'm2', 'от 14 000 ₽/м²'],
    'apartment:remont-vtorichnogo-zhilya' => [16000, 'm2', 'от 16 000 ₽/м²'],
    'apartment:remont-studiy' => [14000, 'm2', 'от 14 000 ₽/м²'],
    'apartment:remont-odnokomnatnyh-kvartir' => [14000, 'm2', 'от 14 000 ₽/м²'],
    'apartment:remont-dvuhkomnatnyh-kvartir' => [14000, 'm2', 'от 14 000 ₽/м²'],
    'apartment:remont-trehkomnatnyh-kvartir' => [14000, 'm2', 'от 14 000 ₽/м²'],
    'apartment:remont-vannoy-i-sanuzla' => [180000, 'room', 'от 180 000 ₽ за помещение'],
    'house:kosmeticheskiy' => [9000, 'm2', 'от 9 000 ₽/м²'],
    'house:kapitalnyy' => [17000, 'm2', 'от 17 000 ₽/м²'],
    'house:pod-klyuch' => [18000, 'm2', 'от 18 000 ₽/м²'],
    'house:otdelka-kottedzhey' => [15000, 'm2', 'от 15 000 ₽/м²'],
    'house:taunkhausov' => [16000, 'm2', 'от 16 000 ₽/м²'],
    'house:zagorodnyh' => [16000, 'm2', 'от 16 000 ₽/м²'],
    'house:posle-stroitelstva' => [16000, 'm2', 'от 16 000 ₽/м²'],
    'house:sanuzlov' => [180000, 'room', 'от 180 000 ₽ за помещение'],
    'commercial:remont-kommercheskih-pomescheniy' => [8000, 'm2', 'от 8 000 ₽/м²'],
    'commercial:remont-ofisov' => [7000, 'm2', 'от 7 000 ₽/м²'],
    'commercial:remont-magazinov' => [8000, 'm2', 'от 8 000 ₽/м²'],
    'commercial:remont-salonov-krasoty' => [10000, 'm2', 'от 10 000 ₽/м²'],
    'commercial:remont-kafe-restoranov' => [12000, 'm2', 'от 12 000 ₽/м²'],
    'commercial:remont-skladskih-pomescheniy' => [5000, 'm2', 'от 5 000 ₽/м²'],
    'commercial:remont-pomescheniy-svobodnogo-naznacheniya' => [8000, 'm2', 'от 8 000 ₽/м²'],
];

$errors = [];
$catalog = StartingPrices::catalog();
if (count($catalog) !== 26) {
    $errors[] = 'catalog-count=' . count($catalog);
}
$seen = [];
foreach ($catalog as $item) {
    $key = $item['category'] . ':' . $item['slug'];
    if (isset($seen[$key])) {
        $errors[] = 'duplicate ' . $key;
    }
    $seen[$key] = true;
    if (!isset($expected[$key])) {
        $errors[] = 'unexpected ' . $key;
        continue;
    }
    [$price, $unit, $label] = $expected[$key];
    if ((int)$item['price_per_m2'] !== $price || $item['unit'] !== $unit || (int)$item['is_approved'] !== 1 || (int)$item['includes_materials'] !== 0) {
        $errors[] = 'mismatch ' . $key;
    }
    $formatted = tariff_price($item + ['price_unit' => $item['unit'], 'is_approved' => 1]);
    if ($formatted['label'] !== $label || $formatted['note'] !== 'Стоимость работ без материалов') {
        $errors[] = 'label ' . $key . ' => ' . $formatted['label'];
    }
}
foreach (array_keys($expected) as $key) {
    if (!isset($seen[$key])) {
        $errors[] = 'missing ' . $key;
    }
}

$capital = 60 * 15000;
if ($capital !== 900000) {
    $errors[] = 'calculator-capital';
}
$office = 40 * 7000;
if ($office !== 280000) {
    $errors[] = 'calculator-office';
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "approved=26\ncapital-60m2=900000\nbathroom=180000-room\n";

if (in_array('--live', $argv ?? [], true)) {
    $pages = [
        'https://kvartiraformat.ru/',
        'https://kvartiraformat.ru/remont-kvartir/',
        'https://kvartiraformat.ru/remont-domov/',
        'https://kvartiraformat.ru/remont-kommercheskih-pomescheniy/',
        'https://kvartiraformat.ru/uslugi/kapitalnyy-remont/',
        'https://kvartiraformat.ru/remont-ofisov/',
    ];
    foreach ($pages as $url) {
        $html = @file_get_contents($url);
        $hasApproved = is_string($html) && (str_contains($html, 'от 15 000 ₽/м²') || str_contains($html, 'от 7 000 ₽/м²') || str_contains($html, 'от 18 000 ₽/м²') || str_contains($html, 'от 8 000 ₽/м²'));
        $individual = is_string($html) ? substr_count($html, 'Индивидуальный расчёт') : -1;
        echo $url . ' approved=' . ($hasApproved ? 'yes' : 'NO') . ' individual=' . $individual . "\n";
        if (!$hasApproved) {
            exit(1);
        }
    }
}
