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
use App\Services\MailNotifyService;
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
        private UploadService $uploads,
        private ?MailNotifyService $mail = null
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
        if ($path === 'admin/leads/file') {
            $this->downloadLeadFile($request);
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
            'houses' => 'admin/houses',
            'commercial' => 'admin/commercial',
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
                'save_commercial_texts' => $this->saveCommercialTexts($request),
                'save_house_texts' => $this->saveHouseTexts($request),
                'lead_status' => $this->updateLead($request),
                'delete_lead_file' => $this->deleteLeadFile($request),
                'upload_media' => $this->uploadMedia(),
                'delete' => $this->repo->deleteById((string)$request->input('table'), (int)$request->input('id')),
                'telegram_test' => $this->telegramTest(),
                'save_notify' => $this->saveNotify($request),
                'email_test' => $this->emailTest(),
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
            'leads' => $this->repo->leads(['lead_type' => (string)($_GET['type'] ?? '')]),
            'leadFilter' => (string)($_GET['type'] ?? ''),
            'pages' => $this->repo->pages(),
            'services' => $this->repo->services('apartment', false),
            'commercialServices' => $this->repo->services('commercial', false),
            'houseServices' => $this->repo->services('house', false),
            'geoPages' => $this->repo->geoPages(false),
            'faqs' => $this->repo->faqs('apartment'),
            'commercialFaqs' => $this->repo->faqs('commercial'),
            'houseFaqs' => $this->repo->faqs('house'),
            'advantages' => $this->repo->advantages('apartment'),
            'commercialAdvantages' => $this->repo->advantages('commercial'),
            'houseAdvantages' => $this->repo->advantages('house'),
            'portfolio' => $this->repo->portfolio('apartment', true),
            'commercialPortfolio' => $this->repo->portfolio('commercial', true),
            'housePortfolio' => $this->repo->portfolio('house'),
            'tariffs' => $this->repo->tariffs('apartment'),
            'commercialTariffs' => $this->repo->tariffs('commercial'),
            'houseTariffs' => $this->repo->tariffs('house'),
            'telegramConfigured' => $this->telegram->isConfigured(),
            'telegramQueue' => $this->telegram->recentQueue(8),
            'mailConfigured' => $this->mail?->isConfigured() ?? false,
            'media' => $this->repo->media(),
            'dbError' => $this->repo->dbError(),
        ];
    }

    private function saveSettings(Request $request): void
    {
        $allowed = ['brand_name','brand_name_full','brand_tagline','service_area','legal_name','inn','phone','phone_href','office_address','domain','ym_counter_id','map_embed_url','route_url','privacy_version','working_hours','working_hours_note','commercial_meta_title','commercial_meta_description','commercial_h1','commercial_intro','commercial_hub_published','house_meta_title','house_meta_description','house_h1','house_intro','house_hub_published','apartment_hub_published','notify_email','notify_email_from'];
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
        $category = in_array((string)$request->input('category'), ['commercial', 'apartment', 'house'], true) ? (string)$request->input('category') : 'apartment';
        $approved = (int)$request->input('is_approved', 0) === 1 ? 1 : 0;
        $slug = trim((string)$request->input('service_slug', ''));
        $priceRaw = trim((string)$request->input('price_per_m2', ''));
        $price = $priceRaw === '' ? null : (int)$priceRaw;
        $note = trim((string)$request->input('note', '')) ?: 'Стоимость работ без материалов';
        $unit = (string)$request->input('price_unit', 'm2') === 'room' ? 'room' : 'm2';
        if ((int)$request->input('id') > 0) {
            $stmt = $pdo->prepare('UPDATE repair_tariffs SET repair_type=?, package_type=?, price_per_m2=?, note=?, is_active=?, sort_order=?, category=?, service_slug=?, is_approved=?, price_unit=?, approved_at=IF(?=1, COALESCE(approved_at, NOW()), NULL), updated_at=NOW() WHERE id=?');
            $stmt->execute([$request->input('repair_type'), $request->input('package_type'), $price, $note, (int)$request->input('is_active', 0), (int)$request->input('sort_order', 100), $category, $slug !== '' ? $slug : null, $approved, $unit, $approved, (int)$request->input('id')]);
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO repair_tariffs (repair_type, package_type, price_per_m2, note, is_active, sort_order, category, service_slug, is_approved, price_unit, approved_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, IF(?=1, NOW(), NULL), NOW(), NOW())');
        $stmt->execute([$request->input('repair_type'), $request->input('package_type'), $price, $note, (int)$request->input('is_active', 0), (int)$request->input('sort_order', 100), $category, $slug !== '' ? $slug : null, $approved, $unit, $approved]);
    }

    private function saveFaq(Request $request): void
    {
        $pdo = $this->repo->pdo();
        if (!$pdo) throw new \RuntimeException('База данных не настроена.');
        $category = in_array((string)$request->input('category'), ['commercial', 'apartment', 'house'], true) ? (string)$request->input('category') : 'apartment';
        $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$request->input('question'), $request->input('answer'), (int)$request->input('is_published', 1), (int)$request->input('sort_order', 100), $category]);
    }

    private function saveAdvantage(Request $request): void
    {
        $pdo = $this->repo->pdo();
        if (!$pdo) throw new \RuntimeException('База данных не настроена.');
        $category = in_array((string)$request->input('category'), ['commercial', 'apartment', 'house'], true) ? (string)$request->input('category') : 'apartment';
        $stmt = $pdo->prepare('INSERT INTO advantages (title, text, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$request->input('title'), $request->input('text'), (int)$request->input('is_published', 1), (int)$request->input('sort_order', 100), $category]);
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
        $category = in_array((string)$request->input('category'), ['commercial', 'apartment', 'house'], true) ? (string)$request->input('category') : 'apartment';
        $stmt = $pdo->prepare('INSERT INTO portfolio_items (title, city, district, area, repair_type, cost, duration, works, is_demo, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$request->input('title'), $request->input('city'), $request->input('district'), $request->input('area'), $request->input('repair_type'), $request->input('cost'), $request->input('duration'), $request->input('works'), (int)$request->input('is_demo', 0), (int)$request->input('is_published', 1), (int)$request->input('sort_order', 100), $category]);
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

    private function saveCommercialTexts(Request $request): void
    {
        $post = $request->post();
        $this->repo->updateSettings([
            'commercial_meta_title' => trim((string)($post['commercial_meta_title'] ?? '')),
            'commercial_meta_description' => trim((string)($post['commercial_meta_description'] ?? '')),
            'commercial_h1' => trim((string)($post['commercial_h1'] ?? '')),
            'commercial_intro' => trim((string)($post['commercial_intro'] ?? '')),
            'commercial_hub_published' => !empty($post['commercial_hub_published']) ? '1' : '0',
        ]);
    }

    private function saveHouseTexts(Request $request): void
    {
        $post = $request->post();
        $this->repo->updateSettings([
            'house_meta_title' => trim((string)($post['house_meta_title'] ?? '')),
            'house_meta_description' => trim((string)($post['house_meta_description'] ?? '')),
            'house_h1' => trim((string)($post['house_h1'] ?? '')),
            'house_intro' => trim((string)($post['house_intro'] ?? '')),
            'house_hub_published' => !empty($post['house_hub_published']) ? '1' : '0',
        ]);
    }

    private function deleteLeadFile(Request $request): void
    {
        $file = $this->repo->deleteLeadFile((int)$request->input('id', 0));
        if ($file) {
            $this->uploads->deleteStored([(string)$file['stored_name']]);
        }
    }

    private function downloadLeadFile(Request $request): never
    {
        $file = $this->repo->leadFile((int)$request->input('id', 0));
        if (!$file) {
            http_response_code(404);
            echo 'Файл не найден';
            exit;
        }
        $path = $this->uploads->privateAbsolutePath((string)$file['stored_name']);
        if (!$path) {
            http_response_code(404);
            echo 'Файл недоступен';
            exit;
        }
        $name = str_replace(['"', "\r", "\n"], '', (string)$file['original_name']);
        $mime = strtolower((string)($file['mime_type'] ?? ''));
        $preview = (string)$request->input('preview', '') === '1';
        $isImage = in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Content-Length: ' . (string)filesize($path));
        if ($preview && $isImage) {
            header('Content-Type: ' . $mime);
            header('Content-Disposition: inline; filename="' . $name . '"');
        } else {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $name . '"');
        }
        readfile($path);
        exit;
    }

    private function telegramTest(): void
    {
        $result = $this->telegram->sendTest();
        if (!$result['ok']) {
            $stage = $result['stage'] ?? 'connect';
            $hint = in_array($stage, ['dns', 'tcp', 'tls', 'connect', 'transfer'], true)
                ? ' Это сетевой отказ до api.telegram.org (на Beget Telegram часто блокируется ТСПУ). Заявки уже сохраняются в разделе «Заявки». Включите email ниже и запустите php tools/telegram_diagnose.php по SSH.'
                : ' Проверьте chat_id и что бот запущен. Токен в отчёт не выводится.';
            throw new \RuntimeException('Telegram: ' . $result['error'] . $hint);
        }
    }

    private function saveNotify(\App\Core\Request $request): void
    {
        $this->repo->updateSettings([
            'notify_email' => trim((string)$request->input('notify_email', '')),
            'notify_email_from' => trim((string)$request->input('notify_email_from', '')),
            'notify_email_enabled' => $request->input('notify_email_enabled') ? '1' : '0',
        ]);
    }

    private function emailTest(): void
    {
        if (!$this->mail) {
            throw new \RuntimeException('Почтовый сервис недоступен.');
        }
        $this->saveNotify(new \App\Core\Request());
        if (!$this->mail->isConfigured()) {
            throw new \RuntimeException('Включите email-уведомления и укажите адрес.');
        }
        $result = $this->mail->sendTest();
        if (!$result['ok']) {
            throw new \RuntimeException((string)$result['error']);
        }
    }
}
