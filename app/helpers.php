<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
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

function format_price(?int $price): string
{
    if (!$price || $price <= 0) {
        return 'По расчёту';
    }
    return number_format($price, 0, ',', ' ') . ' ₽/м²';
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
