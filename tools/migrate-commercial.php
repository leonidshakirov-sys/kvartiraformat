<?php

declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/app/bootstrap.php';

$config = new App\Core\Config($root);
$db = new App\Core\Database($config);
$pdo = $db->pdo();
if (!$pdo) {
    fwrite(STDERR, ($db->error() ?: 'Нет подключения к базе') . PHP_EOL);
    exit(1);
}

echo "Миграция коммерческого раздела применена (или уже была применена)." . PHP_EOL;
echo "Резервная копия: storage/backups/pre-commercial-*.sql" . PHP_EOL;
