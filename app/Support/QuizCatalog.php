<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Quiz copy, questions and the server-side quote.
 * Prices are never stored here: each priced answer points at a repair_tariffs slug.
 */
final class QuizCatalog
{
    public const RESULT_NOTE = 'Расчёт выполнен по базовому тарифу на работы без материалов. Дополнительные работы и особенности объекта могут изменить итоговую стоимость.';

    /**
     * @param array<string,mixed> $settings
     * @param array<string,mixed> $context
     * @return array{copy:array<string,string>,steps:list<array<string,mixed>>,preset:array<string,mixed>}
     */
    public static function presentation(array $settings, array $context = []): array
    {
        $model = self::merge($settings);
        $steps = [];
        foreach ($model['steps'] as $step) {
            $publicOptions = [];
            foreach ($step['options'] as $option) {
                if (empty($option['enabled'])) {
                    continue;
                }
                $publicOptions[] = [
                    'value' => $option['value'],
                    'label' => $option['label'],
                    'image' => $option['image'],
                    'exclusive' => !empty($option['exclusive']),
                    'sort' => (int)$option['sort'],
                ];
            }
            $step['options'] = $publicOptions;
            $steps[] = $step;
        }
        $steps = self::ordered($steps);
        return [
            'copy' => $model['copy'],
            'steps' => $steps,
            'preset' => self::preset($context),
        ];
    }

    /**
     * @param array<string,mixed> $settings
     * @return array{copy:array<string,string>,steps:list<array<string,mixed>>}
     */
    public static function adminModel(array $settings): array
    {
        $model = self::merge($settings);
        $model['steps'] = self::ordered($model['steps']);
        return $model;
    }

    /**
     * @param array<string,mixed> $post
     */
    public static function configFromPost(array $post): array
    {
        $defaults = self::defaults();
        $copy = [];
        foreach ($defaults['copy'] as $key => $default) {
            $value = trim((string)($post[$key] ?? ''));
            $copy[$key] = $value !== '' ? mb_substr($value, 0, 500) : $default;
        }

        $postedSteps = is_array($post['steps'] ?? null) ? $post['steps'] : [];
        $steps = [];
        foreach ($defaults['steps'] as $step) {
            $row = is_array($postedSteps[$step['id']] ?? null) ? $postedSteps[$step['id']] : [];
            $title = trim((string)($row['title'] ?? ''));
            $enabled = !empty($step['lock_enabled']) || !empty($row['enabled']);
            $required = !empty($step['lock_required']) || !empty($row['required']);
            $sort = (int)($row['sort'] ?? $step['sort']);
            $postedOptions = is_array($row['options'] ?? null) ? $row['options'] : [];
            $options = [];
            $enabledOptions = 0;
            foreach ($step['options'] as $option) {
                $opt = is_array($postedOptions[$option['value']] ?? null) ? $postedOptions[$option['value']] : [];
                $label = trim((string)($opt['label'] ?? ''));
                $image = trim((string)($opt['image'] ?? ''));
                if ($image !== '' && !self::validImage($image)) {
                    throw new RuntimeException('Изображение «' . $label . '» должно быть путём внутри /assets или /uploads.');
                }
                $coefficient = trim(str_replace(',', '.', (string)($opt['coefficient'] ?? '')));
                $approved = !empty($opt['coefficient_approved']);
                if ($approved && ($coefficient === '' || !is_numeric($coefficient) || (float)$coefficient < 0.5 || (float)$coefficient > 3)) {
                    throw new RuntimeException('Утверждённый коэффициент для «' . ($label !== '' ? $label : $option['label']) . '» должен быть числом от 0,5 до 3. Иначе снимите утверждение.');
                }
                if ($coefficient !== '' && (!is_numeric($coefficient) || (float)$coefficient < 0.5 || (float)$coefficient > 3)) {
                    throw new RuntimeException('Коэффициент для «' . ($label !== '' ? $label : $option['label']) . '» укажите от 0,5 до 3 или оставьте поле пустым.');
                }
                $optionEnabled = !empty($opt['enabled']);
                if ($optionEnabled) {
                    $enabledOptions++;
                }
                $options[] = [
                    'value' => $option['value'],
                    'label' => $label !== '' ? mb_substr($label, 0, 160) : $option['label'],
                    'image' => $image !== '' ? $image : $option['image'],
                    'tariff_slug' => $option['tariff_slug'],
                    'exclusive' => !empty($option['exclusive']),
                    'sort' => (int)($opt['sort'] ?? $option['sort']),
                    'enabled' => $optionEnabled,
                    'coefficient' => $coefficient,
                    'coefficient_approved' => $approved,
                ];
            }
            if ($enabled && $step['type'] !== 'area' && $enabledOptions === 0) {
                throw new RuntimeException('В вопросе «' . ($title !== '' ? $title : $step['title']) . '» оставьте хотя бы один вариант ответа.');
            }
            $steps[] = [
                'id' => $step['id'],
                'branch' => $step['branch'],
                'title' => $title !== '' ? mb_substr($title, 0, 180) : $step['title'],
                'type' => $step['type'],
                'required' => $required,
                'enabled' => $enabled,
                'lock_enabled' => !empty($step['lock_enabled']),
                'lock_required' => !empty($step['lock_required']),
                'sort' => $sort,
                'max' => $step['max'],
                'options' => $options,
            ];
        }

        return ['copy' => $copy, 'steps' => $steps];
    }

