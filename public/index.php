<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\LeadController;
use App\Controllers\PublicController;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\SiteRepository;
use App\Services\LeadService;
use App\Services\MailNotifyService;
use App\Services\TelegramService;
use App\Services\UploadService;
use App\Support\CommercialCatalog;
use App\Support\HouseCatalog;

if (PHP_SAPI === 'cli-server') {
    $staticPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $staticFile = __DIR__ . $staticPath;
    if ($staticPath !== '/' && is_file($staticFile)) {
        return false;
    }
}

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

$config = new Config($root);
$cookieParams = session_get_cookie_params();
session_name((string)$config->get('app.session_name', 'kf_session'));
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookieParams['path'] ?: '/',
    'domain' => $cookieParams['domain'] ?: '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$request = new Request();
$db = new Database($config);
$repo = new SiteRepository($db);
$view = new View($root . '/app/Views');
$telegram = new TelegramService($config, $db);
$uploadService = new UploadService($config, $root . '/public/uploads', $root . '/storage/lead-files');
$mailNotify = new MailNotifyService($config, $repo);
$leadService = new LeadService($db, $config, $repo, $telegram, $uploadService, $mailNotify);
$public = new PublicController($view, $repo);
$leads = new LeadController($leadService);
$admin = new AdminController($view, $repo, new Auth($db), $config, $telegram, $uploadService, $mailNotify);

$path = $request->path();
$pathNoSlash = trim($path, '/');

try {
    if ($path === '/robots.txt') {
        $public->robots();
    }
    if ($path === '/sitemap.xml') {
        $public->sitemap();
    }
    if ($path === '/lead/submit' && $request->isPost()) {
        $leads->submit($request);
    }
    if ($path === '/lead/estimate' && $request->isPost()) {
        $leads->estimate($request);
    }
    if (str_starts_with($pathNoSlash, 'admin')) {
        echo $admin->handle($request, $pathNoSlash ?: 'admin');
        exit;
    }

    echo match (true) {
        $path === '/' => $public->home(),
        $path === '/contacts' || $path === '/contacts/' => $public->contacts(),
        $path === '/portfolio' || $path === '/portfolio/' => $public->portfolio(),
        $pathNoSlash === 'remont-kvartir' => $public->apartmentHub(),
        $pathNoSlash === HouseCatalog::HUB_SLUG => $public->houseHub(),
        preg_match('#^remont-domov/([a-z0-9-]+)$#', $pathNoSlash, $houseMatch) === 1 => $public->houseService($houseMatch[1]),
        ($legacyHouse = HouseCatalog::legacyTarget($pathNoSlash)) ? $public->redirect($legacyHouse) : false,
        $pathNoSlash === CommercialCatalog::HUB_SLUG => $public->commercialHub(),
        CommercialCatalog::isServiceSlug($pathNoSlash) => $public->commercialService($pathNoSlash),
        in_array($pathNoSlash, ['privacy-policy', 'personal-data-consent', 'cookies'], true) => $public->legal($pathNoSlash),
        preg_match('#^uslugi/([a-z0-9-]+)/?$#', $pathNoSlash, $m) === 1 => $public->service($m[1]),
        preg_match('#^remont-kvartir/([a-z0-9-]+)/?$#', $pathNoSlash, $m) === 1 => $public->geo($m[1]),
        default => $public->customPage($pathNoSlash),
    };
} catch (Throwable $e) {
    if ($config->get('app.debug', false)) {
        http_response_code(500);
        echo '<pre>' . e((string)$e) . '</pre>';
        exit;
    }
    error_log((string)$e);
    http_response_code(500);
    echo $view->render('public/500', ['metaTitle' => 'Ошибка сервера', 'settings' => $repo->settings(), 'h1' => 'Ошибка сервера']);
}
