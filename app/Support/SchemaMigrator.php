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
        self::ensureCosmeticPhoto($pdo);
        self::ensureCapitalPhoto($pdo);
        self::ensurePortfolioWorks($pdo);
        self::ensureQuiz($pdo);

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

    private static function ensureQuiz(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'leads')) {
            return;
        }
        $fresh = !self::hasSetting($pdo, 'schema_quiz_v1');
        if ($fresh) {
            self::backup($pdo);
        }
        self::ensureColumn($pdo, 'leads', 'quiz_payload', 'MEDIUMTEXT NULL');
        if ($fresh) {
            self::touchSetting($pdo, 'schema_quiz_v1', '1');
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

    private static function ensureCosmeticPhoto(PDO $pdo): void
    {
        if (self::hasSetting($pdo, 'schema_cosmetic_photo_v1') || !self::tableExists($pdo, 'services')) {
            return;
        }
        try {
            $stmt = $pdo->prepare('UPDATE services SET image_path = ?, image_alt = ?, updated_at = NOW() WHERE slug = ?');
            $stmt->execute([
                '/assets/images/services/cosmetic.webp',
                'Мастер в форме КвартираФормат красит стену валиком при косметическом ремонте',
                'kosmeticheskiy-remont',
            ]);
            $stmt->execute([
                '/assets/images/houses/cosmetic.webp',
                'Мастер в форме КвартираФормат красит стену валиком при косметическом ремонте дома',
                'kosmeticheskiy',
            ]);
            self::touchSetting($pdo, 'schema_cosmetic_photo_v1', '1');
        } catch (\Throwable) {
            return;
        }
    }

    private static function ensureCapitalPhoto(PDO $pdo): void
    {
        if (self::hasSetting($pdo, 'schema_capital_photo_v1') || !self::tableExists($pdo, 'services')) {
            return;
        }
        try {
            $stmt = $pdo->prepare('UPDATE services SET image_path = ?, image_alt = ?, updated_at = NOW() WHERE slug = ?');
            $stmt->execute([
                '/assets/images/services/capital.webp',
                'Мастер в форме КвартираФормат штукатурит стену при капитальном ремонте',
                'kapitalnyy-remont',
            ]);
            $stmt->execute([
                '/assets/images/houses/capital.webp',
                'Мастер в форме КвартираФормат штукатурит стену при капитальном ремонте дома',
                'kapitalnyy',
            ]);
            self::touchSetting($pdo, 'schema_capital_photo_v1', '1');
        } catch (\Throwable) {
            return;
        }
    }

    private static function ensurePortfolioWorks(PDO $pdo): void
    {
        if (self::hasSetting($pdo, 'schema_portfolio_works_v1') || !self::tableExists($pdo, 'portfolio_items')) {
            return;
        }
        try {
            $pdo->exec("UPDATE portfolio_items SET is_published = 0, updated_at = NOW() WHERE title LIKE '%Демонстрацион%'");
            $rows = $pdo->query('SELECT id, works FROM portfolio_items')->fetchAll();
            $update = $pdo->prepare('UPDATE portfolio_items SET works = ?, is_demo = 0, updated_at = NOW() WHERE id = ?');
            foreach ($rows as $row) {
                $clean = portfolio_public_works((string)($row['works'] ?? ''));
                if ($clean !== trim((string)($row['works'] ?? ''))) {
                    $update->execute([$clean, (int)$row['id']]);
                }
            }
            self::touchSetting($pdo, 'schema_portfolio_works_v1', '1');
        } catch (\Throwable) {
            return;
        }
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
        self::ensureColumn($pdo, 'repair_tariffs', 'price_unit', "VARCHAR(16) NOT NULL DEFAULT 'm2'");
        self::ensureColumn($pdo, 'repair_tariffs', 'approved_at', 'DATETIME NULL');
        if (!self::columnExists($pdo, 'repair_tariffs', 'category')) {
            return;
        }

        $publish = !self::hasSetting($pdo, 'schema_prices_approved_v3');
        $kept = [];
        $findBySlug = $pdo->prepare('SELECT id FROM repair_tariffs WHERE category = ? AND service_slug = ? ORDER BY id LIMIT 1');
        $findByType = $pdo->prepare('SELECT id FROM repair_tariffs WHERE category = ? AND repair_type = ? AND package_type = ? ORDER BY id LIMIT 1');
        $update = $pdo->prepare('UPDATE repair_tariffs SET repair_type=?, package_type=?, price_per_m2=?, note=?, category=?, sort_order=?, service_slug=?, includes_materials=?, is_approved=1, price_unit=?, is_active=1, approved_at=COALESCE(approved_at, NOW()), updated_at=NOW() WHERE id=?');
        $insert = $pdo->prepare('INSERT INTO repair_tariffs (repair_type, package_type, price_per_m2, note, is_active, sort_order, category, service_slug, includes_materials, is_approved, price_unit, approved_at, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, 1, ?, NOW(), NOW(), NOW())');

        foreach (StartingPrices::catalog() as $tariff) {
            try {
                $findBySlug->execute([$tariff['category'], $tariff['slug']]);
                $id = (int)($findBySlug->fetchColumn() ?: 0);
                if ($id === 0) {
                    $findByType->execute([$tariff['category'], $tariff['repair_type'], $tariff['package_type']]);
                    $id = (int)($findByType->fetchColumn() ?: 0);
                }
                if ($id > 0) {
                    if ($publish) {
                        $update->execute([
                            $tariff['repair_type'],
                            $tariff['package_type'],
                            $tariff['price_per_m2'],
                            $tariff['note'],
                            $tariff['category'],
                            $tariff['sort_order'],
                            $tariff['slug'],
                            $tariff['includes_materials'],
                            $tariff['unit'],
                            $id,
                        ]);
                    }
                    $kept[] = $id;
                    $hide = $pdo->prepare('UPDATE repair_tariffs SET is_active = 0, updated_at = NOW() WHERE category = ? AND id <> ? AND is_active = 1 AND (service_slug = ? OR (repair_type = ? AND package_type = ?))');
                    $hide->execute([$tariff['category'], $id, $tariff['slug'], $tariff['repair_type'], $tariff['package_type']]);
                } else {
                    $insert->execute([
                        $tariff['repair_type'],
                        $tariff['package_type'],
                        $tariff['price_per_m2'],
                        $tariff['note'],
                        $tariff['sort_order'],
                        $tariff['category'],
                        $tariff['slug'],
                        $tariff['includes_materials'],
                        $tariff['unit'],
                    ]);
                    $kept[] = (int)$pdo->lastInsertId();
                }
            } catch (\Throwable) {
                continue;
            }
        }

        if ($publish && $kept) {
            $placeholders = implode(',', array_fill(0, count($kept), '?'));
            $retire = $pdo->prepare("UPDATE repair_tariffs SET is_active = 0, updated_at = NOW() WHERE category IN ('apartment','house','commercial') AND id NOT IN ({$placeholders}) AND (service_slug IS NULL OR service_slug = '' OR service_slug NOT IN (" . implode(',', array_fill(0, count(StartingPrices::catalog()), '?')) . '))');
            $slugs = array_map(static fn(array $item): string => $item['slug'], StartingPrices::catalog());
            $retire->execute(array_merge($kept, $slugs));
            self::touchSetting($pdo, 'schema_prices_v2', '1');
            self::touchSetting($pdo, 'schema_prices_approved_v3', '1');
            self::refreshPublishedPriceCopy($pdo);
        }
    }

    private static function refreshPublishedPriceCopy(PDO $pdo): void
    {
        try {
            if (self::tableExists($pdo, 'services')) {
                $stmt = $pdo->prepare('UPDATE services SET meta_description = ? WHERE slug = ?');
                $stmt->execute(['Ремонт санузлов в частных домах: плитка, гидроизоляция и сантехника. Стартовая цена работ без материалов — за помещение.', 'sanuzlov']);
                $stmt->execute(['Внутренняя отделка помещений общественного питания в Москве и МО. Стартовая цена работ без материалов указана на странице.', 'remont-kafe-restoranov']);
                $pdo->exec("UPDATE services SET body = REPLACE(body, 'Поэтому мы не публикуем универсальную цену за м² склада.', 'Стартовая цена работ без материалов указана в карточке услуги.') WHERE slug = 'remont-skladskih-pomescheniy'");
            }
            if (self::tableExists($pdo, 'faqs')) {
                $faq = $pdo->prepare('UPDATE faqs SET answer = ?, updated_at = NOW() WHERE question = ?');
                $faq->execute(['Ремонт дома считаем отдельно от квартиры: учитываем этажность, площадь и состояние отделки. На сайте опубликована стартовая цена работ без материалов, точная смета — после осмотра.', 'Сколько стоит ремонт дома?']);
                $faq->execute(['Для коммерции важны назначение помещения, площадь, состояние и техническое задание. На сайте указана стартовая цена работ без материалов, точная смета — после осмотра.', 'Как рассчитывается стоимость коммерческого ремонта?']);
            }
        } catch (\Throwable) {
            return;
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
