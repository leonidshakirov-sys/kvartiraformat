<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Support\QuizCatalog;
use App\Support\StartingPrices;

$tariffs = [
    'apartment' => StartingPrices::tariffs('apartment'),
    'house' => StartingPrices::tariffs('house'),
    'commercial' => StartingPrices::tariffs('commercial'),
];
$errors = [];

$fill = static function (array $post): array {
    $quiz = $post['quiz'] ?? [];
    $direction = (string)($quiz['direction'] ?? '');
    $defaults = match ($direction) {
        'apartment' => ['apt_property' => 'newbuild', 'apt_condition' => 'shell', 'apt_extras' => ['none']],
        'house' => ['house_property' => 'house', 'house_floors' => '2', 'house_condition' => 'rough', 'house_extras' => ['stairs']],
        'commercial' => ['com_condition' => 'shell', 'com_extras' => ['walls'], 'com_project' => 'no'],
        default => [],
    };
    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $quiz)) {
            $quiz[$key] = $value;
        }
    }
    $post['quiz'] = $quiz;
    return $post;
};

$check = static function (string $name, array $post, array $expect) use ($tariffs, &$errors, $fill): void {
    if (($expect['fill'] ?? true) === true) {
        $post = $fill($post);
    }
    $quote = QuizCatalog::quote($post, $tariffs, $expect['settings'] ?? []);
    foreach ($expect as $key => $value) {
        if ($key === 'settings' || $key === 'fill') {
            continue;
        }
        $actual = $quote[$key] ?? null;
        if ($actual !== $value) {
            $errors[] = $name . ' ' . $key . ' expected ' . json_encode($value, JSON_UNESCAPED_UNICODE) . ' got ' . json_encode($actual, JSON_UNESCAPED_UNICODE);
        }
    }
};

$check('capital-60', [
    'quiz' => ['direction' => 'apartment', 'apt_repair' => 'capital', 'apt_area' => '60', 'apt_property' => 'secondary', 'apt_condition' => 'old', 'apt_extras' => ['electric', 'tile']],
], [
    'ok' => true,
    'individual' => false,
    'price' => 15000,
    'total' => 900000,
    'price_label' => 'от 15 000 ₽/м²',
    'total_label' => 'от 900 000 ₽',
    'tariff_slug' => 'kapitalnyy-remont',
    'object_label' => 'Квартира',
    'repair_label' => 'Капитальный',
]);

$check('cosmetic-40', ['quiz' => ['direction' => 'apartment', 'apt_repair' => 'cosmetic', 'apt_area' => '40']], ['total' => 320000, 'price' => 8000]);
$check('turnkey-20', ['quiz' => ['direction' => 'apartment', 'apt_repair' => 'turnkey', 'apt_area' => '20']], ['total' => 320000, 'price' => 16000]);
$check('designer-10', ['quiz' => ['direction' => 'apartment', 'apt_repair' => 'designer', 'apt_area' => '10']], ['total' => 220000, 'price' => 22000]);
$check('house-capital', ['quiz' => ['direction' => 'house', 'house_repair' => 'capital', 'house_area' => '100', 'house_floors' => '4plus']], ['total' => 1700000, 'price' => 17000, 'tariff_slug' => 'kapitalnyy']);
$check('house-cosmetic', ['quiz' => ['direction' => 'house', 'house_repair' => 'cosmetic', 'house_area' => '50']], ['total' => 450000, 'price' => 9000]);
$check('house-turnkey', ['quiz' => ['direction' => 'house', 'house_repair' => 'turnkey', 'house_area' => '10']], ['total' => 180000, 'price' => 18000]);
$check('house-after', ['quiz' => ['direction' => 'house', 'house_repair' => 'after_build', 'house_area' => '70']], ['total' => 1120000, 'price' => 16000, 'tariff_slug' => 'posle-stroitelstva']);
$check('office', ['quiz' => ['direction' => 'commercial', 'com_purpose' => 'office', 'com_repair' => 'capital', 'com_area' => '100']], ['total' => 700000, 'price' => 7000, 'tariff_slug' => 'remont-ofisov', 'object_label' => 'Офис']);
$check('cafe', ['quiz' => ['direction' => 'commercial', 'com_purpose' => 'cafe', 'com_repair' => 'cosmetic', 'com_area' => '30']], ['total' => 360000, 'price' => 12000]);
$check('warehouse', ['quiz' => ['direction' => 'commercial', 'com_purpose' => 'warehouse', 'com_repair' => 'turnkey', 'com_area' => '200']], ['total' => 1000000, 'price' => 5000]);
$check('salon', ['quiz' => ['direction' => 'commercial', 'com_purpose' => 'salon', 'com_repair' => 'cosmetic', 'com_area' => '10']], ['total' => 100000, 'price' => 10000]);
$check('store', ['quiz' => ['direction' => 'commercial', 'com_purpose' => 'store', 'com_repair' => 'capital', 'com_area' => '40']], ['total' => 320000, 'price' => 8000]);
$check('free', ['quiz' => ['direction' => 'commercial', 'com_purpose' => 'free', 'com_repair' => 'turnkey', 'com_area' => '25']], ['total' => 200000, 'price' => 8000]);

