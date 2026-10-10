<?php
use App\Core\Csrf;
use App\Support\CommercialCatalog;
$propertyType = $propertyType ?? '';
$propertyTypes = CommercialCatalog::propertyTypes();
?>
<section class="section calculator commercial-calc" id="calculator">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Калькулятор коммерческого ремонта</span>
            <h2>Получите смету на ремонт помещения</h2>
            <p>Стоимость не считается по шаблону. Если тарифы не заданы, отправим заявку на индивидуальный расчёт по площади, состоянию и перечню работ.</p>
        </div>
        <form class="calc-card" data-calculator data-lead-form action="/lead/submit" method="post" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <input type="hidden" name="form_uid" value="<?= e(bin2hex(random_bytes(12))) ?>">
            <input type="hidden" name="page_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
            <input type="hidden" name="source" value="commercial-calculator">
            <input type="hidden" name="lead_type" value="commercial">
            <input type="hidden" name="utm_source"><input type="hidden" name="utm_medium"><input type="hidden" name="utm_campaign"><input type="hidden" name="utm_content"><input type="hidden" name="utm_term">
            <label class="hp">Сайт <input name="website" tabindex="-1" autocomplete="off"></label>
            <div class="calc-steps commercial-steps">
                <fieldset>
                    <legend>1. Какое помещение необходимо отремонтировать?</legend>
                    <?php foreach ($propertyTypes as $i => $type): ?>
                        <label><input type="radio" name="property_type" value="<?= e($type['value']) ?>" <?= ($propertyType === $type['value'] || ($propertyType === '' && $i === 0)) ? 'checked' : '' ?>><?= e($type['label']) ?></label>
                    <?php endforeach; ?>
                </fieldset>
                <fieldset>
                    <legend>2. Площадь помещения</legend>
                    <input required name="area" inputmode="decimal" placeholder="м²">
                </fieldset>
                <fieldset>
                    <legend>3. Текущее состояние</legend>
                    <label><input type="radio" name="premise_condition" value="Без отделки" checked>Без отделки</label>
                    <label><input type="radio" name="premise_condition" value="Косметический">Требуется косметический ремонт</label>
                    <label><input type="radio" name="premise_condition" value="Капитальный">Требуется капитальный ремонт</label>
                    <input type="hidden" name="repair_type" value="Без отделки" data-sync-condition>
                </fieldset>
                <fieldset>
                    <legend>4. Вид работ</legend>
                    <label><input type="checkbox" name="extras[]" value="Отделка стен">Отделка стен</label>
                    <label><input type="checkbox" name="extras[]" value="Полы">Полы</label>
                    <label><input type="checkbox" name="extras[]" value="Потолки">Потолки</label>
                    <label><input type="checkbox" name="extras[]" value="Электромонтаж">Электромонтаж</label>
                    <label><input type="checkbox" name="extras[]" value="Перегородки">Перегородки</label>
                    <label><input type="checkbox" name="extras[]" value="Инженерные системы">Инженерные системы</label>
                    <label><input type="checkbox" name="extras[]" value="Комплексный ремонт">Комплексный ремонт</label>
                </fieldset>
                <fieldset>
                    <legend>5. Наличие проекта</legend>
                    <select name="has_project">
                        <option value="Нет">Нет</option>
                        <option value="Есть проект">Есть проект</option>
                        <option value="Есть эскиз или ТЗ">Есть эскиз или ТЗ</option>
                    </select>
                </fieldset>
                <fieldset>
                    <legend>6. Планируемый срок начала работ</legend>
                    <select name="work_start">
                        <option value="По согласованию">По согласованию</option>
                        <option value="Срочно">Срочно</option>
                        <option value="В течение месяца">В течение месяца</option>
                    </select>
                </fieldset>
                <fieldset>
                    <legend>7. Город или адрес объекта</legend>
                    <input name="city" placeholder="Москва или город МО">
                </fieldset>
                <fieldset>
                    <legend>8–9. Контакты</legend>
                    <input required name="name" placeholder="Имя" autocomplete="name">
                    <input required name="phone" inputmode="tel" placeholder="Телефон" autocomplete="tel">
                </fieldset>
                <fieldset class="full">
                    <legend>10. Комментарий и файлы</legend>
                    <textarea name="comment" rows="3" placeholder="Опишите задачу, ограничения арендодателя или желаемый срок открытия"></textarea>
                    <label>Техническое задание или план помещения
                        <input type="file" name="brief_file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                    </label>
                    <small class="muted">PDF, JPG, PNG, WEBP, DOC до 8 МБ. Файл не публикуется и доступен только в заявке.</small>
                </fieldset>
            </div>
            <div class="calc-result">
                <strong data-estimate><?= e(CommercialCatalog::INDIVIDUAL_ESTIMATE) ?></strong>
                <p>Фиктивную стоимость мы не считаем. Точная смета готовится после осмотра помещения.</p>
                <label class="consent"><input type="checkbox" name="privacy_consent" value="1" required> Согласен(на) на обработку персональных данных.</label>
                <button class="btn btn-primary" type="submit">Получить смету на ремонт помещения</button>
                <p class="form-result" data-form-result></p>
            </div>
        </form>
    </div>
</section>