    /**
     * @param array<string,mixed> $post
     * @param array<string,list<array<string,mixed>>> $tariffsByCategory
     * @param array<string,mixed> $settings
     * @return array<string,mixed>
     */
    public static function quote(array $post, array $tariffsByCategory, array $settings = []): array
    {
        $model = self::merge($settings);
        $answers = self::collect($post, $model);
        $errors = self::validateAnswers($answers, $model);
        if ($errors) {
            return [
                'ok' => false,
                'errors' => $errors,
                'individual' => true,
                'summary' => 'Индивидуальный расчёт',
            ];
        }

        $direction = (string)$answers['direction'];
        $tariffs = $tariffsByCategory[$direction] ?? [];
        $selected = $answers['selected'];
        $tariff = self::resolveTariff($direction, $selected, $model, $tariffs, (string)$answers['service_slug']);
        $labels = self::labels($model, $selected, $direction);
        $area = $answers['area'];
        $factor = self::factor($model, $selected);
        $individual = $tariff === null;
        $unit = (string)($tariff['price_unit'] ?? $tariff['unit'] ?? 'm2');
        $price = $individual ? 0 : (int)($tariff['price_per_m2'] ?? 0);
        $total = null;
        if (!$individual && $price > 0) {
            if ($unit === 'room') {
                $total = (int)round($price * $factor['value']);
            } elseif ($area !== null && $area > 0) {
                $total = (int)round($area * $price * $factor['value']);
            } else {
                $individual = true;
            }
        }

        $summary = 'Индивидуальный расчёт';
        $priceLabel = '';
        $totalLabel = '';
        if (!$individual && $total !== null) {
            $priceLabel = format_price($price, true, $unit);
            $totalLabel = 'от ' . number_format($total, 0, ',', ' ') . ' ₽';
            $summary = 'Предварительно от ' . number_format($total, 0, ',', ' ') . ' ₽';
            if ($unit === 'room') {
                $summary .= ' за помещение';
                $totalLabel .= ' за помещение';
            }
        }

        $note = $individual
            ? 'Для выбранного состава работ нет отдельного утверждённого тарифа. Оставьте контакты — подготовим индивидуальный расчёт. Точную смету можно назвать только после уточнения объекта.'
            : (string)$model['copy']['result_note'];
        $basis = '';
        if (!$individual && $direction === 'commercial') {
            $basis = 'Отдельной утверждённой цены для выбранного вида ремонта нет. Показана базовая ставка для этого типа помещения.';
        } elseif (!$individual && $unit === 'room') {
            $basis = 'Расчёт по утверждённому тарифу за помещение, без умножения на площадь.';
        }

        $object = $labels['object'];
        $detail = $labels['detail'];
        $detailCaption = $labels['detail_caption'];
        if ($direction === 'commercial' && $detail !== '') {
            $object = $detail;
            $labels['lines']['Тип объекта'] = $detail;
            $detail = '';
            $detailCaption = '';
        }
        if (!$individual && $unit === 'room') {
            $repairName = trim((string)($tariff['repair_type'] ?? ''));
            if ($repairName !== '') {
                $labels['repair'] = $repairName;
                foreach ($labels['lines'] as $lineKey => $lineValue) {
                    if (mb_stripos((string)$lineKey, 'вид ремонта') !== false) {
                        $labels['lines'][$lineKey] = $repairName;
                    }
                }
            }
        }
        if ($area !== null) {
            $labels['lines']['Площадь'] = self::formatArea($area) . ' м²';
        }

        return [
            'ok' => true,
            'errors' => [],
            'individual' => $individual,
            'direction' => $direction,
            'object_label' => $object,
            'detail_caption' => $detailCaption,
            'detail_label' => $detail,
            'repair_label' => $labels['repair'],
            'condition_label' => $labels['condition'],
            'extras_label' => $labels['extras'],
            'extras_list' => $labels['extras_list'],
            'floors_label' => $labels['floors'],
            'project_label' => $labels['project'],
            'property_type' => trim($object . ($detail !== '' ? ' — ' . $detail : '')),
            'area' => $area,
            'area_label' => $area !== null ? self::formatArea($area) . ' м²' : '',
            'unit' => $individual ? '' : $unit,
            'price' => $individual ? null : $price,
            'price_label' => $priceLabel,
            'total' => $total,
            'total_label' => $totalLabel,
            'summary' => $summary,
            'tariff_slug' => $individual ? '' : (string)($tariff['service_slug'] ?? $tariff['slug'] ?? ''),
            'service_slug' => (string)$answers['service_slug'],
            'note' => $note,
            'basis' => $basis,
            'aside' => $labels['aside'],
            'coefficient_applied' => $factor['applied'],
            'lines' => $labels['lines'],
            'package_type' => 'Только работы',
        ];
    }

