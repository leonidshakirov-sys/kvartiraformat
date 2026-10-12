<?php

use App\Core\Csrf;
use App\Support\QuizCatalog;

$quizModel = QuizCatalog::presentation($settings ?? [], [
    'direction' => (string)($calcLeadType ?? ''),
    'lock_direction' => !empty($lockDirection),
    'service_slug' => (string)($calcServiceSlug ?? ''),
    'property_type' => (string)($propertyType ?? ''),
]);
$quizCopy = $quizModel['copy'];
$quizPreset = $quizModel['preset'];
$quizChecked = static function (string $stepId, string $value) use ($quizPreset): bool {
    if ($stepId === 'direction') {
        return (string)($quizPreset['direction'] ?? '') === $value;
    }
    return (string)($quizPreset['answers'][$stepId] ?? '') === $value;
};
?>
<section class="section quiz-section" id="calculator" data-quiz>
    <div class="container">
        <div class="quiz-card">
            <div class="quiz-progress" data-quiz-progress hidden>
                <span data-quiz-count>Шаг 1 из 1</span>
                <div class="quiz-bar" data-quiz-progressbar role="progressbar" aria-valuemin="1" aria-valuemax="1" aria-valuenow="1" aria-label="Прогресс расчёта">
                    <span data-quiz-bar></span>
                </div>
            </div>

            <div data-quiz-screen="intro">
                <h2 tabindex="-1"><?= e($quizCopy['intro_title']) ?></h2>
                <p><?= e($quizCopy['intro_text']) ?></p>
                <button class="btn btn-primary" type="button" data-quiz-start><?= e($quizCopy['start_label']) ?></button>
                <p class="quiz-note"><?= e($quizCopy['intro_note']) ?></p>
                <noscript><p>Чтобы пройти расчёт, включите JavaScript в браузере.</p></noscript>
            </div>

            <form data-quiz-form action="/lead/submit" method="post" enctype="multipart/form-data" hidden>
                <?= Csrf::field() ?>
                <input type="hidden" name="form_uid" value="<?= e(bin2hex(random_bytes(12))) ?>">
                <input type="hidden" name="page_url" value="<?= e($_SERVER['REQUEST_URI'] ?? '/') ?>">
                <input type="hidden" name="source" value="quiz">
                <input type="hidden" name="utm_source"><input type="hidden" name="utm_medium"><input type="hidden" name="utm_campaign"><input type="hidden" name="utm_content"><input type="hidden" name="utm_term">
                <?php if (!empty($quizPreset['service_slug'])): ?>
                    <input type="hidden" name="service_slug" value="<?= e((string)$quizPreset['service_slug']) ?>">
                <?php endif; ?>
                <label class="hp">Сайт <input name="website" tabindex="-1" autocomplete="off"></label>

                <?php foreach ($quizModel['steps'] as $step):
                    if (empty($step['enabled'])) {
                        continue;
                    }
                    $stepId = (string)$step['id'];
                    $skip = $stepId === 'direction' && !empty($quizPreset['lock']);
                    $hasImage = false;
                    foreach ($step['options'] as $option) {
                        if (!empty($option['image'])) {
                            $hasImage = true;
                            break;
                        }
                    }
                    $count = count($step['options']);
                    $cols = 'cols-1';
                    if ($stepId === 'house_floors') {
                        $cols = 'cols-4';
                    } elseif ($hasImage && $count === 3) {
                        $cols = 'cols-3';
                    } elseif ($count > 1) {
                        $cols = 'cols-2';
                    }
                    ?>
                    <fieldset class="quiz-step" data-quiz-step data-step-id="<?= e($stepId) ?>" data-branch="<?= e((string)$step['branch']) ?>" data-type="<?= e((string)$step['type']) ?>" data-required="<?= !empty($step['required']) ? '1' : '0' ?>" data-enabled="<?= !empty($step['enabled']) ? '1' : '0' ?>" data-max="<?= (int)$step['max'] ?>" <?= $skip ? 'data-skip="1"' : '' ?> hidden tabindex="-1">
                        <legend><?= e((string)$step['title']) ?></legend>
                        <?php if ($step['type'] === 'area'): ?>
                            <div class="quiz-area">
                                <button class="quiz-stepper" type="button" data-area-dec aria-label="Уменьшить площадь">−</button>
                                <label class="quiz-area-field">Площадь, м²
                                    <input name="quiz[<?= e($stepId) ?>]" inputmode="decimal" autocomplete="off" enterkeyhint="done" aria-describedby="quiz-error">
                                </label>
                                <button class="quiz-stepper" type="button" data-area-inc aria-label="Увеличить площадь">+</button>
                            </div>
                            <p class="muted">От 1 до <?= (int)$step['max'] ?> м².</p>
                        <?php else: ?>
                            <div class="quiz-options <?= e($cols) ?>">
                                <?php foreach ($step['options'] as $option): ?>
                                    <label class="quiz-option<?= empty($option['image']) ? ' is-compact' : '' ?>">
                                        <input type="<?= $step['type'] === 'multi' ? 'checkbox' : 'radio' ?>" name="quiz[<?= e($stepId) ?>]<?= $step['type'] === 'multi' ? '[]' : '' ?>" value="<?= e((string)$option['value']) ?>" <?= !empty($option['exclusive']) ? 'data-exclusive="1"' : '' ?> <?= $quizChecked($stepId, (string)$option['value']) ? 'checked' : '' ?>>
                                        <?php if (!empty($option['image'])): ?>
                                            <img src="<?= e((string)$option['image']) ?>" alt="" width="640" height="360" loading="lazy" decoding="async">
                                        <?php endif; ?>
                                        <span class="quiz-option-label"><?= e((string)$option['label']) ?></span>
                                        <span class="quiz-option-mark" aria-hidden="true"></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </fieldset>
                <?php endforeach; ?>

                <p class="quiz-error" id="quiz-error" data-quiz-error role="alert" hidden></p>
                <div class="quiz-nav" data-quiz-stepnav>
                    <button class="btn btn-outline" type="button" data-quiz-back><?= e($quizCopy['back_label']) ?></button>
                    <button class="btn btn-primary" type="button" data-quiz-next><?= e($quizCopy['next_label']) ?></button>
                </div>

                <div data-quiz-screen="result" hidden>
                    <h2 tabindex="-1"><?= e($quizCopy['result_title']) ?></h2>
                    <dl class="quiz-result">
                        <div><dt>Тип объекта</dt><dd data-quote-object></dd></div>
                        <div data-quote-detail-row hidden><dt data-quote-detail-caption></dt><dd data-quote-detail></dd></div>
                        <div data-quote-repair-row><dt>Вид ремонта</dt><dd data-quote-repair></dd></div>
                        <div data-quote-area-row><dt>Площадь</dt><dd data-quote-area></dd></div>
                        <div data-quote-price-row><dt>Цена</dt><dd data-quote-price></dd></div>
                        <div class="quiz-total"><dt>Предварительная стоимость</dt><dd data-quote-total></dd></div>
                    </dl>
                    <p class="quiz-basis" data-quote-basis hidden></p>
                    <p class="quiz-aside" data-quote-aside hidden></p>
                    <p class="quiz-note" data-quote-note></p>
                    <div class="quiz-nav quiz-nav-stack">
                        <button class="btn btn-primary" type="button" data-quiz-estimate><?= e($quizCopy['estimate_label']) ?></button>
                        <button class="btn btn-outline" type="button" data-quiz-files><?= e($quizCopy['files_label']) ?></button>
                    </div>
                </div>

                <div data-quiz-screen="contact" hidden>
                    <h2 tabindex="-1"><?= e($quizCopy['contact_title']) ?></h2>
                    <p class="muted">Ответы квиза уже прикреплены к заявке. Повторно выбирать тип ремонта и площадь не нужно.</p>
                    <div class="quiz-fields">
                        <label>Имя<input required name="name" autocomplete="name" placeholder="Ваше имя"></label>
                        <label>Телефон<input required name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 ___ ___-__-__"></label>
                        <label class="is-wide">Город или адрес объекта<input name="city" autocomplete="address-level2" placeholder="Москва"></label>
                        <label class="is-wide">Комментарий<textarea name="comment" rows="3" placeholder="Что важно учесть при расчёте"></textarea></label>
                    </div>
                    <?php $fileLabel = $quizCopy['file_field_label']; require __DIR__ . '/file-attach.php'; ?>
                    <label class="consent"><input type="checkbox" name="privacy_consent" value="1" required> <span>Согласен(на) на обработку персональных данных по <a href="/personal-data-consent/" target="_blank">согласию</a> и ознакомлен(а) с <a href="/privacy-policy/" target="_blank">политикой</a>.</span></label>
                    <div class="quiz-nav">
                        <button class="btn btn-outline" type="button" data-quiz-back><?= e($quizCopy['back_label']) ?></button>
                        <button class="btn btn-primary" type="submit"><?= e($quizCopy['submit_label']) ?></button>
                    </div>
                    <p class="form-result" data-form-result></p>
                </div>
            </form>
        </div>
    </div>
</section>
<script defer src="<?= asset('js/quiz.js') ?>"></script>
