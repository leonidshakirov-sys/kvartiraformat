<?php

declare(strict_types=1);

namespace App\Support;

final class StartingPrices
{
    /**
     * @return array<string,int>
     */
    public static function all(): array
    {
        return self::apartment() + self::commercial();
    }

    /**
     * @return array<string,int>
     */
    public static function apartment(): array
    {
        return [
            'remont-kvartir-pod-klyuch' => 18000,
            'kapitalnyy-remont' => 16000,
            'kosmeticheskiy-remont' => 8500,
            'dizaynerskiy-remont' => 28000,
            'remont-novostroek' => 15000,
            'remont-vtorichnogo-zhilya' => 17000,
            'remont-studiy' => 16000,
            'remont-odnokomnatnyh-kvartir' => 15000,
            'remont-dvuhkomnatnyh-kvartir' => 15500,
            'remont-trehkomnatnyh-kvartir' => 16000,
            'remont-vannoy-i-sanuzla' => 22000,
        ];
    }

    /**
     * @return array<string,int>
     */
    public static function commercial(): array
    {
        return [
            'remont-ofisov' => 14000,
            'remont-magazinov' => 16000,
            'remont-salonov-krasoty' => 18000,
            'remont-kafe-restoranov' => 20000,
            'remont-skladskih-pomescheniy' => 9000,
            'remont-pomescheniy-svobodnogo-naznacheniya' => 13000,
        ];
    }

    public static function forSlug(string $slug): ?int
    {
        return self::all()[$slug] ?? null;
    }

    /**
     * @return list<array{repair_type:string,package_type:string,price_per_m2:int,note:string,category:string,sort_order:int}>
     */
    public static function tariffs(): array
    {
        return [
            ['repair_type' => 'Косметический', 'package_type' => 'Только работы', 'price_per_m2' => 8500, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 10],
            ['repair_type' => 'Косметический', 'package_type' => 'Работы с черновыми материалами', 'price_per_m2' => 11000, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 20],
            ['repair_type' => 'Косметический', 'package_type' => 'Комплексный ремонт', 'price_per_m2' => 13000, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 30],
            ['repair_type' => 'Капитальный', 'package_type' => 'Только работы', 'price_per_m2' => 16000, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 40],
            ['repair_type' => 'Капитальный', 'package_type' => 'Работы с черновыми материалами', 'price_per_m2' => 21000, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 50],
            ['repair_type' => 'Капитальный', 'package_type' => 'Комплексный ремонт', 'price_per_m2' => 25000, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 60],
            ['repair_type' => 'Дизайнерский', 'package_type' => 'Только работы', 'price_per_m2' => 28000, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 70],
            ['repair_type' => 'Дизайнерский', 'package_type' => 'Работы с черновыми материалами', 'price_per_m2' => 34000, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 80],
            ['repair_type' => 'Дизайнерский', 'package_type' => 'Комплексный ремонт', 'price_per_m2' => 39000, 'note' => 'Стартовая цена за м²', 'category' => 'apartment', 'sort_order' => 90],
            ['repair_type' => 'Офис', 'package_type' => 'Только работы', 'price_per_m2' => 14000, 'note' => 'Стартовая цена за м²', 'category' => 'commercial', 'sort_order' => 10],
            ['repair_type' => 'Магазин', 'package_type' => 'Только работы', 'price_per_m2' => 16000, 'note' => 'Стартовая цена за м²', 'category' => 'commercial', 'sort_order' => 20],
            ['repair_type' => 'Салон красоты', 'package_type' => 'Только работы', 'price_per_m2' => 18000, 'note' => 'Стартовая цена за м²', 'category' => 'commercial', 'sort_order' => 30],
            ['repair_type' => 'Кафе или ресторан', 'package_type' => 'Только работы', 'price_per_m2' => 20000, 'note' => 'Стартовая цена за м²', 'category' => 'commercial', 'sort_order' => 40],
            ['repair_type' => 'Склад', 'package_type' => 'Только работы', 'price_per_m2' => 9000, 'note' => 'Стартовая цена за м²', 'category' => 'commercial', 'sort_order' => 50],
            ['repair_type' => 'Помещение свободного назначения', 'package_type' => 'Только работы', 'price_per_m2' => 13000, 'note' => 'Стартовая цена за м²', 'category' => 'commercial', 'sort_order' => 60],
        ];
    }
}
