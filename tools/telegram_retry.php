<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Services\TelegramService;

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';
$config = new Config($root);
$db = new Database($config);
$service = new TelegramService($config, $db);
$result = $service->sendPending(20);
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