$check('extras-ignored', [
    'quiz' => ['direction' => 'apartment', 'apt_repair' => 'capital', 'apt_area' => '60', 'apt_extras' => ['electric', 'plumbing', 'walls']],
], ['total' => 900000]);

$check('none-extra', [
    'quiz' => ['direction' => 'apartment', 'apt_repair' => 'capital', 'apt_area' => '60', 'apt_extras' => ['none', 'electric']],
], ['total' => 900000, 'extras_label' => 'Ничего дополнительного']);

$check('bathroom', [
    'service_slug' => 'remont-vannoy-i-sanuzla',
    'quiz' => ['direction' => 'apartment', 'apt_repair' => 'capital', 'apt_area' => '8'],
], ['total' => 180000, 'unit' => 'room', 'tariff_slug' => 'remont-vannoy-i-sanuzla', 'repair_label' => 'Санузел']);

$check('missing-area', ['quiz' => ['direction' => 'apartment', 'apt_repair' => 'capital']], ['ok' => false, 'fill' => false]);

$unapproved = $tariffs;
foreach ($unapproved['apartment'] as &$row) {
    if (($row['slug'] ?? '') === 'kapitalnyy-remont') {
        $row['is_approved'] = 0;
    }
}
unset($row);
$hidden = QuizCatalog::quote([
    'quiz' => ['direction' => 'apartment', 'apt_repair' => 'capital', 'apt_area' => '60'],
], $unapproved);
if (empty($hidden['individual']) || ($hidden['total'] ?? null) !== null) {
    $errors[] = 'unapproved tariff must stay individual';
}

$settings = ['quiz_config' => json_encode(['steps' => [[
    'id' => 'apt_condition',
    'options' => [[
        'value' => 'shell',
        'coefficient' => '1.1',
        'coefficient_approved' => true,
    ]],
]]], JSON_UNESCAPED_UNICODE)];
$withFactor = QuizCatalog::quote([
    'quiz' => ['direction' => 'apartment', 'apt_property' => 'newbuild', 'apt_repair' => 'capital', 'apt_area' => '60', 'apt_condition' => 'shell', 'apt_extras' => ['none']],
], $tariffs, $settings);
if (($withFactor['total'] ?? 0) !== 990000 || empty($withFactor['coefficient_applied'])) {
    $errors[] = 'approved coefficient expected 990000 got ' . json_encode($withFactor['total'] ?? null);
}
$draft = ['quiz_config' => json_encode(['steps' => [[
    'id' => 'apt_condition',
    'options' => [[
        'value' => 'shell',
        'coefficient' => '1.1',
        'coefficient_approved' => false,
    ]],
]]], JSON_UNESCAPED_UNICODE)];
$without = QuizCatalog::quote([
    'quiz' => ['direction' => 'apartment', 'apt_property' => 'newbuild', 'apt_repair' => 'capital', 'apt_area' => '60', 'apt_condition' => 'shell', 'apt_extras' => ['none']],
], $tariffs, $draft);
if (($without['total'] ?? 0) !== 900000 || !empty($without['coefficient_applied'])) {
    $errors[] = 'draft coefficient must not change 900000';
}

$disabled = ['quiz_config' => json_encode(['steps' => [[
    'id' => 'apt_condition',
    'enabled' => false,
    'required' => false,
]]], JSON_UNESCAPED_UNICODE)];
$skipped = QuizCatalog::quote([
    'quiz' => ['direction' => 'apartment', 'apt_property' => 'newbuild', 'apt_repair' => 'capital', 'apt_area' => '60', 'apt_extras' => ['none']],
], $tariffs, $disabled);
if (empty($skipped['ok']) || ($skipped['total'] ?? 0) !== 900000) {
    $errors[] = 'disabled condition must not block the quote';
}

$preset = QuizCatalog::presentation([], [
    'direction' => 'apartment',
    'lock_direction' => true,
    'service_slug' => 'kapitalnyy-remont',
]);
if (($preset['preset']['answers']['apt_repair'] ?? '') !== 'capital' || empty($preset['preset']['lock'])) {
    $errors[] = 'service preset failed';
}
$home = QuizCatalog::presentation([], ['direction' => 'apartment', 'lock_direction' => false]);
if (($home['preset']['direction'] ?? '') !== '') {
    $errors[] = 'home quiz must ask for the object type';
}

if ($errors) {
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(1);
}

echo "quiz-quotes-ok\ncapital-60=900000\noffice-separate\ncoefficient-gated\n";