    /**
     * @param array<string,mixed> $settings
     * @return array{copy:array<string,string>,steps:list<array<string,mixed>>}
     */
    private static function merge(array $settings): array
    {
        $defaults = self::defaults();
        $saved = self::decode($settings['quiz_config'] ?? null);
        $copy = $defaults['copy'];
        $savedCopy = is_array($saved['copy'] ?? null) ? $saved['copy'] : [];
        foreach ($copy as $key => $value) {
            $custom = trim((string)($savedCopy[$key] ?? ''));
            if ($custom !== '') {
                $copy[$key] = $custom;
            }
        }

        $savedSteps = [];
        foreach ((array)($saved['steps'] ?? []) as $step) {
            if (is_array($step) && isset($step['id'])) {
                $savedSteps[(string)$step['id']] = $step;
            }
        }

        $steps = [];
        foreach ($defaults['steps'] as $step) {
            $custom = $savedSteps[$step['id']] ?? [];
            $savedOptions = [];
            foreach ((array)($custom['options'] ?? []) as $option) {
                if (is_array($option) && isset($option['value'])) {
                    $savedOptions[(string)$option['value']] = $option;
                }
            }
            $options = [];
            foreach ($step['options'] as $option) {
                $over = $savedOptions[$option['value']] ?? [];
                $label = trim((string)($over['label'] ?? ''));
                $image = trim((string)($over['image'] ?? ''));
                $coefficient = trim((string)($over['coefficient'] ?? ''));
                $options[] = [
                    'value' => $option['value'],
                    'label' => $label !== '' ? $label : $option['label'],
                    'image' => ($image !== '' && self::validImage($image)) ? $image : $option['image'],
                    'tariff_slug' => $option['tariff_slug'],
                    'exclusive' => !empty($option['exclusive']),
                    'sort' => isset($over['sort']) ? (int)$over['sort'] : (int)$option['sort'],
                    'enabled' => array_key_exists('enabled', $over) ? !empty($over['enabled']) : !empty($option['enabled']),
                    'coefficient' => $coefficient,
                    'coefficient_approved' => !empty($over['coefficient_approved']),
                ];
            }
            usort($options, static fn(array $a, array $b): int => ((int)$a['sort'] <=> (int)$b['sort']) ?: strcmp((string)$a['value'], (string)$b['value']));
            $steps[] = [
                'id' => $step['id'],
                'branch' => $step['branch'],
                'title' => trim((string)($custom['title'] ?? '')) !== '' ? trim((string)$custom['title']) : $step['title'],
                'type' => $step['type'],
                'required' => !empty($step['lock_required']) || (array_key_exists('required', $custom) ? !empty($custom['required']) : !empty($step['required'])),
                'enabled' => !empty($step['lock_enabled']) || (array_key_exists('enabled', $custom) ? !empty($custom['enabled']) : !empty($step['enabled'])),
                'lock_enabled' => !empty($step['lock_enabled']),
                'lock_required' => !empty($step['lock_required']),
                'sort' => isset($custom['sort']) ? (int)$custom['sort'] : (int)$step['sort'],
                'max' => (int)$step['max'],
                'options' => $options,
            ];
        }

        return ['copy' => $copy, 'steps' => self::ordered($steps)];
    }

