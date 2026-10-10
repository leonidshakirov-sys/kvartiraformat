<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\View;
use App\Repositories\SiteRepository;
use App\Support\CommercialCatalog;

final class PublicController
{
    public function __construct(private View $view, private SiteRepository $repo)
    {
    }

    public function home(): string
    {
        $settings = $this->repo->settings();
        return $this->view->render('public/home', $this->common([
            'metaTitle' => 'Ремонт квартир под ключ в Москве и Подмосковье | КвартираФормат',
            'metaDescription' => 'КвартираФормат: ремонт квартир под ключ в Москве и Московской области. Калькулятор стоимости, заявка на замер, услуги и прозрачный расчёт сметы.',
            'h1' => 'Ремонт квартир под ключ в Москве и Московской области',
            'canonical' => '/',
            'settings' => $settings,
            'services' => $this->repo->services('apartment'),
            'commercialServices' => $this->repo->services('commercial'),
            'advantages' => $this->repo->advantages('apartment'),
            'portfolio' => $this->repo->portfolio('apartment'),
            'geoPages' => $this->repo->geoPages(true),
            'faqs' => $this->repo->faqs('apartment'),
            'tariffs' => $this->repo->tariffs('apartment'),
            'preloadImage' => '/assets/images/hero/living-room.webp',
            'ogImage' => '/assets/images/hero/living-room.webp',
        ]));
    }

    public function contacts(): string
    {
        $settings = $this->repo->settings();
        return $this->view->render('public/contacts', $this->common([
            'metaTitle' => 'Контакты компании КвартираФормат',
            'metaDescription' => 'Контакты КвартираФормат: телефон, ИП Шакиров Леонид Альбертович, ИНН, адрес офиса в Москве и форма заявки на ремонт квартиры.',
            'h1' => 'Контакты компании КвартираФормат',
            'canonical' => '/contacts/',
            'settings' => $settings,
        ]));
    }

    public function portfolio(): string
    {
        return $this->view->render('public/portfolio', $this->common([
            'metaTitle' => 'Портфолио ремонтов квартир | КвартираФормат',
            'metaDescription' => 'Примеры интерьеров после ремонта квартир. Иллюстративные фото отмечены и не выдаются за выполненные объекты компании.',
            'h1' => 'Наши работы',
            'canonical' => '/portfolio/',
            'portfolio' => $this->repo->portfolio(),
            'settings' => $this->repo->settings(),
        ]));
    }

    public function service(string $slug): string
    {
        $service = $this->repo->service($slug);
        if (!$service) {
            return $this->notFound();
        }
        $title = $service['meta_title'] ?? ($service['title'] . ' в Москве и Московской области | КвартираФормат');
        return $this->view->render('public/service', $this->common([
            'metaTitle' => $title,
            'metaDescription' => $service['meta_description'] ?? excerpt((string)($service['short'] ?? ''), 150),
            'h1' => $service['h1'] ?? $service['title'],
            'canonical' => '/uslugi/' . $slug . '/',
            'service' => $service,
            'services' => $this->repo->services('apartment'),
            'faqs' => $this->repo->faqs('apartment'),
            'settings' => $this->repo->settings(),
        ]));
    }

    public function commercialHub(): string
    {
        $settings = $this->repo->settings();
        if (isset($settings['commercial_hub_published']) && (string)$settings['commercial_hub_published'] === '0') {
            return $this->notFound();
        }
        $hub = CommercialCatalog::hub();
        $hero = CommercialCatalog::image('hero');
        return $this->view->render('public/commercial', $this->common([
            'metaTitle' => $settings['commercial_meta_title'] ?? $hub['meta_title'],
            'metaDescription' => $settings['commercial_meta_description'] ?? $hub['meta_description'],
            'h1' => $settings['commercial_h1'] ?? $hub['h1'],
            'intro' => $settings['commercial_intro'] ?? $hub['intro'],
            'canonical' => '/' . CommercialCatalog::HUB_SLUG . '/',
            'settings' => $settings,
            'commercialServices' => $this->repo->services('commercial'),
            'apartmentServices' => $this->repo->services('apartment'),
            'advantages' => $this->repo->advantages('commercial'),
            'faqs' => $this->repo->faqs('commercial'),
            'portfolio' => $this->repo->portfolio('commercial'),
            'works' => CommercialCatalog::commonWorks(),
            'stages' => CommercialCatalog::stages(),
            'cities' => CommercialCatalog::cities(),
            'heroImage' => $hero,
            'processImage' => CommercialCatalog::image('process'),
            'propertyType' => '',
            'calcHref' => '#calculator',
            'leadHref' => '#lead-form',
            'preloadImage' => $hero['src'],
            'ogImage' => $hero['src'],
        ]));
    }

