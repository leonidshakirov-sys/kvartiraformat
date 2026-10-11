<?php

declare(strict_types=1);

namespace App\Support;

final class StartingPrices
{
    /**
     * Preliminary owner-confirmation tariffs. Unpublished until is_approved = 1.
     *
     * @return list<array{slug:string,repair_type:string,package_type:string,price_per_m2:?int,note:string,category:string,sort_order:int,is_approved:int,includes_materials:int}>
     */
    public static function catalog(): array
    {
        $note = 'Стоимость работ без материалов. Предварительный ориентир, требуется утверждение.';
        return [
            ['slug' => 'kosmeticheskiy-remont', 'repair_type' => 'Косметический', 'package_type' => 'Только работы', 'price_per_m2' => 8000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 10, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'kapitalnyy-remont', 'repair_type' => 'Капитальный', 'package_type' => 'Только работы', 'price_per_m2' => 14000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 20, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-kvartir-pod-klyuch', 'repair_type' => 'Под ключ', 'package_type' => 'Только работы', 'price_per_m2' => 14000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 30, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'dizaynerskiy-remont', 'repair_type' => 'Дизайнерский', 'package_type' => 'Только работы', 'price_per_m2' => 19000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 40, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-novostroek', 'repair_type' => 'Новостройка', 'package_type' => 'Только работы', 'price_per_m2' => 12000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 50, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-vtorichnogo-zhilya', 'repair_type' => 'Вторичное жильё', 'package_type' => 'Только работы', 'price_per_m2' => 14000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 60, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-studiy', 'repair_type' => 'Студия', 'package_type' => 'Только работы', 'price_per_m2' => 12000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 70, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-odnokomnatnyh-kvartir', 'repair_type' => 'Однокомнатная', 'package_type' => 'Только работы', 'price_per_m2' => 12000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 80, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-dvuhkomnatnyh-kvartir', 'repair_type' => 'Двухкомнатная', 'package_type' => 'Только работы', 'price_per_m2' => 12000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 90, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-trehkomnatnyh-kvartir', 'repair_type' => 'Трёхкомнатная', 'package_type' => 'Только работы', 'price_per_m2' => 12000, 'note' => $note, 'category' => 'apartment', 'sort_order' => 100, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-vannoy-i-sanuzla', 'repair_type' => 'Санузел', 'package_type' => 'Только работы', 'price_per_m2' => null, 'note' => $note, 'category' => 'apartment', 'sort_order' => 110, 'is_approved' => 0, 'includes_materials' => 0],

            ['slug' => 'pod-klyuch', 'repair_type' => 'Под ключ', 'package_type' => 'Только работы', 'price_per_m2' => 16000, 'note' => $note, 'category' => 'house', 'sort_order' => 10, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'kapitalnyy', 'repair_type' => 'Капитальный', 'package_type' => 'Только работы', 'price_per_m2' => 16000, 'note' => $note, 'category' => 'house', 'sort_order' => 20, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'kosmeticheskiy', 'repair_type' => 'Косметический', 'package_type' => 'Только работы', 'price_per_m2' => 9000, 'note' => $note, 'category' => 'house', 'sort_order' => 30, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'otdelka-kottedzhey', 'repair_type' => 'Отделка коттеджа', 'package_type' => 'Только работы', 'price_per_m2' => 14000, 'note' => $note, 'category' => 'house', 'sort_order' => 40, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'taunkhausov', 'repair_type' => 'Таунхаус', 'package_type' => 'Только работы', 'price_per_m2' => 14000, 'note' => $note, 'category' => 'house', 'sort_order' => 50, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'zagorodnyh', 'repair_type' => 'Загородный дом', 'package_type' => 'Только работы', 'price_per_m2' => 14000, 'note' => $note, 'category' => 'house', 'sort_order' => 60, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'posle-stroitelstva', 'repair_type' => 'После строительства', 'package_type' => 'Только работы', 'price_per_m2' => 15000, 'note' => $note, 'category' => 'house', 'sort_order' => 70, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'sanuzlov', 'repair_type' => 'Санузел в доме', 'package_type' => 'Только работы', 'price_per_m2' => null, 'note' => $note, 'category' => 'house', 'sort_order' => 80, 'is_approved' => 0, 'includes_materials' => 0],

            ['slug' => 'remont-ofisov', 'repair_type' => 'Офис', 'package_type' => 'Только работы', 'price_per_m2' => 5000, 'note' => $note, 'category' => 'commercial', 'sort_order' => 10, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-magazinov', 'repair_type' => 'Магазин', 'package_type' => 'Только работы', 'price_per_m2' => 6000, 'note' => $note, 'category' => 'commercial', 'sort_order' => 20, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-salonov-krasoty', 'repair_type' => 'Салон красоты', 'package_type' => 'Только работы', 'price_per_m2' => 7000, 'note' => $note, 'category' => 'commercial', 'sort_order' => 30, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-kafe-restoranov', 'repair_type' => 'Кафе или ресторан', 'package_type' => 'Только работы', 'price_per_m2' => 10000, 'note' => $note, 'category' => 'commercial', 'sort_order' => 40, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-skladskih-pomescheniy', 'repair_type' => 'Склад', 'package_type' => 'Только работы', 'price_per_m2' => 4000, 'note' => $note, 'category' => 'commercial', 'sort_order' => 50, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-pomescheniy-svobodnogo-naznacheniya', 'repair_type' => 'Помещение свободного назначения', 'package_type' => 'Только работы', 'price_per_m2' => 6000, 'note' => $note, 'category' => 'commercial', 'sort_order' => 60, 'is_approved' => 0, 'includes_materials' => 0],
            ['slug' => 'remont-kommercheskih-pomescheniy', 'repair_type' => 'Коммерческий ремонт', 'package_type' => 'Только работы', 'price_per_m2' => 6000, 'note' => $note, 'category' => 'commercial', 'sort_order' => 70, 'is_approved' => 0, 'includes_materials' => 0],
        ];
    }

    /**
     * @return array<string,int>
     */
    public static function all(): array
    {
        $out = [];
        foreach (self::catalog() as $item) {
            if ($item['price_per_m2']) {
                $out[$item['slug']] = (int)$item['price_per_m2'];
            }
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
        return self::all()[$slug] ?? null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function item(string $slug): ?array
    {
        foreach (self::catalog() as $item) {
            if ($item['slug'] === $slug) {
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
            $items[] = $item + ['is_active' => 1];
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
            if ($item['category'] === $category && $item['price_per_m2']) {
                $out[$item['slug']] = (int)$item['price_per_m2'];
            }
        }
        return $out;
    }
}
