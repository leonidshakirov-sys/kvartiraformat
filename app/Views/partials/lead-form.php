<?php use App\Core\Csrf; $formId = $formId ?? 'lead-form'; ?>
<form class="lead-form" id="<?= e($formId) ?>" data-lead-form method="post" action="/lead/submit">
    <?= Csrf::field() ?>
    <input type="hidden" name="form_uid" value="<?= e(bin2hex(random_bytes(12))) ?>">
    <input type="hidden" name="page_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
    <input type="hidden" name="source" value="site">
    <input type="hidden" name="utm_source"><input type="hidden" name="utm_medium"><input type="hidden" name="utm_campaign"><input type="hidden" name="utm_content"><input type="hidden" name="utm_term">
    <label class="hp">Сайт <input name="website" tabindex="-1" autocomplete="off"></label>
    <div class="form-grid"><label>Имя<input required name="name" autocomplete="name" placeholder="Ваше имя"></label><label>Телефон<input required name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 ___ ___-__-__"></label><label>Площадь квартиры<input name="area" inputmode="decimal" placeholder="Например, 72"></label><label>Город<input name="city" placeholder="Домодедово"></label></div>
    <label>Комментарий<textarea name="comment" rows="4" placeholder="Кратко опишите квартиру и пожелания"></textarea></label>
    <label class="consent"><input type="checkbox" name="privacy_consent" value="1" required> <span>Согласен(на) на обработку персональных данных по <a href="/personal-data-consent/" target="_blank">согласию</a> и ознакомлен(а) с <a href="/privacy-policy/" target="_blank">политикой</a>.</span></label>
    <button class="btn btn-primary" type="submit">Получить расчёт стоимости</button><p class="form-result" data-form-result></p>
</form>