    /**
     * @param array<string,mixed> $context
     * @return array{direction:string,lock:bool,service_slug:string,answers:array<string,string>}
     */
    private static function preset(array $context): array
    {
        $direction = (string)($context['direction'] ?? '');
        if (!in_array($direction, ['apartment', 'house', 'commercial'], true)) {
            $direction = '';
        }
        $lock = !empty($context['lock_direction']) && $direction !== '';
        if (!$lock) {
            $direction = '';
        }
        $slug = trim((string)($context['service_slug'] ?? ''));
        $property = trim((string)($context['property_type'] ?? ''));
        $answers = [];
        $serviceSlug = '';

        $repair = [
            'kosmeticheskiy-remont' => ['apartment', 'apt_repair', 'cosmetic'],
            'kapitalnyy-remont' => ['apartment', 'apt_repair', 'capital'],
            'remont-kvartir-pod-klyuch' => ['apartment', 'apt_repair', 'turnkey'],
            'dizaynerskiy-remont' => ['apartment', 'apt_repair', 'designer'],
            'remont-novostroek' => ['apartment', 'apt_property', 'newbuild'],
            'remont-vtorichnogo-zhilya' => ['apartment', 'apt_property', 'secondary'],
            'remont-studiy' => ['apartment', 'apt_property', 'studio'],
            'kosmeticheskiy' => ['house', 'house_repair', 'cosmetic'],
            'kapitalnyy' => ['house', 'house_repair', 'capital'],
            'pod-klyuch' => ['house', 'house_repair', 'turnkey'],
            'posle-stroitelstva' => ['house', 'house_repair', 'after_build'],
            'otdelka-kottedzhey' => ['house', 'house_property', 'cottage'],
            'taunkhausov' => ['house', 'house_property', 'townhouse'],
            'zagorodnyh' => ['house', 'house_property', 'country'],
            'remont-ofisov' => ['commercial', 'com_purpose', 'office'],
            'remont-magazinov' => ['commercial', 'com_purpose', 'store'],
            'remont-salonov-krasoty' => ['commercial', 'com_purpose', 'salon'],
            'remont-kafe-restoranov' => ['commercial', 'com_purpose', 'cafe'],
            'remont-skladskih-pomescheniy' => ['commercial', 'com_purpose', 'warehouse'],
            'remont-pomescheniy-svobodnogo-naznacheniya' => ['commercial', 'com_purpose', 'free'],
        ];
        if ($lock && isset($repair[$slug]) && $repair[$slug][0] === $direction) {
            $answers[$repair[$slug][1]] = $repair[$slug][2];
        }
        if ($lock && $slug === 'remont-vannoy-i-sanuzla' && $direction === 'apartment') {
            $serviceSlug = $slug;
        }
        if ($lock && $slug === 'sanuzlov' && $direction === 'house') {
            $serviceSlug = $slug;
        }
        $purpose = [
            'Офис' => 'office',
            'Магазин' => 'store',
            'Салон красоты' => 'salon',
            'Кафе или ресторан' => 'cafe',
            'Склад' => 'warehouse',
            'Помещение свободного назначения' => 'free',
        ];
        if ($lock && $direction === 'commercial' && isset($purpose[$property])) {
            $answers['com_purpose'] = $purpose[$property];
        }

        return [
            'direction' => $direction,
            'lock' => $lock,
            'service_slug' => $serviceSlug,
            'answers' => $answers,
        ];
    }

    /**
     * @param array{copy:array<string,string>,steps:list<array<string,mixed>>} $model
     * @param array<string,mixed> $post
     * @return array{direction:string,service_slug:string,area:?float,selected:array<string,list<string>>}
     */
    private static function collect(array $post, array $model): array
    {
        $quiz = is_array($post['quiz'] ?? null) ? $post['quiz'] : [];
        $selected = [];
        foreach ($model['steps'] as $step) {
            if (empty($step['enabled'])) {
                continue;
            }
            $raw = $quiz[$step['id']] ?? null;
            $values = [];
            if ($step['type'] === 'area') {
                $text = trim(str_replace(',', '.', (string)$raw));
                if ($text !== '') {
                    $values[] = $text;
                }
            } else {
                $items = is_array($raw) ? $raw : [$raw];
                foreach ($items as $item) {
                    $value = trim((string)$item);
                    if ($value !== '' && self::option($step, $value)) {
                        $values[] = $value;
                    }
                }
                $values = array_values(array_unique($values));
                if ($step['type'] === 'single') {
                    $values = array_slice($values, 0, 1);
                }
                if ($step['type'] === 'multi' && in_array('none', $values, true)) {
                    $values = ['none'];
                }
            }
            if ($values) {
                $selected[$step['id']] = $values;
            }
        }

        $direction = $selected['direction'][0] ?? '';
        $serviceSlug = trim((string)($post['service_slug'] ?? ''));
        $allowedService = [
            'remont-vannoy-i-sanuzla' => 'apartment',
            'sanuzlov' => 'house',
        ];
        if (!isset($allowedService[$serviceSlug]) || $allowedService[$serviceSlug] !== $direction) {
            $serviceSlug = '';
        }

        $area = null;
        $areaStep = match ($direction) {
            'house' => 'house_area',
            'commercial' => 'com_area',
            'apartment' => 'apt_area',
            default => '',
        };
        if ($areaStep !== '' && isset($selected[$areaStep][0]) && is_numeric($selected[$areaStep][0])) {
            $area = round((float)$selected[$areaStep][0], 1);
        }

        return [
            'direction' => $direction,
            'service_slug' => $serviceSlug,
            'area' => $area,
            'selected' => $selected,
        ];
    }