    public function commercialService(string $slug): string
    {
        $service = $this->repo->commercialService($slug);
        if (!$service) {
            return $this->notFound();
        }
        $hero = CommercialCatalog::image((string)($service['image_key'] ?? 'hero'));
        if (!empty($service['image_path'])) {
            $hero['src'] = (string)$service['image_path'];
            $hero['alt'] = (string)($service['image_alt'] ?: $hero['alt']);
        }
        return $this->view->render('public/commercial-service', $this->common([
            'metaTitle' => $service['meta_title'] ?? ($service['title'] . ' | КвартираФормат'),
            'metaDescription' => $service['meta_description'] ?? excerpt((string)($service['short'] ?? ''), 150),
            'h1' => $service['h1'] ?? $service['title'],
            'canonical' => '/' . $slug . '/',
            'service' => $service,
            'commercialServices' => $this->repo->services('commercial'),
            'apartmentServices' => $this->repo->services('apartment'),
            'faqs' => $this->repo->faqs('commercial'),
            'stages' => CommercialCatalog::stages(),
            'heroImage' => $hero,
            'processImage' => CommercialCatalog::image('process'),
            'propertyType' => $service['property_type'] ?? '',
            'calcHref' => '#calculator',
            'leadHref' => '#lead-form',
            'settings' => $this->repo->settings(),
            'preloadImage' => $hero['src'],
            'ogImage' => $hero['src'],
        ]));
    }

    public function geo(string $slug): string
    {
        $geo = $this->repo->geoPage($slug);
        if (!$geo) {
            return $this->notFound();
        }
        $name = $geo['name'];
        return $this->view->render('public/geo', $this->common([
            'metaTitle' => $geo['title'] ?? "Ремонт квартир под ключ в $name — расчёт стоимости | КвартираФормат",
            'metaDescription' => $geo['description'] ?? "Ремонт квартир под ключ в $name: виды ремонта, расчёт стоимости, заявка на замер и консультацию.",
            'h1' => $geo['h1'] ?? "Ремонт квартир под ключ в $name",
            'canonical' => '/remont-kvartir/' . $slug . '/',
            'geo' => $geo,
            'services' => $this->repo->services('apartment'),
            'faqs' => $this->repo->faqs('apartment'),
            'settings' => $this->repo->settings(),
        ]));
    }

    public function legal(string $type): string
    {
        $map = [
            'privacy-policy' => ['Политика обработки персональных данных', 'privacy'],
            'personal-data-consent' => ['Согласие на обработку персональных данных', 'consent'],
            'cookies' => ['Политика использования cookies', 'cookies'],
        ];
        if (!isset($map[$type])) {
            return $this->notFound();
        }
        return $this->view->render('public/legal', $this->common([
            'metaTitle' => $map[$type][0] . ' | КвартираФормат',
            'metaDescription' => $map[$type][0] . ' сайта КвартираФормат. Документ подготовлен для проверки владельцем и юристом перед запуском.',
            'h1' => $map[$type][0],
            'canonical' => '/' . $type . '/',
            'docType' => $map[$type][1],
            'settings' => $this->repo->settings(),
        ]));
    }

    public function customPage(string $slug): string
    {
        $page = $this->repo->page($slug);
        if (!$page) {
            return $this->notFound();
        }
        return $this->view->render('public/page', $this->common([
            'metaTitle' => $page['meta_title'] ?: $page['title'],
            'metaDescription' => $page['meta_description'] ?: excerpt($page['body']),
            'h1' => $page['h1'] ?: $page['title'],
            'canonical' => '/' . trim($page['slug'], '/') . '/',
            'page' => $page,
            'settings' => $this->repo->settings(),
        ]));
    }

    public function sitemap(): never
    {
        $settings = $this->repo->settings();
        $base = rtrim('https://' . ($settings['domain'] ?? 'kvartiraformat.ru'), '/');
        $urls = ['/', '/contacts/', '/portfolio/', '/privacy-policy/', '/personal-data-consent/', '/cookies/'];
        if (($settings['commercial_hub_published'] ?? '1') !== '0') {
            $urls[] = '/' . CommercialCatalog::HUB_SLUG . '/';
        }
        foreach ($this->repo->services('apartment') as $service) {
            $urls[] = '/uslugi/' . $service['slug'] . '/';
        }
        foreach ($this->repo->services('commercial') as $service) {
            $urls[] = '/' . $service['slug'] . '/';
        }
        foreach ($this->repo->geoPages(true) as $geo) {
            if (($geo['priority'] ?? 1) <= 1 || !empty($geo['is_published'])) {
                $urls[] = '/remont-kvartir/' . $geo['slug'] . '/';
            }
        }
        foreach ($this->repo->pages() as $page) {
            if (!empty($page['is_published']) && !empty($page['robots_index'])) {
                $urls[] = '/' . trim($page['slug'], '/') . '/';
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
        foreach (array_unique($urls) as $url) {
            $xml .= '  <url><loc>' . htmlspecialchars($base . $url, ENT_XML1, 'UTF-8') . '</loc><changefreq>weekly</changefreq></url>' . "\n";
        }
        $xml .= '</urlset>';
        Response::text($xml, 'application/xml; charset=utf-8');
    }

    public function robots(): never
    {
        $settings = $this->repo->settings();
        $domain = $settings['domain'] ?? 'kvartiraformat.ru';
        $txt = "User-agent: *\nDisallow: /admin/\nDisallow: /storage/\nDisallow: /config/\nAllow: /uploads/\nSitemap: https://$domain/sitemap.xml\n";
        Response::text($txt, 'text/plain; charset=utf-8');
    }

    public function notFound(): string
    {
        http_response_code(404);
        return $this->view->render('public/404', $this->common([
            'metaTitle' => 'Страница не найдена | КвартираФормат',
            'metaDescription' => 'Запрошенная страница не найдена.',
            'h1' => 'Страница не найдена',
            'canonical' => '/404',
            'settings' => $this->repo->settings(),
        ]));
    }

    private function common(array $data): array
    {
        $data['dbError'] = $this->repo->dbError();
        $data['currentPath'] = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return $data;
    }
}
