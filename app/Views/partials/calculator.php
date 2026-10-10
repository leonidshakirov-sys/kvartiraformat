<?php
use App\Core\Csrf;
use App\Support\CommercialCatalog;
use App\Support\HouseCatalog;
$calcLeadType = $calcLeadType ?? 'apartment';
$lockDirection = $lockDirection ?? false;
$propertyType = $propertyType ?? '';
$propertyTypes = CommercialCatalog::propertyTypes();
$houseTypes = HouseCatalog::houseTypes();
$titles = [
    'apartment' => 'Рассчитайте ориентир стоимости ремонта',
    'house' => 'Получите расчёт ремонта дома',
    'commercial' => 'Получите смету на ремонт помещения',
];
?>
<section class="section calculator section-dark" id="calculator">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Калькулятор</span>
            <h2><?= e($titles[$calcLeadType] ?? $titles['apartment']) ?></h2>
            <p>Сначала выберите направление. Поля меняются под квартиру, дом или коммерческое помещение. На последнем шаге можно прикрепить план, фотографии или техническое задание.</p>
        </div>
        <form class="calc-card" data-calculator data-lead-form data-calc-mode="<?= e($calcLeadType) ?>" action="/lead/submit" method="post" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <input type="hidden" name="form_uid" value="<?= e(bin2hex(random_bytes(12))) ?>">
            <input type="hidden" name="page_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
            <input type="hidden" name="source" value="calculator">
            <input type="hidden" name="lead_type" value="<?= e($calcLeadType) ?>" data-lead-type>
            <input type="hidden" name="utm_source"><input type="hidden" name="utm_medium"><input type="hidden" name="utm_campaign"><input type="hidden" name="utm_content"><input type="hidden" name="utm_term">
            <label class="hp">Сайт <input name="website" tabindex="-1" autocomplete="off"></label>
            <div class="calc-steps">
                <fieldset class="full">
                    <legend>Что необходимо отремонтировать?</legend>
                    <label><input type="radio" name="calc_direction" value="apartment" <?= $calcLeadType === 'apartment' ? 'checked' : '' ?> <?= $lockDirection ? 'disabled' : '' ?>>Квартиру</label>
                    <label><input type="radio" name="calc_direction" value="house" <?= $calcLeadType === 'house' ? 'checked' : '' ?> <?= $lockDirection ? 'disabled' : '' ?>>Частный дом</label>
                    <label><input type="radio" name="calc_direction" value="commercial" <?= $calcLeadType === 'commercial' ? 'checked' : '' ?> <?= $lockDirection ? 'disabled' : '' ?>>Коммерческое помещение</label>
                </fieldset>

                <fieldset data-mode="apartment">
                    <legend>Тип жилья</legend>
                    <label><input type="radio" name="apt_property_type" value="Новостройка" checked>Новостройка</label>
                    <label><input type="radio" name="apt_property_type" value="Вторичка">Вторичка</label>
                    <label><input type="radio" name="apt_property_type" value="Апартаменты">Апартаменты</label>
                </fieldset>
                <fieldset data-mode="apartment">
                    <legend>Вид ремонта</legend>
                    <label><input type="radio" name="apt_repair_type" value="Косметический" checked>Косметический</label>
                    <label><input type="radio" name="apt_repair_type" value="Капитальный">Капитальный</label>
                    <label><input type="radio" name="apt_repair_type" value="Дизайнерский">Дизайнерский</label>
                </fieldset>
                <fieldset data-mode="apartment">
                    <legend>Площадь</legend>
                    <input name="apt_area" inputmode="decimal" placeholder="м²">
                </fieldset>
                <fieldset data-mode="apartment">
                    <legend>Количество комнат</legend>
                    <select name="rooms"><option>Студия</option><option>1</option><option>2</option><option>3</option><option>4+</option></select>
                </fieldset>
                <fieldset data-mode="apartment">
                    <legend>Пакет расчёта</legend>
                    <select name="package_type">
                        <option value="Только работы">Только работы</option>
                        <option value="Работы с черновыми материалами">Работы с черновыми материалами</option>
                        <option value="Комплексный ремонт">Комплексный ремонт</option>
                    </select>
                </fieldset>

                <fieldset data-mode="house">
                    <legend>Тип дома</legend>
                    <?php foreach ($houseTypes as $i => $type): ?>
                        <label><input type="radio" name="house_property_type" value="<?= e($type['value']) ?>" <?= $i === 0 ? 'checked' : '' ?>><?= e($type['label']) ?></label>
                    <?php endforeach; ?>
                </fieldset>
                <fieldset data-mode="house">
                    <legend>Площадь</legend>
                    <input name="house_area" inputmode="decimal" placeholder="м²">
                </fieldset>
                <fieldset data-mode="house">
                    <legend>Количество этажей</legend>
                    <select name="floors"><option>1</option><option>2</option><option>3</option><option>4+</option></select>
                </fieldset>
                <fieldset data-mode="house">
                    <legend>Состояние отделки</legend>
                    <label><input type="radio" name="house_condition" value="Без отделки" checked>Без отделки</label>
                    <label><input type="radio" name="house_condition" value="Частичная отделка">Частичная отделка</label>
                    <label><input type="radio" name="house_condition" value="Требуется обновление">Требуется обновление</label>
                </fieldset>
                <fieldset data-mode="house">
                    <legend>Вид ремонта</legend>
                    <label><input type="radio" name="house_repair_type" value="Косметический" checked>Косметический</label>
                    <label><input type="radio" name="house_repair_type" value="Капитальный">Капитальный</label>
                    <label><input type="radio" name="house_repair_type" value="Под ключ">Под ключ</label>
                </fieldset>

                <fieldset data-mode="commercial">
                    <legend>Назначение</legend>
                    <?php foreach ($propertyTypes as $i => $type): ?>
                        <label><input type="radio" name="com_property_type" value="<?= e($type['value']) ?>" <?= ($propertyType === $type['value'] || ($propertyType === '' && $i === 0)) ? 'checked' : '' ?>><?= e($type['label']) ?></label>
                    <?php endforeach; ?>
                </fieldset>
                <fieldset data-mode="commercial">
                    <legend>Площадь</legend>
                    <input name="com_area" inputmode="decimal" placeholder="м²">
                </fieldset>
                <fieldset data-mode="commercial">
                    <legend>Состояние</legend>
                    <label><input type="radio" name="premise_condition" value="Без отделки" checked>Без отделки</label>
                    <label><input type="radio" name="premise_condition" value="Косметический">Требуется косметический ремонт</label>
                    <label><input type="radio" name="premise_condition" value="Капитальный">Требуется капитальный ремонт</label>
                </fieldset>
                <fieldset data-mode="commercial">
                    <legend>Необходимые работы</legend>
                    <label><input type="checkbox" name="extras[]" value="Отделка стен">Отделка стен</label>
                    <label><input type="checkbox" name="extras[]" value="Полы">Полы</label>
                    <label><input type="checkbox" name="extras[]" value="Потолки">Потолки</label>
                    <label><input type="checkbox" name="extras[]" value="Электромонтаж">Электромонтаж</label>
                    <label><input type="checkbox" name="extras[]" value="Перегородки">Перегородки</label>
                    <label><input type="checkbox" name="extras[]" value="Комплексный ремонт">Комплексный ремонт</label>
                </fieldset>
                <fieldset data-mode="commercial">
                    <legend>Наличие технического задания</legend>
                    <select name="has_project">
                        <option value="Нет">Нет</option>
                        <option value="Есть проект">Есть проект</option>
                        <option value="Есть эскиз или ТЗ">Есть эскиз или ТЗ</option>
                    </select>
                </fieldset>

                <fieldset class="full">
                    <legend>Контакты и файлы</legend>
                    <input required name="name" placeholder="Имя" autocomplete="name">
                    <input required name="phone" inputmode="tel" placeholder="Телефон" autocomplete="tel">
                    <input name="city" placeholder="Город или адрес объекта">
                    <textarea name="comment" rows="3" placeholder="Комментарий к заявке"></textarea>
                    <?php require __DIR__ . '/file-attach.php'; ?>
                </fieldset>
            </div>
            <input type="hidden" name="property_type" data-sync-property>
            <input type="hidden" name="repair_type" data-sync-repair>
            <input type="hidden" name="area" data-sync-area>
            <div class="calc-result">
                <strong data-estimate>Индивидуальный расчёт</strong>
                <p>Для домов цена не считается по квартирному тарифу. Точная смета готовится после осмотра.</p>
                <label class="consent"><input type="checkbox" name="privacy_consent" value="1" required> Согласен(на) на обработку персональных данных.</label>
                <button class="btn btn-primary" type="submit">Получить расчёт</button>
                <p class="form-result" data-form-result></p>
            </div>
        </form>
    </div>
</section>
