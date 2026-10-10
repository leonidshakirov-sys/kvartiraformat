<?php
$maxFiles = $maxFiles ?? 5;
$maxEach = $maxEach ?? 15;
$maxTotal = $maxTotal ?? 50;
$accept = '.jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx';
?>
<div class="file-attach" data-file-attach>
    <label class="file-attach-label">Прикрепить файлы
        <input type="file" name="attachments[]" multiple accept="<?= e($accept) ?>" capture="environment" data-file-input>
    </label>
    <p class="muted">Прикрепите план помещения, фотографии, дизайн-проект, смету или техническое задание. Это поможет точнее рассчитать стоимость работ.</p>
    <p class="muted">До <?= (int)$maxFiles ?> файлов: JPG, JPEG, PNG, WebP, PDF, DOC, DOCX, XLS, XLSX. До <?= (int)$maxEach ?> МБ каждый, всего до <?= (int)$maxTotal ?> МБ. Поле необязательное.</p>
    <ul class="file-list" data-file-list hidden></ul>
    <p class="file-error" data-file-error hidden></p>
</div>
