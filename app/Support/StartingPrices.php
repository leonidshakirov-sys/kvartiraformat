<?php

declare(strict_types=1);

namespace App\Support;

final class StartingPrices
{
    /**
     * Owner-approved tariffs. Amounts are numeric; the template adds «от» and the unit.
     *
     * @return list<array{slug:string,repair_type:string,package_type:string,price_per_m2:int,unit:string,note:string,category:string,sort_order:int,is_approved:int,includes_materials:int}>
     */
    public static function catalog(): array
    {
        $note = 'Стоимость работ без материалов';
        $row = static function (string $slug, string $type, int $price, string $category, int $sort, string $unit = 'm2') use ($note): array {
            return [
                'slug' => $slug,
                'repair_type' => $type,
                'package_type' => 'Только работы',
                'price_per_m2' => $price,
                'unit' => $unit,
                'note' => $note,
                'category' => $category,
                'sort_order' => $sort,
                'is_approved' => 1,
                'includes_materials' => 0,
            ];
        };

        return [
            $row('kosmeticheskiy-remont', 'Косметический', 8000, 'apartment', 10),
            $row('kapitalnyy-remont', 'Капитальный', 15000, 'apartment', 20),
            $row('remont-kvartir-pod-klyuch', 'Под ключ', 16000, 'apartment', 30),
            $row('dizaynerskiy-remont', 'Дизайнерский', 22000, 'apartment', 40),
            $row('remont-novostroek', 'Новостройка', 14000, 'apartment', 50),
            $row('remont-vtorichnogo-zhilya', 'Вторичное жильё', 16000, 'apartment', 60),
            $row('remont-studiy', 'Студия', 14000, 'apartment', 70),
            $row('remont-odnokomnatnyh-kvartir', 'Однокомнатная', 14000, 'apartment', 80),
            $row('remont-dvuhkomnatnyh-kvartir', 'Двухкомнатная', 14000, 'apartment', 90),
            $row('remont-trehkomnatnyh-kvartir', 'Трёхкомнатная', 14000, 'apartment', 100),
            $row('remont-vannoy-i-sanuzla', 'Санузел', 180000, 'apartment', 110, 'room'),

            $row('kosmeticheskiy', 'Косметический', 9000, 'house', 30),
            $row('kapitalnyy', 'Капитальный', 17000, 'house', 20),
            $row('pod-klyuch', 'Под ключ', 18000, 'house', 10),
            $row('otdelka-kottedzhey', 'Отделка коттеджа', 15000, 'house', 40),
            $row('taunkhausov', 'Таунхаус', 16000, 'house', 50),
            $row('zagorodnyh', 'Загородный дом', 16000, 'house', 60),
            $row('posle-stroitelstva', 'После строительства', 16000, 'house', 70),
            $row('sanuzlov', 'Санузел в доме', 180000, 'house', 80, 'room'),

            $row('remont-kommercheskih-pomescheniy', 'Коммерческий ремонт', 8000, 'commercial', 70),
            $row('remont-ofisov', 'Офис', 7000, 'commercial', 10),
            $row('remont-magazinov', 'Магазин', 8000, 'commercial', 20),
            $row('remont-salonov-krasoty', 'Салон красоты', 10000, 'commercial', 30),
            $row('remont-kafe-restoranov', 'Кафе или ресторан', 12000, 'commercial', 40),
            $row('remont-skladskih-pomescheniy', 'Склад', 5000, 'commercial', 50),
            $row('remont-pomescheniy-svobodnogo-naznacheniya', 'Помещение свободного назначения', 8000, 'commercial', 60),
        ];
    }

    /**
     * @return array<string,int>
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::catalog() as $item) {
            $out[$item['category'] . ':' . $item['slug']] = (int)$item['price_per_m2'];
        }
        return $out;
    }

    /**
     * @return array<string,int>
     */
    public static function apartment(): array
    {
        return self::byCategory('apartment');
    }

    /**
     * @return array<string,int>
     */
    public static function commercial(): array
    {
        return self::byCategory('commercial');
    }

    /**
     * @return array<string,int>
     */
    public static function house(): array
    {
        return self::byCategory('house');
    }

    public static function forSlug(string $slug): ?int
    {
        foreach (self::catalog() as $item) {
            if ($item['slug'] === $slug) {
                return (int)$item['price_per_m2'];
            }
        }
        return null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function item(string $slug, ?string $category = null): ?array
    {
        foreach (self::catalog() as $item) {
            if ($item['slug'] === $slug && ($category === null || $item['category'] === $category)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function tariffs(?string $category = null): array
    {
        $items = [];
        foreach (self::catalog() as $item) {
            if ($category && $item['category'] !== $category) {
                continue;
            }
            $items[] = $item + ['is_active' => 1, 'price_unit' => $item['unit']];
        }
        return $items;
    }

    /**
     * @return array<string,int>
     */
    private static function byCategory(string $category): array
    {
        $out = [];
        foreach (self::catalog() as $item) {
            if ($item['category'] === $category) {
                $out[$item['slug']] = (int)$item['price_per_m2'];
            }
        }
        return $out;
    }
}
