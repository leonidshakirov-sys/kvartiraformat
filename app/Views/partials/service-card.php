<?php
$card = $card ?? [];
$cardImage = $cardImage ?? ['src' => $card['image_path'] ?? '', 'alt' => $card['image_alt'] ?? ($card['title'] ?? ''), 'width' => 1600, 'height' => 1000];
$cardHref = $cardHref ?? '#';
$cardCalcHref = $cardCalcHref ?? '#calculator';
$cardPrice = isset($card['price']) ? (int)$card['price'] : null;
?>
<article class="card service-card">
    <div class="media-frame card-image"><?= picture($cardImage) ?></div>
    <div class="card-body">
        <h3><?= e($card['title'] ?? '') ?></h3>
        <p class="card-text"><?= e($card['short'] ?? '') ?></p>
        <div class="card-footer">
            <strong class="card-price"><?= e(format_price($cardPrice)) ?></strong>
            <div class="card-actions">
                <a class="btn btn-small" href="<?= e($cardHref) ?>">Подробнее</a>
                <a class="btn btn-small btn-outline" href="<?= e($cardCalcHref) ?>">Рассчитать</a>
            </div>
        </div>
    </div>
</article>
