<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class SchemaMigrator
{
    private const VERSION_KEY = 'schema_commercial_v1';

    public static function apply(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        self::ensureYmCounter($pdo);
        self::ensureStartingPrices($pdo);

        if (self::hasSetting($pdo, self::VERSION_KEY)) {
            if (!self::hasCommercialServices($pdo)) {
                self::seedCommercial($pdo);
            }
            self::ensureHouses($pdo);
            return;
        }

        self::backup($pdo);
        self::ensureColumn($pdo, 'services', 'category', "VARCHAR(40) NOT NULL DEFAULT 'apartment'");
        self::ensureIndex($pdo, 'services', 'idx_services_category', 'category, is_published, sort_order');
        self::ensureColumn($pdo, 'faqs', 'category', "VARCHAR(40) NOT NULL DEFAULT 'apartment'");
        self::ensureColumn($pdo, 'advantages', 'category', "VARCHAR(40) NOT NULL DEFAULT 'apartment'");
        self::ensureColumn($pdo, 'portfolio_items', 'category', "VARCHAR(40) NOT NULL DEFAULT 'apartment'");
        self::ensureColumn($pdo, 'repair_tariffs', 'category', "VARCHAR(40) NOT NULL DEFAULT 'apartment'");
        self::ensureColumn($pdo, 'leads', 'lead_type', "VARCHAR(40) NOT NULL DEFAULT 'apartment'");
        self::ensureColumn($pdo, 'leads', 'work_start', 'VARCHAR(160) NULL');
        self::ensureColumn($pdo, 'leads', 'has_project', 'VARCHAR(80) NULL');
        self::ensureColumn($pdo, 'leads', 'premise_condition', 'VARCHAR(160) NULL');
        self::ensureIndex($pdo, 'leads', 'idx_leads_type', 'lead_type, created_at');
        self::ensureLeadFiles($pdo);
        self::seedCommercial($pdo);
        self::touchSetting($pdo, self::VERSION_KEY, '1');
        self::ensureHouses($pdo);
    }

    private static function ensureHouses(PDO $pdo): void
    {
        if (!self::columnExists($pdo, 'services', 'category')) {
            return;
        }
        if (!self::hasSetting($pdo, 'schema_houses_v1') || !self::hasHouseServices($pdo)) {
            self::seedHouses($pdo);
            self::touchSetting($pdo, 'schema_houses_v1', '1');
        }
        self::ensureColumn($pdo, 'repair_tariffs', 'is_approved', 'TINYINT(1) NOT NULL DEFAULT 1');
        self::ensureColumn($pdo, 'repair_tariffs', 'includes_materials', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'repair_tariffs', 'works_list', 'TEXT NULL');
        self::ensureColumn($pdo, 'repair_tariffs', 'approved_at', 'DATETIME NULL');
    }

    private static function hasHouseServices(PDO $pdo): bool
    {
        if (!self::columnExists($pdo, 'services', 'category')) {
            return false;
        }
        $count = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE category = 'house'")->fetchColumn();
        return $count > 0;
    }

    private static function seedHouses(PDO $pdo): void
    {
        $stmt = $pdo->prepare('INSERT INTO services (title, slug, short, body, price, image_path, image_alt, meta_title, meta_description, h1, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, 1, ?, \'house\', NOW(), NOW()) ON DUPLICATE KEY UPDATE slug = slug');
        foreach (HouseCatalog::services() as $service) {
            $stmt->execute([
                $service['title'],
                $service['slug'],
                $service['short'],
                $service['body'],
                $service['image_path'],
                $service['image_alt'],
                $service['meta_title'],
                $service['meta_description'],
                $service['h1'],
                (int)$service['sort_order'],
            ]);
        }

        if (self::columnExists($pdo, 'faqs', 'category')) {
            $faq = $pdo->prepare('INSERT INTO faqs (question, answer, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, 1, ?, \'house\', NOW(), NOW()) ON DUPLICATE KEY UPDATE question = question');
            foreach (HouseCatalog::faqs() as $i => $item) {
                $faq->execute([$item['q'], $item['a'], ($i + 1) * 10]);
            }
        }

        if (self::columnExists($pdo, 'advantages', 'category')) {
            $adv = $pdo->prepare('INSERT INTO advantages (title, text, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, 1, ?, \'house\', NOW(), NOW()) ON DUPLICATE KEY UPDATE title = title');
            foreach (HouseCatalog::advantages() as $i => $item) {
                $adv->execute([$item['title'], $item['text'], ($i + 1) * 10]);
            }
        }

        $hub = HouseCatalog::hub();
        foreach ([
            'house_meta_title' => $hub['meta_title'],
            'house_meta_description' => $hub['meta_description'],
            'house_h1' => $hub['h1'],
            'house_intro' => $hub['intro'],
            'house_hub_published' => '1',
            'apartment_hub_published' => '1',
        ] as $key => $value) {
            if (!self::hasSetting($pdo, $key)) {
                self::touchSetting($pdo, $key, $value);
            }
        }
    }

    private static function backup(PDO $pdo): void
    {
        $dir = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $file = $dir . '/pre-commercial-' . date('Ymd-His') . '.sql';
        $tables = ['settings', 'services', 'faqs', 'advantages', 'portfolio_items', 'repair_tariffs', 'leads', 'pages'];
        $sql = "-- KvartiraFormat backup before commercial migration\nSET NAMES utf8mb4;\n";
        foreach ($tables as $table) {
            if (!self::tableExists($pdo, $table)) {
                continue;
            }
            try {
                $create = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
                $sql .= "\n-- TABLE {$table}\n" . ($create['Create Table'] ?? '') . ";\n";
                $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $cols = array_map(static fn($c) => '`' . str_replace('`', '``', (string)$c) . '`', array_keys($row));
                    $vals = array_map(static function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote((string)$v);
                    }, array_values($row));
                    $sql .= 'INSERT INTO `' . $table . '` (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ");\n";
                }
            } catch (\Throwable) {
                continue;
            }
        }
        @file_put_contents($file, $sql);
    }

    private static function hasCommercialServices(PDO $pdo): bool
    {
        if (!self::columnExists($pdo, 'services', 'category')) {
            return false;
        }
        $count = (int)$pdo->query("SELECT COUNT(*) FROM services WHERE category = 'commercial'")->fetchColumn();
        return $count > 0;
    }

    private static function seedCommercial(PDO $pdo): void
    {
        if (!self::columnExists($pdo, 'services', 'category')) {
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO services (title, slug, short, body, price, image_path, image_alt, meta_title, meta_description, h1, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, 1, ?, \'commercial\', NOW(), NOW()) ON DUPLICATE KEY UPDATE slug = slug');
        foreach (CommercialCatalog::services() as $service) {
            $stmt->execute([
                $service['title'],
                $service['slug'],
                $service['short'],
                $service['body'],
                $service['image_path'],
                $service['image_alt'],
                $service['meta_title'],
                $service['meta_description'],
                $service['h1'],
                (int)$service['sort_order'],
            ]);
        }

        if (self::columnExists($pdo, 'faqs', 'category')) {
            $faq = $pdo->prepare('INSERT INTO faqs (question, answer, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, 1, ?, \'commercial\', NOW(), NOW()) ON DUPLICATE KEY UPDATE question = question');
            foreach (CommercialCatalog::faqs() as $i => $item) {
                $faq->execute([$item['q'], $item['a'], ($i + 1) * 10]);
            }
        }

        if (self::columnExists($pdo, 'advantages', 'category')) {
            $adv = $pdo->prepare('INSERT INTO advantages (title, text, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, 1, ?, \'commercial\', NOW(), NOW()) ON DUPLICATE KEY UPDATE title = title');
            foreach (CommercialCatalog::advantages() as $i => $item) {
                $adv->execute([$item['title'], $item['text'], ($i + 1) * 10]);
            }
        }

        $hub = CommercialCatalog::hub();
        $set = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_key = setting_key');
        foreach ([
            'commercial_meta_title' => $hub['meta_title'],
            'commercial_meta_description' => $hub['meta_description'],
            'commercial_h1' => $hub['h1'],
            'commercial_intro' => $hub['intro'],
            'commercial_hub_published' => '1',
        ] as $key => $value) {
            $set->execute([$key, $value]);
        }
    }

    private static function ensureLeadFiles(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS lead_files (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            lead_id INT UNSIGNED NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(160) NULL,
            size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX idx_lead_files_lead (lead_id),
            CONSTRAINT fk_lead_files_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private static function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!self::tableExists($pdo, $table) || self::columnExists($pdo, $table, $column)) {
            return;
        }
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }

    private static function ensureIndex(PDO $pdo, string $table, string $index, string $columns): void
    {
        if (!self::tableExists($pdo, $table) || self::indexExists($pdo, $table, $index)) {
            return;
        }
        $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function columnExists(PDO $pdo, string $table, string $column): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function indexExists(PDO $pdo, string $table, string $index): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?');
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function ensureStartingPrices(PDO $pdo): void
    {
        try {
            $update = $pdo->prepare('UPDATE services SET price = ?, updated_at = NOW() WHERE slug = ? AND (price IS NULL OR price = 0)');
            foreach (StartingPrices::all() as $slug => $price) {
                $update->execute([$price, $slug]);
            }
        } catch (\Throwable) {
            return;
        }

        try {
            $count = (int)$pdo->query('SELECT COUNT(*) FROM repair_tariffs')->fetchColumn();
        } catch (\Throwable) {
            return;
        }
        if ($count > 0) {
            return;
        }
        $hasCategory = self::columnExists($pdo, 'repair_tariffs', 'category');
        foreach (StartingPrices::tariffs() as $tariff) {
            if ($hasCategory) {
                $stmt = $pdo->prepare('INSERT INTO repair_tariffs (repair_type, package_type, price_per_m2, note, is_active, sort_order, category, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, NOW(), NOW())');
                $stmt->execute([$tariff['repair_type'], $tariff['package_type'], $tariff['price_per_m2'], $tariff['note'], $tariff['sort_order'], $tariff['category']]);
            } elseif ($tariff['category'] === 'apartment') {
                $stmt = $pdo->prepare('INSERT INTO repair_tariffs (repair_type, package_type, price_per_m2, note, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, NOW(), NOW())');
                $stmt->execute([$tariff['repair_type'], $tariff['package_type'], $tariff['price_per_m2'], $tariff['note'], $tariff['sort_order']]);
            }
        }
    }

    private static function ensureYmCounter(PDO $pdo): void
    {
        $current = '';
        try {
            $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute(['ym_counter_id']);
            $current = trim((string)$stmt->fetchColumn());
        } catch (\Throwable) {
            return;
        }
        if ($current === '' || $current === '123456') {
            self::touchSetting($pdo, 'ym_counter_id', '113599005');
        }
    }

    private static function hasSetting(PDO $pdo, string $key): bool
    {
        try {
            $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            return (string)$stmt->fetchColumn() !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    private static function touchSetting(PDO $pdo, string $key, string $value): void
    {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
        $stmt->execute([$key, $value]);
    }
}
