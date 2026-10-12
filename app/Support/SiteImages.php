<?php

declare(strict_types=1);

namespace App\Support;

final class SiteImages
{
    /**
     * @return array{src:string,alt:string,width:int,height:int,loading?:string}
     */
    public static function hero(): array
    {
        return [
            'src' => '/assets/images/hero/living-room.webp',
            'alt' => 'Современная просторная гостиная после ремонта: светлый интерьер с диваном и панорамным окном',
            'width' => 1400,
            'height' => 1050,
            'loading' => 'eager',
        ];
    }

    /**
     * @param array<string,mixed> $service
     * @return array{src:string,alt:string,width:int,height:int}
     */
    public static function serviceCard(array $service): array
    {
        $slug = (string)($service['slug'] ?? '');
        $map = self::serviceMap();
        $fallback = $map[$slug]['card'] ?? [
            'src' => '/assets/images/services/turnkey.webp',
            'alt' => 'Ремонт квартиры под ключ в Москве',
            'width' => 1200,
            'height' => 840,
        ];
        $alt = trim((string)($service['image_alt'] ?? '')) ?: $fallback['alt'];
        return self::resolve($service['image_path'] ?? null, $fallback, $alt);
    }

    /**
     * @param array<string,mixed> $service
     * @return array{src:string,alt:string,width:int,height:int}
     */
    public static function serviceHero(array $service): array
    {
        $slug = (string)($service['slug'] ?? '');
        $map = self::serviceMap();
        $fallback = $map[$slug]['page'] ?? self::hero();
        $alt = trim((string)($service['image_alt'] ?? '')) ?: ($fallback['alt'] ?? $service['title'] ?? 'Ремонт квартиры');
        if (!empty($service['image_path'])) {
            return self::resolve($service['image_path'], $fallback, $alt);
        }
        $fallback['alt'] = $alt;
        $fallback['width'] = $fallback['width'] ?? 1600;
        $fallback['height'] = $fallback['height'] ?? 900;
        return $fallback;
    }

    /**
     * @param array<string,mixed> $item
     * @return array{src:string,alt:string,width:int,height:int}
     */
    public static function portfolio(array $item, int $index = 0): array
    {
        $images = $item['images'] ?? [];
        if ($images) {
            $first = $images[0];
            $path = (string)($first['file_path'] ?? '');
            $alt = (string)($first['alt_text'] ?? $item['title'] ?? 'Пример интерьера после ремонта');
            return self::resolve($path, self::portfolioCatalog()[$index % count(self::portfolioCatalog())], $alt);
        }
        $catalog = self::portfolioCatalog();
        $key = (string)($item['image_key'] ?? '');
        foreach ($catalog as $entry) {
            if ($key !== '' && $entry['key'] === $key) {
                return $entry;
            }
            if (($entry['title'] ?? '') === ($item['title'] ?? '')) {
                return $entry;
            }
        }
        return $catalog[$index % count($catalog)];
    }

    /**
     * @return array{src:string,alt:string,width:int,height:int}
     */
    public static function geo(string $slug): array
    {
        $pool = [
            [
                'src' => '/assets/images/geo/interior-1.webp',
                'alt' => 'Ремонт квартиры: светлая гостиная после отделки',
                'width' => 1600,
                'height' => 900,
            ],
            [
                'src' => '/assets/images/geo/interior-2.webp',
                'alt' => 'Финишная отделка квартиры: гардероб и санузел',
                'width' => 1600,
                'height' => 900,
            ],
            [
                'src' => '/assets/images/geo/process-1.webp',
                'alt' => 'Кухня после ремонта квартиры',
                'width' => 1600,
                'height' => 900,
            ],
            [
                'src' => '/assets/images/geo/process-2.webp',
                'alt' => 'Отделочные работы при ремонте квартиры',
                'width' => 1600,
                'height' => 900,
            ],
        ];
        $index = abs(crc32($slug)) % count($pool);
        return $pool[$index];
    }

    /**
     * @return array{src:string,alt:string,width:int,height:int}
     */
    public static function contacts(): array
    {
        return [
            'src' => '/assets/images/contacts/office.webp',
            'alt' => 'Офис и рабочее пространство компании КвартираФормат',
            'width' => 1400,
            'height' => 900,
        ];
    }

