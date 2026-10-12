<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $url = '/assets/' . $relative;
    $full = dirname(__DIR__) . '/public/assets/' . $relative;
    if (is_file($full)) {
        $url .= '?v=' . filemtime($full);
    }
    return $url;
}

function upload_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    return '/uploads/' . ltrim($path, '/');
}

function route_url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function format_price(?int $price, bool $approved = true, string $unit = 'm2'): string
{
    if (!$approved || !$price || $price <= 0) {
        return 'Индивидуальный расчёт';
    }
    $amount = number_format($price, 0, ',', ' ');
    if ($unit === 'room') {
        return 'от ' . $amount . ' ₽ за помещение';
    }
    return 'от ' . $amount . ' ₽/м²';
}

function price_unit_of(array $item): string
{
    $unit = (string)($item['price_unit'] ?? $item['unit'] ?? 'm2');
    return $unit === 'room' ? 'room' : 'm2';
}

/**
 * @param array<string,mixed> $item
 * @return array{label:string,note:string,approved:bool,amount:?int}
 */
function service_price(array $item): array
{
    $amount = isset($item['price']) && $item['price'] !== '' && $item['price'] !== null ? (int)$item['price'] : null;
    $approved = !empty($item['price_approved']);
    $label = format_price($amount, $approved, price_unit_of($item));
    return [
        'label' => $label,
        'note' => ($approved && $amount && $amount > 0) ? 'Стоимость работ без материалов' : '',
        'approved' => $approved && $amount && $amount > 0,
        'amount' => $amount,
    ];
}

function working_hours(array $settings): string
{
    $value = trim((string)($settings['working_hours'] ?? ''));
    return $value !== '' ? $value : 'ежедневно с 09:00 до 20:00';
}

function working_hours_note(array $settings): string
{
    $value = trim((string)($settings['working_hours_note'] ?? ''));
    return $value !== '' ? $value : 'Часы приёма телефонных обращений, время московское.';
}

/**
 * @param array<string,mixed> $tariff
 * @return array{label:string,note:string,approved:bool,amount:?int}
 */
function tariff_price(array $tariff): array
{
    return service_price([
        'price' => $tariff['price_per_m2'] ?? null,
        'price_approved' => (int)($tariff['is_approved'] ?? 0) === 1,
        'price_unit' => $tariff['price_unit'] ?? $tariff['unit'] ?? 'm2',
    ]);
}

function portfolio_public_works(?string $works): string
{
    $text = trim((string)$works);
    if ($text === '') {
        return '';
    }
    $phrases = [
        'Иллюстративное изображение.',
        'Иллюстрационное фото.',
        'Не является фотографией объекта, выполненного компанией КвартираФормат.',
        'Не является фотографией объекта, выполненного компанией «КвартираФормат».',
        'Демонстрационные материалы показывают формат карточки. Замените их реальными объектами перед публикацией.',
        'Демонстрационные материалы.',
        'Примеры интерьеров, не являющиеся работами компании.',
        'Примеры интерьеров, не являющиеся работами компании',
    ];
    $clean = trim((string)preg_replace('/\s+/u', ' ', str_replace($phrases, '', $text)));
    if ($clean === '' || preg_match('/^[\s\.\,\;\:\-]+$/u', $clean)) {
        return '';
    }
    return $clean;
}

function excerpt(string $text, int $limit = 170): string
{
    $plain = trim(strip_tags($text));
    if (mb_strlen($plain) <= $limit) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $limit - 1)) . '…';
}

function active_class(string $current, string $expected): string
{
    return $current === $expected ? ' is-active' : '';
}

/**
 * @param array{src:string,alt:string,width?:int,height?:int,loading?:string} $image
 */
function picture(array $image, string $class = '', string $loading = ''): string
{
    $loading = $loading !== '' ? $loading : (string)($image['loading'] ?? 'lazy');
    $fetch = $loading === 'eager' ? ' fetchpriority="high"' : '';
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';
    return '<img src="' . e($image['src']) . '" alt="' . e($image['alt']) . '" width="' . (int)($image['width'] ?? 1200) . '" height="' . (int)($image['height'] ?? 840) . '" loading="' . e($loading) . '" decoding="async"' . $fetch . $classAttr . '>';
}
