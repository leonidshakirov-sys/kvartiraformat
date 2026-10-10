<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\SiteRepository;
use App\Services\TelegramService;
use App\Services\UploadService;

final class AdminController
{
    public function __construct(
        private View $view,
        private SiteRepository $repo,
        private Auth $auth,
        private Config $config,
        private TelegramService $telegram,
        private UploadService $uploads
    ) {
    }

    public function handle(Request $request, string $path): string
    {
        $path = trim($path, '/');
        if ($path === 'admin/login') {
            return $request->isPost() ? $this->login($request) : $this->loginForm();
        }
        if ($path === 'admin/setup') {
            return $request->isPost() ? $this->setup($request) : $this->setupForm();
        }
        if (!$this->auth->hasUsers()) {
            Response::redirect('/admin/setup');
        }
        if (!$this->auth->check()) {
            Response::redirect('/admin/login');
        }
        if ($path === 'admin/logout' && $request->isPost()) {
            if (Csrf::verify((string)$request->input('_csrf', ''))) {
                $this->auth->logout();
            }
            Response::redirect('/admin/login');
        }
        if ($request->isPost()) {
            return $this->post($request, $path);
        }
        return $this->page($path ?: 'admin');
    }

    private function page(string $path): string
    {
        $section = str_replace('admin/', '', $path);
        $section = $section === 'admin' ? 'dashboard' : $section;
        $data = $this->adminData($section);
        $template = match ($section) {
            'pages' => 'admin/pages',
            'texts' => 'admin/texts',
            'photos' => 'admin/photos',
            'services' => 'admin/services',
            'portfolio' => 'admin/portfolio',
            'seo' => 'admin/seo',
            'calculator' => 'admin/calculator',
            'leads' => 'admin/leads',
            'settings' => 'admin/settings',
            'integrations' => 'admin/integrations',
            default => 'admin/dashboard',
        };
        return $this->view->render($template, $data, 'layouts/admin');
    }

    private function post(Request $request, string $path): string
    {
        if (!Csrf::verify((string)$request->input('_csrf', ''))) {
            $_SESSION['flash_error'] = 'Проверка безопасности формы не пройдена.';
            Response::redirect('/admin/');
        }
        $action = (string)$request->input('action', '');
        try {
            match ($action) {
                'save_settings' => $this->saveSettings($request),
                'save_page' => $this->repo->upsertPage($request->post()),
                'save_service' => $this->saveService($request),
                'save_geo' => $this->repo->upsertGeo($request->post()),
                'save_tariff' => $this->saveTariff($request),
                'save_faq' => $this->saveFaq($request),
                'save_advantage' => $this->saveAdvantage($request),
                'save_portfolio' => $this->savePortfolio($request),
                'lead_status' => $this->updateLead($request),
                'upload_media' => $this->uploadMedia(),
                'delete' => $this->repo->deleteById((string)$request->input('table'), (int)$request->input('id')),
                'telegram_test' => $this->telegramTest(),
                default => null,
            };
            $_SESSION['flash_success'] = 'Изменения сохранены.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        Response::redirect('/' . $path);
    }

    private function loginForm(): string
    {
        if ($this->auth->check()) {
            Response::redirect('/admin/');
        }
        return $this->view->render('admin/login', ['metaTitle' => 'Вход в админ-панель'], 'layouts/blank');
    }

    private function login(Request $request): string
    {
        if (!Csrf::verify((string)$request->input('_csrf', ''))) {
            $_SESSION['flash_error'] = 'Обновите страницу входа и повторите попытку.';
            Response::redirect('/admin/login');
        }
        $ok = $this->auth->attempt(
            trim((string)$request->input('username')),
            (string)$request->input('password'),
            $request->ip(),
            (int)$this->config->get('security.admin_login_limit', 7),
            (int)$this->config->get('security.admin_login_window_minutes', 20)
        );
        if ($ok) {
            Response::redirect('/admin/');
        }
        $_SESSION['flash_error'] = 'Неверный логин или пароль либо превышен лимит попыток.';
        Response::redirect('/admin/login');
    }

    private function setupForm(): string
    {
        if ($this->auth->hasUsers()) {
            Response::redirect('/admin/login');
        }
        return $this->view->render('admin/setup', ['metaTitle' => 'Первичная настройка администратора', 'dbError' => $this->repo->dbError()], 'layouts/blank');
    }

    private function setup(Request $request): string
    {
        if (!Csrf::verify((string)$request->input('_csrf', ''))) {
            $_SESSION['flash_error'] = 'Обновите страницу и повторите попытку.';
            Response::redirect('/admin/setup');
        }
        $username = trim((string)$request->input('username'));
        $password = (string)$request->input('password');
        if (mb_strlen($username) < 3 || mb_strlen($password) < 12) {
            $_SESSION['flash_error'] = 'Логин должен быть от 3 символов, пароль — минимум 12 символов.';
            Response::redirect('/admin/setup');
        }
        if ($this->auth->createFirstAdmin($username, $password)) {
            $_SESSION['flash_success'] = 'Администратор создан. Войдите с указанными данными.';
            Response::redirect('/admin/login');
        }
        $_SESSION['flash_error'] = 'Не удалось создать администратора. Проверьте базу данных.';
        Response::redirect('/admin/setup');
    }

    private function adminData(string $section): array
    {
        return [
            'metaTitle' => 'Админ-панель — ' . $section,
            'section' => $section,
            'user' => $this->auth->user(),
            'settings' => $this->repo->settings(),
            'stats' => $this->repo->dashboardStats(),
            'leads' => $this->repo->leads(),
            'pages' => $this->repo->pages(),
            'services' => $this->repo->services(),
            'geoPages' => $this->repo->geoPages(false),
            'faqs' => $this->repo->faqs(),
            'advantages' => $this->repo->advantages(),
            'portfolio' => $this->repo->portfolio(),
            'tariffs' => $this->repo->tariffs(),
            'telegramConfigured' => $this->telegram->isConfigured(),
            'media' => $this->repo->media(),
            'dbError' => $this->repo->dbError(),
        ];
    }

    private function saveSettings(Request $request): void
    {
        $allowed = ['brand_name','brand_name_full','brand_tagline','service_area','legal_name','inn','phone','phone_href','office_address','domain','ym_counter_id','map_embed_url','route_url','privacy_version'];
        $values = [];
        $post = $request->post();
        foreach ($allowed as $key) {
            if (array_key_exists($key, $post)) {
                $values[$key] = trim((string)$post[$key]);
            }
        }
        if (!$values) {
            throw new \RuntimeException('Нет данных для сохранения.');
        }
        $this->repo->updateSettings($values);
    }

    private function saveTariff(Request $request): void
    {
        $pdo = $this->repo->pdo();
        if (!$pdo) {
            throw new \RuntimeException('База данных не настроена.');
        }
        if ((int)$request->input('id') > 0) {
            $stmt = $pdo->prepare('UPDATE repair_tariffs SET repair_type=?, package_type=?, price_per_m2=?, note=?, is_active=?, sort_order=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$request->input('repair_type'), $request->input('package_type'), (int)$request->input('price_per_m2'), $request->input('note'), (int)$request->input('is_active', 0), (int)$request->input('sort_order', 100), (int)$request->input('id')]);
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO repair_tariffs (repair_type, package_type, price_per_m2, note, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$request->input('repair_type'), $request->input('package_type'), (int)$request->input('price_per_m2'), $request->input('note'), (int)$request->input('is_active', 0), (int)$request->input('sort_order', 100)]);
    }

    private function saveFaq(Request $request): void
    {
        $pdo = $this->repo->pdo();
        if (!$pdo) throw new \RuntimeException('База данных не настроена.');
        $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, is_published, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$request->input('question'), $request->input('answer'), (int)$request->input('is_published', 1), (int)$request->input('sort_order', 100)]);
    }