    /**
     * @param array{direction:string,service_slug:string,area:?float,selected:array<string,list<string>>} $answers
     * @param array{copy:array<string,string>,steps:list<array<string,mixed>>} $model
     * @return list<string>
     */
    private static function validateAnswers(array $answers, array $model): array
    {
        $errors = [];
        $direction = $answers['direction'];
        if (!in_array($direction, ['apartment', 'house', 'commercial'], true)) {
            return ['Выберите, что планируете ремонтировать.'];
        }
        foreach ($model['steps'] as $step) {
            if (empty($step['enabled'])) {
                continue;
            }
            $branch = (string)$step['branch'];
            if ($branch !== '' && $branch !== $direction) {
                continue;
            }
            $values = $answers['selected'][$step['id']] ?? [];
            if ($step['type'] === 'area') {
                $max = (int)$step['max'];
                $area = $answers['area'];
                if (!empty($step['required']) && ($area === null || $area <= 0)) {
                    $errors[] = 'Укажите площадь.';
                } elseif ($area !== null && ($area <= 0 || $area > $max)) {
                    $errors[] = 'Проверьте площадь: от 1 до ' . $max . ' м².';
                }
                continue;
            }
            if (!empty($step['required']) && $values === []) {
                $errors[] = 'Ответьте на вопрос «' . $step['title'] . '».';
            }
        }
        return array_values(array_unique($errors));
    }

    /**
     * @param array<string,list<string>> $selected
     * @param list<array<string,mixed>> $tariffs
     * @param array{copy:array<string,string>,steps:list<array<string,mixed>>} $model
     * @return array<string,mixed>|null
     */
    private static function resolveTariff(string $direction, array $selected, array $model, array $tariffs, string $serviceSlug): ?array
    {
        if ($serviceSlug !== '') {
            $room = self::approvedTariff($tariffs, $serviceSlug);
            if ($room && (string)($room['price_unit'] ?? $room['unit'] ?? '') === 'room') {
                return $room;
            }
        }

        if ($direction === 'commercial') {
            $repairSlug = self::tariffSlug($model, 'com_repair', $selected['com_repair'][0] ?? '');
            if ($repairSlug !== '') {
                $repair = self::approvedTariff($tariffs, $repairSlug);
                if ($repair) {
                    return $repair;
                }
            }
            $purposeSlug = self::tariffSlug($model, 'com_purpose', $selected['com_purpose'][0] ?? '');
            return $purposeSlug !== '' ? self::approvedTariff($tariffs, $purposeSlug) : null;
        }

        $stepId = $direction === 'house' ? 'house_repair' : 'apt_repair';
        $slug = self::tariffSlug($model, $stepId, $selected[$stepId][0] ?? '');
        return $slug !== '' ? self::approvedTariff($tariffs, $slug) : null;
    }

    /**
     * @param list<array<string,mixed>> $tariffs
     * @return array<string,mixed>|null
     */
    private static function approvedTariff(array $tariffs, string $slug): ?array
    {
        foreach ($tariffs as $tariff) {
            $tariffSlug = (string)($tariff['service_slug'] ?? $tariff['slug'] ?? '');
            if ($tariffSlug !== $slug) {
                continue;
            }
            if ((int)($tariff['is_active'] ?? 1) !== 1 || (int)($tariff['is_approved'] ?? 0) !== 1) {
                return null;
            }
            $price = (int)($tariff['price_per_m2'] ?? 0);
            if ($price <= 0) {
                return null;
            }
            return $tariff;
        }
        return null;
    }

    /**
     * @param array{copy:array<string,string>,steps:list<array<string,mixed>>} $model
     * @param array<string,list<string>> $selected
     * @return array{value:float,applied:bool}
     */
    private static function factor(array $model, array $selected): array
    {
        $factor = 1.0;
        $applied = false;
        foreach ($model['steps'] as $step) {
            foreach ($selected[$step['id']] ?? [] as $value) {
                $option = self::option($step, $value);
                if (!$option || empty($option['coefficient_approved'])) {
                    continue;
                }
                $coefficient = (float)str_replace(',', '.', (string)$option['coefficient']);
                if ($coefficient >= 0.5 && $coefficient <= 3) {
                    $factor *= $coefficient;
                    $applied = true;
                }
            }
        }
        return ['value' => $factor, 'applied' => $applied];
    }

