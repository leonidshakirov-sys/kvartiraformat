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
        self::ensureWorkingHours($pdo);
        self::ensureStartingPrices($pdo);
        self::syncApprovedTariffs($pdo);
        self::cleanupPublicFaqs($pdo);

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
        self::renameHouseSlugs($pdo);
        if (!self::hasSetting($pdo, 'schema_houses_v1') || !self::hasHouseServices($pdo)) {
            self::seedHouses($pdo);
            self::touchSetting($pdo, 'schema_houses_v1', '1');
        }
        self::refreshHouseCopy($pdo);
        self::ensureCustomerFaqs($pdo);
        self::ensureColumn($pdo, 'repair_tariffs', 'is_approved', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'repair_tariffs', 'includes_materials', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'repair_tariffs', 'works_list', 'TEXT NULL');
        self::ensureColumn($pdo, 'repair_tariffs', 'approved_at', 'DATETIME NULL');
        self::ensureColumn($pdo, 'repair_tariffs', 'service_slug', 'VARCHAR(160) NULL');
    }

    private static function renameHouseSlugs(PDO $pdo): void
    {
        try {
            $stmt = $pdo->prepare("UPDATE services SET slug = ? WHERE slug = ? AND category = 'house'");
            foreach (HouseCatalog::legacyMap() as $old => $new) {
                $stmt->execute([$new, $old]);
            }
        } catch (\Throwable) {
            return;
        }
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
        self::syncApprovedTariffs($pdo);
    }

    private static function syncApprovedTariffs(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'repair_tariffs')) {
            return;
        }
        self::ensureColumn($pdo, 'repair_tariffs', 'is_approved', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'repair_tariffs', 'includes_materials', 'TINYINT(1) NOT NULL DEFAULT 0');
        self::ensureColumn($pdo, 'repair_tariffs', 'service_slug', 'VARCHAR(160) NULL');
        $hasCategory = self::columnExists($pdo, 'repair_tariffs', 'category');
        $reset = !self::hasSetting($pdo, 'schema_prices_v2');
        foreach (StartingPrices::catalog() as $tariff) {
            $price = $tariff['price_per_m2'];
            try {
                if ($hasCategory && self::columnExists($pdo, 'repair_tariffs', 'service_slug')) {
                    $found = $pdo->prepare('SELECT id, is_approved FROM repair_tariffs WHERE service_slug = ? OR (category = ? AND repair_type = ? AND package_type = ?) LIMIT 1');
                    $found->execute([$tariff['slug'], $tariff['category'], $tariff['repair_type'], $tariff['package_type']]);
                    $row = $found->fetch() ?: null;
                    if ($row) {
                        $approved = $reset ? 0 : (int)$row['is_approved'];
                        $stmt = $pdo->prepare('UPDATE repair_tariffs SET repair_type=?, package_type=?, price_per_m2=?, note=?, category=?, sort_order=?, service_slug=?, includes_materials=?, is_approved=?, updated_at=NOW() WHERE id=?');
                        $stmt->execute([$tariff['repair_type'], $tariff['package_type'], $price, $tariff['note'], $tariff['category'], $tariff['sort_order'], $tariff['slug'], $tariff['includes_materials'], $approved, (int)$row['id']]);
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO repair_tariffs (repair_type, package_type, price_per_m2, note, is_active, sort_order, category, service_slug, includes_materials, is_approved, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, 0, NOW(), NOW())');
                        $stmt->execute([$tariff['repair_type'], $tariff['package_type'], $price, $tariff['note'], $tariff['sort_order'], $tariff['category'], $tariff['slug'], $tariff['includes_materials']]);
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }
        if ($reset) {
            self::touchSetting($pdo, 'schema_prices_v2', '1');
        }
    }

    private static function refreshHouseCopy(PDO $pdo): void
    {
        if (self::hasSetting($pdo, 'schema_house_copy_v2') || !self::tableExists($pdo, 'services')) {
            return;
        }
        try {
            $stmt = $pdo->prepare('UPDATE services SET title=?, short=?, body=?, image_path=?, image_alt=?, meta_title=?, meta_description=?, h1=?, sort_order=?, updated_at=NOW() WHERE slug=? AND category=\'house\'');
            foreach (HouseCatalog::services() as $service) {
                $stmt->execute([
                    $service['title'],
                    $service['short'],
                    $service['body'],
                    $service['image_path'],
                    $service['image_alt'],
                    $service['meta_title'],
                    $service['meta_description'],
                    $service['h1'],
                    (int)$service['sort_order'],
                    $service['slug'],
                ]);
            }
            $hub = HouseCatalog::hub();
            foreach ([
                'house_meta_title' => $hub['meta_title'],
                'house_meta_description' => $hub['meta_description'],
                'house_h1' => $hub['h1'],
                'house_intro' => $hub['intro'],
            ] as $key => $value) {
                self::touchSetting($pdo, $key, $value);
            }
            self::touchSetting($pdo, 'schema_house_copy_v2', '1');
        } catch (\Throwable) {
            return;
        }
    }

    private static function ensureCustomerFaqs(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'faqs') || self::hasSetting($pdo, 'schema_customer_faq_v2')) {
            return;
        }
        $seed = require dirname(__DIR__) . '/seed.php';
        try {
            $hasCategory = self::columnExists($pdo, 'faqs', 'category');
            $exists = $pdo->prepare('SELECT COUNT(*) FROM faqs WHERE question = ?');
            foreach (($seed['faqs'] ?? []) as $i => $faq) {
                $exists->execute([$faq['q']]);
                if ((int)$exists->fetchColumn() > 0) {
                    continue;
                }
                if ($hasCategory) {
                    $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, 1, ?, \'apartment\', NOW(), NOW())');
                } else {
                    $stmt = $pdo->prepare('INSERT INTO faqs (question, answer, is_published, sort_order, created_at, updated_at) VALUES (?, ?, 1, ?, NOW(), NOW())');
                }
                $stmt->execute([$faq['q'], $faq['a'], ($i + 1) * 10]);
            }
            self::touchSetting($pdo, 'schema_customer_faq_v2', '1');
        } catch (\Throwable) {
            return;
        }
    }

    private static function ensureWorkingHours(PDO $pdo): void
    {
        if (!self::hasSetting($pdo, 'working_hours')) {
            self::touchSetting($pdo, 'working_hours', 'ежедневно с 09:00 до 20:00');
        }
        if (!self::hasSetting($pdo, 'working_hours_note')) {
            self::touchSetting($pdo, 'working_hours_note', 'Часы приёма телефонных обращений, время московское.');
        }
    }

    private static function cleanupPublicFaqs(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'faqs')) {
            return;
        }
        try {
            $pdo->exec("DELETE FROM faqs WHERE question LIKE '%админ%' OR question LIKE '%SEO%' OR question LIKE '%Telegram%' OR question LIKE '%базе данных%' OR question LIKE '%изменить телефон%' OR question LIKE '%страницу под конкретный город%' OR question LIKE '%программ%'");
        } catch (\Throwable) {
            return;
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