    private function saveAdvantage(Request $request): void
    {
        $pdo = $this->repo->pdo();
        if (!$pdo) throw new \RuntimeException('База данных не настроена.');
        $stmt = $pdo->prepare('INSERT INTO advantages (title, text, is_published, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$request->input('title'), $request->input('text'), (int)$request->input('is_published', 1), (int)$request->input('sort_order', 100)]);
    }

    private function saveService(Request $request): void
    {
        $data = $request->post();
        if (!empty($_FILES['image']['tmp_name'])) {
            $result = $this->uploads->handle($_FILES['image'], 'services');
            if (!$result['ok']) {
                throw new \RuntimeException($result['error']);
            }
            $data['image_path'] = $result['path'];
        }
        $this->repo->upsertService($data);
    }

    private function savePortfolio(Request $request): void
    {
        $pdo = $this->repo->pdo();
        if (!$pdo) {
            throw new \RuntimeException('База данных не настроена.');
        }
        $stmt = $pdo->prepare('INSERT INTO portfolio_items (title, city, district, area, repair_type, cost, duration, works, is_demo, is_published, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$request->input('title'), $request->input('city'), $request->input('district'), $request->input('area'), $request->input('repair_type'), $request->input('cost'), $request->input('duration'), $request->input('works'), (int)$request->input('is_demo', 0), (int)$request->input('is_published', 1), (int)$request->input('sort_order', 100)]);
        $id = (int)$pdo->lastInsertId();
        if ($id > 0 && !empty($_FILES['image']['tmp_name'])) {
            $result = $this->uploads->handle($_FILES['image'], 'portfolio');
            if (!$result['ok']) {
                throw new \RuntimeException($result['error']);
            }
            $this->repo->attachPortfolioImage($id, $result['path'], (string)$request->input('image_alt', ''), 'gallery');
        }
    }

    private function updateLead(Request $request): void
    {
        $pdo = $this->repo->pdo();
        if (!$pdo) throw new \RuntimeException('База данных не настроена.');
        $stmt = $pdo->prepare('UPDATE leads SET status=?, manager_comment=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$request->input('status'), $request->input('manager_comment'), (int)$request->input('id')]);
    }

    private function uploadMedia(): void
    {
        $result = $this->uploads->handle($_FILES['file'] ?? []);
        if (!$result['ok']) {
            throw new \RuntimeException($result['error']);
        }
        $pdo = $this->repo->pdo();
        if ($pdo) {
            $stmt = $pdo->prepare('INSERT INTO media (file_path, original_name, alt_text, mime_type, width, height, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$result['path'], $result['original'], $_POST['alt_text'] ?? '', $result['mime'], $result['width'], $result['height']]);
        }
    }

    private function telegramTest(): void
    {
        $result = $this->telegram->sendTest();
        if (!$result['ok']) {
            throw new \RuntimeException('Telegram: ' . $result['error']);
        }
    }
}