    /**
     * @param array{copy:array<string,string>,steps:list<array<string,mixed>>} $model
     * @param array<string,list<string>> $selected
     * @return array{object:string,detail:string,detail_caption:string,repair:string,condition:string,extras:string,extras_list:list<string>,floors:string,project:string,aside:string,lines:array<string,string>}
     */
    private static function labels(array $model, array $selected, string $direction): array
    {
        $text = static function (array $model, array $selected, string $stepId) : string {
            $step = null;
            foreach ($model['steps'] as $item) {
                if ($item['id'] === $stepId) {
                    $step = $item;
                    break;
                }
            }
            if (!$step) {
                return '';
            }
            $labels = [];
            foreach ($selected[$stepId] ?? [] as $value) {
                $option = self::option($step, $value);
                if ($option) {
                    $labels[] = (string)$option['label'];
                }
            }
            return implode(', ', $labels);
        };

        $directionLabel = match ($direction) {
            'house' => 'Частный дом',
            'commercial' => 'Коммерческое помещение',
            default => 'Квартира',
        };
        $detailCaption = match ($direction) {
            'house' => 'Тип дома',
            'apartment' => 'Тип недвижимости',
            default => '',
        };
        $detailStep = match ($direction) {
            'house' => 'house_property',
            'apartment' => 'apt_property',
            'commercial' => 'com_purpose',
            default => '',
        };
        $repairStep = match ($direction) {
            'house' => 'house_repair',
            'commercial' => 'com_repair',
            default => 'apt_repair',
        };
        $conditionStep = match ($direction) {
            'house' => 'house_condition',
            'commercial' => 'com_condition',
            default => 'apt_condition',
        };
        $extrasStep = match ($direction) {
            'house' => 'house_extras',
            'commercial' => 'com_extras',
            default => 'apt_extras',
        };
        $detail = $detailStep !== '' ? $text($model, $selected, $detailStep) : '';
        $repair = $text($model, $selected, $repairStep);
        $condition = $text($model, $selected, $conditionStep);
        $extras = $text($model, $selected, $extrasStep);
        $floors = $text($model, $selected, 'house_floors');
        $project = $text($model, $selected, 'com_project');
        $none = $extras === 'Ничего дополнительного';
        $extrasList = [];
        if ($extras !== '' && !$none) {
            $extrasList = array_map('trim', explode(',', $extras));
        }

        $lines = ['Тип объекта' => $directionLabel];
        foreach ($model['steps'] as $step) {
            $branch = (string)$step['branch'];
            if ($branch !== '' && $branch !== $direction) {
                continue;
            }
            if ($step['id'] === 'direction' || $step['type'] === 'area') {
                continue;
            }
            $value = $text($model, $selected, (string)$step['id']);
            if ($value !== '') {
                $lines[(string)$step['title']] = $value;
            }
        }

        $asideParts = [];
        if ($condition !== '') {
            $asideParts[] = 'Состояние: ' . $condition . '.';
        }
        if ($floors !== '') {
            $asideParts[] = 'Этажей: ' . $floors . '.';
        }
        if ($project !== '') {
            $asideParts[] = 'Проект: ' . $project . '.';
        }
        if ($none) {
            $asideParts[] = 'Дополнительные работы не выбраны.';
        } elseif ($extras !== '') {
            $asideParts[] = 'Дополнительные работы: ' . $extras . '. В сумму они не входят.';
        }

        return [
            'object' => $directionLabel,
            'detail' => $detail,
            'detail_caption' => $detail !== '' ? $detailCaption : '',
            'repair' => $repair,
            'condition' => $condition,
            'extras' => $none ? 'Ничего дополнительного' : $extras,
            'extras_list' => $none ? ['Ничего дополнительного'] : $extrasList,
            'floors' => $floors,
            'project' => $project,
            'aside' => implode(' ', $asideParts),
            'lines' => $lines,
        ];
    }

    /**
     * @param array<string,mixed> $step
     * @return array<string,mixed>|null
     */
    private static function option(array $step, string $value): ?array
    {
        foreach ($step['options'] as $option) {
            if ((string)$option['value'] === $value && !empty($option['enabled'])) {
                return $option;
            }
        }
        return null;
    }

    /**
     * @param array{copy:array<string,string>,steps:list<array<string,mixed>>} $model
     */
    private static function tariffSlug(array $model, string $stepId, string $value): string
    {
        if ($value === '') {
            return '';
        }
        foreach ($model['steps'] as $step) {
            if ($step['id'] !== $stepId) {
                continue;
            }
            $option = self::option($step, $value);
            return $option ? (string)$option['tariff_slug'] : '';
        }
        return '';
    }

    /**
     * @param list<array<string,mixed>> $steps
     * @return list<array<string,mixed>>
     */
    private static function ordered(array $steps): array
    {
        usort($steps, static function (array $a, array $b): int {
            if ($a['id'] === 'direction') {
                return -1;
            }
            if ($b['id'] === 'direction') {
                return 1;
            }
            return ((int)$a['sort'] <=> (int)$b['sort']) ?: strcmp((string)$a['id'], (string)$b['id']);
        });
        return $steps;
    }

    private static function formatArea(float $area): string
    {
        $rounded = round($area, 1);
        if (abs($rounded - round($rounded)) < 0.001) {
            return number_format($rounded, 0, ',', ' ');
        }
        return number_format($rounded, 1, ',', ' ');
    }

    private static function validImage(string $path): bool
    {
        return (bool)preg_match('#^/(assets|uploads)/[a-zA-Z0-9_./-]+$#', $path) && !str_contains($path, '..');
    }

