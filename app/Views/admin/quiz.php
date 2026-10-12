<?php
use App\Support\QuizCatalog;
$model = QuizCatalog::adminModel($settings ?? []);
$copy = $model['copy'];
$copyFields = [
    'intro_title' => 'Заголовок первого экрана',
    'intro_text' => 'Подзаголовок',
    'start_label' => 'Кнопка старта',
    'intro_note' => 'Подпись под кнопкой',
    'next_label' => 'Кнопка «Далее»',
    'back_label' => 'Кнопка «Назад»',
    'result_title' => 'Заголовок результата',
    'result_note' => 'Пояснение к расчёту',
    'estimate_label' => 'Кнопка подробной сметы',
    'files_label' => 'Кнопка с файлами',
    'file_field_label' => 'Подпись поля файлов',
    'submit_label' => 'Кнопка отправки',
    'contact_title' => 'Заголовок заявки',
];
?>
<section class="admin-panel">
    <h1>Квиз-калькулятор</h1>
    <p>Здесь меняются тексты, порядок и состав вопросов. Суммы квиз берёт из раздела «Калькулятор»: отдельного прайса у квиза нет. Пустой или неутверждённый коэффициент не меняет стоимость. Дополнительные работы без утверждённого коэффициента в сумму не входят.</p>
    <form method="post" class="admin-form quiz-admin">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="action" value="save_quiz">
        <h2>Тексты</h2>
        <div class="grid">
            <?php foreach ($copyFields as $key => $label): ?>
                <label class="<?= in_array($key, ['intro_text', 'result_note'], true) ? 'is-wide' : '' ?>"><?= e($label) ?>
                    <?php if (in_array($key, ['intro_text', 'result_note'], true)): ?>
                        <textarea name="<?= e($key) ?>" rows="3"><?= e($copy[$key] ?? '') ?></textarea>
                    <?php else: ?>
                        <input name="<?= e($key) ?>" value="<?= e($copy[$key] ?? '') ?>">
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
        </div>
        <h2>Вопросы</h2>
        <?php foreach ($model['steps'] as $step): ?>
            <details class="quiz-admin-step">
                <summary><?= e((string)$step['title']) ?> <small><?= e((string)$step['branch'] !== '' ? (string)$step['branch'] : 'старт') ?></small></summary>
                <label>Заголовок вопроса<input name="steps[<?= e((string)$step['id']) ?>][title]" value="<?= e((string)$step['title']) ?>"></label>
                <label>Порядок<input type="number" name="steps[<?= e((string)$step['id']) ?>][sort]" value="<?= (int)$step['sort'] ?>"></label>
                <?php if (!empty($step['lock_enabled'])): ?>
                    <input type="hidden" name="steps[<?= e((string)$step['id']) ?>][enabled]" value="1">
                    <p class="muted">Этот вопрос всегда включён.</p>
                <?php else: ?>
                    <label><input type="checkbox" name="steps[<?= e((string)$step['id']) ?>][enabled]" value="1" <?= !empty($step['enabled']) ? 'checked' : '' ?>> Показывать вопрос</label>
                <?php endif; ?>
                <?php if (!empty($step['lock_required'])): ?>
                    <input type="hidden" name="steps[<?= e((string)$step['id']) ?>][required]" value="1">
                    <p class="muted">Ответ обязателен.</p>
                <?php else: ?>
                    <label><input type="checkbox" name="steps[<?= e((string)$step['id']) ?>][required]" value="1" <?= !empty($step['required']) ? 'checked' : '' ?>> Обязательный вопрос</label>
                <?php endif; ?>
                <?php if ($step['options']): ?>
                    <div class="table-wrap">
                        <table>
                            <tr><th>Вариант</th><th>Подпись</th><th>Изображение</th><th>Порядок</th><th>Вкл.</th><th>Коэффициент</th><th>Утверждён</th></tr>
                            <?php foreach ($step['options'] as $option): ?>
                                <tr>
                                    <td><code><?= e((string)$option['value']) ?></code></td>
                                    <td><input name="steps[<?= e((string)$step['id']) ?>][options][<?= e((string)$option['value']) ?>][label]" value="<?= e((string)$option['label']) ?>"></td>
                                    <td><input name="steps[<?= e((string)$step['id']) ?>][options][<?= e((string)$option['value']) ?>][image]" value="<?= e((string)$option['image']) ?>" placeholder="/assets/..."></td>
                                    <td><input type="number" name="steps[<?= e((string)$step['id']) ?>][options][<?= e((string)$option['value']) ?>][sort]" value="<?= (int)$option['sort'] ?>"></td>
                                    <td><input type="checkbox" name="steps[<?= e((string)$step['id']) ?>][options][<?= e((string)$option['value']) ?>][enabled]" value="1" <?= !empty($option['enabled']) ? 'checked' : '' ?>></td>
                                    <td><input name="steps[<?= e((string)$step['id']) ?>][options][<?= e((string)$option['value']) ?>][coefficient]" value="<?= e((string)$option['coefficient']) ?>" placeholder="1.0"></td>
                                    <td><input type="checkbox" name="steps[<?= e((string)$step['id']) ?>][options][<?= e((string)$option['value']) ?>][coefficient_approved]" value="1" <?= !empty($option['coefficient_approved']) ? 'checked' : '' ?>></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </details>
        <?php endforeach; ?>
        <button class="btn btn-primary" type="submit">Сохранить квиз</button>
    </form>
</section>
