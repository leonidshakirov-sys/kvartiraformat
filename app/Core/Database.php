<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private ?PDO $pdo = null;
    private ?string $error = null;

    public function __construct(private Config $config)
    {
    }

    public function pdo(): ?PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $db = $this->config->get('db', []);
        if (($db['database'] ?? '') === 'your_database_name') {
            $this->error = 'База данных не настроена. Создайте config/config.php и импортируйте database/database.sql.';
            return null;
        }

        try {
            $dsn = sprintf(
                '%s:host=%s;dbname=%s;charset=%s',
                $db['driver'] ?? 'mysql',
                $db['host'] ?? 'localhost',
                $db['database'] ?? '',
                $db['charset'] ?? 'utf8mb4'
            );
            $this->pdo = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            $this->error = 'Ошибка подключения к базе данных: ' . $exception->getMessage();
            return null;
        }

        return $this->pdo;
    }

    public function isConnected(): bool
    {
        return $this->pdo() instanceof PDO;
    }

    public function error(): ?string
    {
        return $this->error;
    }
}