    /**
     * @return array<string,mixed>
     */
    private static function decode(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array{copy:array<string,string>,steps:list<array<string,mixed>>}
     */
    private static function defaults(): array
    {
        $opt = static function (string $value, string $label, string $image = '', string $tariff = '', bool $exclusive = false, int $sort = 10): array {
            return [
                'value' => $value,
                'label' => $label,
                'image' => $image,
                'tariff_slug' => $tariff,
                'exclusive' => $exclusive,
                'sort' => $sort,
                'enabled' => true,
            ];
        };
        $step = static function (string $id, string $branch, string $title, string $type, int $sort, bool $lockEnabled, bool $lockRequired, int $max = 0, array $options = [], bool $required = true): array {
            return [
                'id' => $id,
                'branch' => $branch,
                'title' => $title,
                'type' => $type,
                'required' => $required,
                'enabled' => true,
                'lock_enabled' => $lockEnabled,
                'lock_required' => $lockRequired,
                'sort' => $sort,
                'max' => $max,
                'options' => $options,
            ];
        };

        return [
            'copy' => [
                'intro_title' => 'Узнайте предварительную стоимость ремонта за 1 минуту',
                'intro_text' => 'Ответьте на несколько коротких вопросов о квартире, доме или коммерческом помещении. Рассчитаем ориентировочную стоимость работ.',
                'start_label' => 'Начать расчёт',
                'intro_note' => 'Бесплатно • Без обязательств • Работы без материалов.',
                'next_label' => 'Далее',
                'back_label' => 'Назад',
                'result_title' => 'Предварительная стоимость вашего ремонта',
                'result_note' => self::RESULT_NOTE,
                'estimate_label' => 'Получить подробную смету',
                'files_label' => 'Прикрепить проект и уточнить стоимость',
                'file_field_label' => 'Прикрепить фото, планировку или проект',
                'submit_label' => 'Отправить заявку на подробный расчёт',
                'contact_title' => 'Заявка на подробный расчёт',
            ],
            'steps' => [
                $step('direction', '', 'Что планируете ремонтировать?', 'single', 0, true, true, 0, [
                    $opt('apartment', 'Квартиру', '/assets/images/hero/living-room.webp', '', false, 10),
                    $opt('house', 'Частный дом или коттедж', '/assets/images/houses/hero.webp', '', false, 20),
                    $opt('commercial', 'Коммерческое помещение', '/assets/images/commercial/hero.webp', '', false, 30),
                ]),
                $step('apt_property', 'apartment', 'Тип недвижимости', 'single', 10, false, false, 0, [
                    $opt('newbuild', 'Новостройка', '/assets/images/services/newbuild.webp', '', false, 10),
                    $opt('secondary', 'Вторичное жильё', '/assets/images/services/secondary.webp', '', false, 20),
                    $opt('studio', 'Студия или апартаменты', '/assets/images/services/studio.webp', '', false, 30),
                ]),
                $step('apt_repair', 'apartment', 'Вид ремонта', 'single', 20, true, true, 0, [
                    $opt('cosmetic', 'Косметический', '/assets/images/services/cosmetic.webp', 'kosmeticheskiy-remont', false, 10),
                    $opt('capital', 'Капитальный', '/assets/images/services/capital.webp', 'kapitalnyy-remont', false, 20),
                    $opt('turnkey', 'Под ключ', '/assets/images/services/turnkey.webp', 'remont-kvartir-pod-klyuch', false, 30),
                    $opt('designer', 'Дизайнерский', '/assets/images/services/designer.webp', 'dizaynerskiy-remont', false, 40),
                ]),
                $step('apt_area', 'apartment', 'Площадь квартиры', 'area', 30, true, true, 1000),
                $step('apt_condition', 'apartment', 'Состояние квартиры', 'single', 40, false, false, 0, [
                    $opt('shell', 'Без отделки', '', '', false, 10),
                    $opt('prefinish', 'Предчистовая отделка', '', '', false, 20),
                    $opt('old', 'Старая отделка', '', '', false, 30),
                    $opt('partial', 'Требуется частичное обновление', '', '', false, 40),
                ]),
                $step('apt_extras', 'apartment', 'Дополнительные работы', 'multi', 50, false, false, 0, [
                    $opt('electric', 'Электрика', '', '', false, 10),
                    $opt('plumbing', 'Сантехника', '', '', false, 20),
                    $opt('walls', 'Выравнивание стен', '', '', false, 30),
                    $opt('screed', 'Стяжка', '', '', false, 40),
                    $opt('tile', 'Укладка плитки', '', '', false, 50),
                    $opt('sound', 'Дополнительная звукоизоляция', '', '', false, 60),
                    $opt('none', 'Ничего дополнительного', '', '', true, 70),
                ]),
                $step('house_property', 'house', 'Что ремонтируем?', 'single', 10, false, false, 0, [
                    $opt('house', 'Частный дом', '/assets/images/houses/hero.webp', '', false, 10),
                    $opt('cottage', 'Коттедж', '/assets/images/houses/cottage.webp', '', false, 20),
                    $opt('townhouse', 'Таунхаус', '/assets/images/houses/townhouse.webp', '', false, 30),
                    $opt('country', 'Загородный дом', '/assets/images/houses/country.webp', '', false, 40),
                ]),
                $step('house_repair', 'house', 'Вид ремонта', 'single', 20, true, true, 0, [
                    $opt('cosmetic', 'Косметический', '/assets/images/houses/cosmetic.webp', 'kosmeticheskiy', false, 10),
                    $opt('capital', 'Капитальный', '/assets/images/houses/capital.webp', 'kapitalnyy', false, 20),
                    $opt('turnkey', 'Под ключ', '/assets/images/houses/turnkey.webp', 'pod-klyuch', false, 30),
                    $opt('after_build', 'Внутренняя отделка после строительства', '/assets/images/houses/after-build.webp', 'posle-stroitelstva', false, 40),
                ]),
                $step('house_area', 'house', 'Площадь дома', 'area', 30, true, true, 5000),
                $step('house_floors', 'house', 'Количество этажей', 'single', 40, false, false, 0, [
                    $opt('1', '1', '', '', false, 10),
                    $opt('2', '2', '', '', false, 20),
                    $opt('3', '3', '', '', false, 30),
                    $opt('4plus', '4 и более', '', '', false, 40),
                ]),
                $step('house_condition', 'house', 'Состояние дома', 'single', 50, false, false, 0, [
                    $opt('shell', 'Без внутренней отделки', '', '', false, 10),
                    $opt('rough', 'Черновая отделка', '', '', false, 20),
                    $opt('capital', 'Требуется капитальное обновление', '', '', false, 30),
                    $opt('cosmetic', 'Требуется косметическое обновление', '', '', false, 40),
                ]),
                $step('house_extras', 'house', 'Дополнительные работы', 'multi', 60, false, false, 0, [
                    $opt('stairs', 'Лестница', '', '', false, 10),
                    $opt('bathrooms', 'Санузлы', '', '', false, 20),
                    $opt('electric', 'Электромонтаж', '', '', false, 30),
                    $opt('plumbing', 'Сантехника', '', '', false, 40),
                    $opt('walls', 'Отделка стен и потолков', '', '', false, 50),
                    $opt('tile', 'Укладка плитки', '', '', false, 60),
                ], false),
                $step('com_purpose', 'commercial', 'Назначение помещения', 'single', 10, true, true, 0, [
                    $opt('office', 'Офис', '/assets/images/commercial/office.webp', 'remont-ofisov', false, 10),
                    $opt('store', 'Магазин', '/assets/images/commercial/store.webp', 'remont-magazinov', false, 20),
                    $opt('salon', 'Салон красоты', '/assets/images/commercial/salon.webp', 'remont-salonov-krasoty', false, 30),
                    $opt('cafe', 'Кафе или ресторан', '/assets/images/commercial/cafe.webp', 'remont-kafe-restoranov', false, 40),
                    $opt('warehouse', 'Склад', '/assets/images/commercial/warehouse.webp', 'remont-skladskih-pomescheniy', false, 50),
                    $opt('free', 'Помещение свободного назначения', '/assets/images/commercial/free-purpose.webp', 'remont-pomescheniy-svobodnogo-naznacheniya', false, 60),
                ]),
                $step('com_repair', 'commercial', 'Вид ремонта', 'single', 20, true, true, 0, [
                    $opt('cosmetic', 'Косметический', '', '', false, 10),
                    $opt('capital', 'Капитальный', '', '', false, 20),
                    $opt('turnkey', 'Комплексный под ключ', '', '', false, 30),
                ]),
                $step('com_area', 'commercial', 'Площадь помещения', 'area', 30, true, true, 20000),
                $step('com_condition', 'commercial', 'Состояние помещения', 'single', 40, false, false, 0, [
                    $opt('shell', 'Без отделки', '', '', false, 10),
                    $opt('partial', 'Частичная отделка', '', '', false, 20),
                    $opt('refresh', 'Требуется обновление', '', '', false, 30),
                    $opt('rebuild', 'Требуется полная реконструкция внутренней отделки', '', '', false, 40),
                ]),
                $step('com_extras', 'commercial', 'Необходимые работы', 'multi', 50, false, false, 0, [
                    $opt('walls', 'Стены', '', '', false, 10),
                    $opt('floors', 'Полы', '', '', false, 20),
                    $opt('ceilings', 'Потолки', '', '', false, 30),
                    $opt('electric', 'Электрика', '', '', false, 40),
                    $opt('plumbing', 'Сантехника', '', '', false, 50),
                    $opt('partitions', 'Перегородки', '', '', false, 60),
                    $opt('full', 'Комплексный ремонт', '', '', false, 70),
                ], false),
                $step('com_project', 'commercial', 'Наличие технического задания', 'single', 60, false, false, 0, [
                    $opt('yes', 'Есть проект или ТЗ', '', '', false, 10),
                    $opt('progress', 'Проект в разработке', '', '', false, 20),
                    $opt('no', 'Проекта нет', '', '', false, 30),
                ]),
            ],
        ];
    }
}