    /**
     * @return array{src:string,alt:string,width:int,height:int}
     */
    public static function prices(): array
    {
        return [
            'src' => '/assets/images/prices/interior.webp',
            'alt' => 'Современная кухня после ремонта квартиры',
            'width' => 1400,
            'height' => 700,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function catalog(): array
    {
        $items = [self::hero(), self::prices(), self::contacts()];
        foreach (self::serviceMap() as $entry) {
            $items[] = $entry['card'];
            $items[] = $entry['page'];
        }
        foreach (self::portfolioCatalog() as $entry) {
            $items[] = $entry;
        }
        $items = array_merge($items, [
            self::geo('a'),
            self::geo('b'),
            self::geo('c'),
            self::geo('d'),
        ]);
        $items = array_merge($items, array_values(CommercialCatalog::images()));
        $unique = [];
        foreach ($items as $item) {
            $unique[$item['src']] = $item;
        }
        return array_values($unique);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function illustrativePortfolio(): array
    {
        $items = [];
        foreach (self::portfolioCatalog() as $i => $image) {
            $items[] = [
                'title' => $image['title'],
                'city' => '',
                'district' => '',
                'area' => '',
                'repair_type' => $image['repair_type'],
                'cost' => '',
                'duration' => '',
                'works' => '',
                'is_demo' => 0,
                'image_key' => $image['key'],
                'images' => [],
                'sort_order' => ($i + 1) * 10,
            ];
        }
        return $items;
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array<string,mixed>>
     */
    public static function normalizePortfolio(array $items): array
    {
        if (self::isPlaceholderPortfolio($items)) {
            return self::illustrativePortfolio();
        }
        return $items;
    }

    /**
     * @return array<string,array{card:array{src:string,alt:string,width:int,height:int},page:array{src:string,alt:string,width:int,height:int}}>
     */
    private static function serviceMap(): array
    {
        return [
            'remont-kvartir-pod-klyuch' => [
                'card' => ['src' => '/assets/images/services/turnkey.webp', 'alt' => 'Ремонт квартиры под ключ в Москве', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/turnkey-page.webp', 'alt' => 'Готовый современный интерьер квартиры после ремонта под ключ', 'width' => 1600, 'height' => 900],
            ],
            'kapitalnyy-remont' => [
                'card' => ['src' => '/assets/images/services/capital.webp', 'alt' => 'Мастер в форме КвартираФормат штукатурит стену при капитальном ремонте', 'width' => 1448, 'height' => 1086],
                'page' => ['src' => '/assets/images/services/capital-page.webp', 'alt' => 'Мастер в форме КвартираФормат штукатурит стену при капитальном ремонте', 'width' => 1448, 'height' => 1086],
            ],
            'kosmeticheskiy-remont' => [
                'card' => ['src' => '/assets/images/services/cosmetic.webp', 'alt' => 'Мастер в форме КвартираФормат красит стену валиком при косметическом ремонте', 'width' => 1448, 'height' => 1086],
                'page' => ['src' => '/assets/images/services/cosmetic-page.webp', 'alt' => 'Мастер в форме КвартираФормат красит стену валиком при косметическом ремонте', 'width' => 1448, 'height' => 1086],
            ],
            'dizaynerskiy-remont' => [
                'card' => ['src' => '/assets/images/services/designer.webp', 'alt' => 'Дизайнерский ремонт квартиры', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/designer-page.webp', 'alt' => 'Стильный премиальный интерьер после дизайнерского ремонта', 'width' => 1600, 'height' => 900],
            ],
            'remont-novostroek' => [
                'card' => ['src' => '/assets/images/services/newbuild.webp', 'alt' => 'Ремонт квартиры в новостройке', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/newbuild-page.webp', 'alt' => 'Отделка квартиры в новостройке', 'width' => 1600, 'height' => 900],
            ],
            'remont-vtorichnogo-zhilya' => [
                'card' => ['src' => '/assets/images/services/secondary.webp', 'alt' => 'Ремонт вторичного жилья', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/secondary-page.webp', 'alt' => 'Обновлённая квартира вторичного фонда после ремонта', 'width' => 1600, 'height' => 900],
            ],
            'remont-studiy' => [
                'card' => ['src' => '/assets/images/services/studio.webp', 'alt' => 'Ремонт современной квартиры-студии', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/studio-page.webp', 'alt' => 'Компактная студия после ремонта', 'width' => 1600, 'height' => 900],
            ],
            'remont-odnokomnatnyh-kvartir' => [
                'card' => ['src' => '/assets/images/services/one-room.webp', 'alt' => 'Ремонт однокомнатной квартиры', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/one-room-page.webp', 'alt' => 'Аккуратная однокомнатная квартира после ремонта', 'width' => 1600, 'height' => 900],
            ],
            'remont-dvuhkomnatnyh-kvartir' => [
                'card' => ['src' => '/assets/images/services/two-room.webp', 'alt' => 'Ремонт двухкомнатной квартиры', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/two-room-page.webp', 'alt' => 'Уютный семейный интерьер двухкомнатной квартиры', 'width' => 1600, 'height' => 900],
            ],
            'remont-trehkomnatnyh-kvartir' => [
                'card' => ['src' => '/assets/images/services/three-room.webp', 'alt' => 'Ремонт трёхкомнатной квартиры', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/three-room-page.webp', 'alt' => 'Просторный современный интерьер трёхкомнатной квартиры', 'width' => 1600, 'height' => 900],
            ],
            'remont-vannoy-i-sanuzla' => [
                'card' => ['src' => '/assets/images/services/bathroom.webp', 'alt' => 'Ремонт ванной и санузла', 'width' => 1200, 'height' => 840],
                'page' => ['src' => '/assets/images/services/bathroom-page.webp', 'alt' => 'Современная ванная комната после ремонта', 'width' => 1600, 'height' => 900],
            ],
        ];
    }

    /**
     * @return list<array{key:string,src:string,alt:string,title:string,repair_type:string,width:int,height:int}>
     */
    private static function portfolioCatalog(): array
    {
        return [
            ['key' => 'living', 'src' => '/assets/images/portfolio/living.webp', 'alt' => 'Пример гостиной после ремонта', 'title' => 'Пример гостиной после ремонта', 'repair_type' => 'Интерьер гостиной', 'width' => 1200, 'height' => 840],
            ['key' => 'kitchen', 'src' => '/assets/images/portfolio/kitchen.webp', 'alt' => 'Пример кухни после ремонта', 'title' => 'Пример кухни после ремонта', 'repair_type' => 'Кухня', 'width' => 1200, 'height' => 840],
            ['key' => 'bathroom', 'src' => '/assets/images/portfolio/bathroom.webp', 'alt' => 'Пример ванной после ремонта', 'title' => 'Пример ванной после ремонта', 'repair_type' => 'Ванная и санузел', 'width' => 1200, 'height' => 840],
            ['key' => 'bedroom', 'src' => '/assets/images/portfolio/bedroom.webp', 'alt' => 'Пример спальни после ремонта', 'title' => 'Пример спальни после ремонта', 'repair_type' => 'Спальня', 'width' => 1200, 'height' => 840],
            ['key' => 'hallway', 'src' => '/assets/images/portfolio/hallway.webp', 'alt' => 'Пример прихожей после ремонта', 'title' => 'Пример прихожей после ремонта', 'repair_type' => 'Прихожая', 'width' => 1200, 'height' => 840],
            ['key' => 'family', 'src' => '/assets/images/portfolio/family.webp', 'alt' => 'Пример семейной квартиры после ремонта', 'title' => 'Пример семейной квартиры после ремонта', 'repair_type' => 'Семейный интерьер', 'width' => 1200, 'height' => 840],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $items
     */
    private static function isPlaceholderPortfolio(array $items): bool
    {
        if (!$items) {
            return true;
        }
        if (count($items) === 1) {
            $title = (string)($items[0]['title'] ?? '');
            return str_contains($title, 'Демонстрацион') || !empty($items[0]['is_demo']);
        }
        return false;
    }

    /**
     * @param array{src:string,alt:string,width?:int,height?:int} $fallback
     * @return array{src:string,alt:string,width:int,height:int}
     */
    private static function resolve(mixed $path, array $fallback, string $alt = ''): array
    {
        $src = self::url($path) ?: $fallback['src'];
        return [
            'src' => $src,
            'alt' => $alt !== '' ? $alt : ($fallback['alt'] ?? 'Ремонт квартиры'),
            'width' => (int)($fallback['width'] ?? 1200),
            'height' => (int)($fallback['height'] ?? 840),
        ];
    }

    private static function url(mixed $path): string
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return $path;
        }
        if (str_starts_with($path, 'assets/')) {
            return '/' . $path;
        }
        return '/uploads/' . ltrim($path, '/');
    }
}
