<?php
use App\Support\SiteImages;
$image = SiteImages::portfolio($item, (int)($index ?? 0));
$headingTag = $headingTag ?? 'h3';
?>
<article class="portfolio-card">
    <div class="media-frame portfolio-photo">
        <?= picture($image) ?>
    </div>
    <<?= $headingTag ?>><?= e($item['title']) ?></<?= $headingTag ?>>
    <?php if (!empty($item['city']) || !empty($item['district'])): ?>
        <p><?= e(trim(($item['city'] ?? '') . ' ' . ($item['district'] ?? ''))) ?></p>
    <?php endif; ?>
    <?php
    $meta = array_filter([
        'Площадь' => $item['area'] ?? '',
        'Вид ремонта' => $item['repair_type'] ?? '',
        'Стоимость' => $item['cost'] ?? '',
        'Срок' => $item['duration'] ?? '',
    ], static fn($value) => trim((string)$value) !== '');
    ?>
    <?php if ($meta): ?>
        <dl>
            <?php foreach ($meta as $label => $value): ?>
                <dt><?= e($label) ?></dt><dd><?= e((string)$value) ?></dd>
            <?php endforeach; ?>
        </dl>
    <?php endif; ?>
    <?php $works = portfolio_public_works($item['works'] ?? ''); if ($works !== ''): ?><p><?= e($works) ?></p><?php endif; ?>
</article>
