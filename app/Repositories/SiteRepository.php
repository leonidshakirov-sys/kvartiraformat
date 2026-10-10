<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\CommercialCatalog;
use App\Support\SiteImages;
use PDO;

final class SiteRepository
{
    private array $seed;

    public function __construct(private Database $db)
    {
        $this->seed = require __DIR__ . '/../seed.php';
    }

    public function dbError(): ?string
    {
        return $this->db->error();
    }

    public function pdo(): ?PDO
    {
        return $this->db->pdo();
    }

    public function settings(): array
    {
        $settings = $this->seed['settings'];
        $pdo = $this->pdo();
        if (!$pdo) {
            return $settings;
        }
        try {
            foreach ($pdo->query('SELECT setting_key, setting_value FROM settings') as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Throwable) {
            return $settings;
        }
        return $settings;
    }

    public function updateSettings(array $values): void
    {
        $pdo = $this->requirePdo();
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
        foreach ($values as $key => $value) {
            $stmt->execute([$key, is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value]);
        }
    }

    public function services(?string $category = 'apartment', bool $publishedOnly = true): array
    {
        $fallback = $category === 'commercial' ? CommercialCatalog::services() : $this->seed['services'];
        $pdo = $this->pdo();
        if (!$pdo) {
            return $fallback;
        }
        try {
            $where = [];
            $params = [];
            if ($publishedOnly) {
                $where[] = 'is_published = 1';
            }
            if ($this->hasColumn('services', 'category')) {
                if ($category === 'commercial') {
                    $where[] = 'category = ?';
                    $params[] = 'commercial';
                } else {
                    $where[] = "(category = 'apartment' OR category IS NULL OR category = '')";
                }
            } elseif ($category === 'commercial') {
                return $fallback;
            }
            $sql = 'SELECT * FROM services' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY sort_order, id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            if ($category === 'commercial') {
                return $this->mergeCommercialRows($rows ?: [], $publishedOnly);
            }
            return $rows ?: ($publishedOnly ? $fallback : $fallback);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public function service(string $slug): ?array
    {
        foreach ($this->services('apartment') as $service) {
            if (($service['slug'] ?? '') === $slug) {
                return $service;
            }
        }
        return null;
    }

    public function commercialService(string $slug, bool $publishedOnly = true): ?array
    {
        $catalog = CommercialCatalog::service($slug);
        if (!$catalog) {
            return null;
        }
        $pdo = $this->pdo();
        if (!$pdo) {
            return $catalog;
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM services WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
            $row = $stmt->fetch() ?: null;
            if ($row && $this->hasColumn('services', 'category') && ($row['category'] ?? '') !== 'commercial') {
                return null;
            }
            $merged = CommercialCatalog::merge($row ?: null, $catalog);
            if ($publishedOnly && isset($merged['is_published']) && (int)$merged['is_published'] === 0) {
                return null;
            }
            return $merged;
        } catch (\Throwable) {
            return $publishedOnly ? $catalog : $catalog;
        }
    }

    public function upsertService(array $data): void
    {
        $pdo = $this->requirePdo();
        $category = in_array(($data['category'] ?? ''), ['commercial', 'apartment'], true) ? $data['category'] : 'apartment';
        $hasCategory = $this->hasColumn('services', 'category');
        if (!empty($data['id'])) {
            if ($hasCategory) {
                $stmt = $pdo->prepare('UPDATE services SET title=?, slug=?, short=?, body=?, price=?, image_path=?, image_alt=?, meta_title=?, meta_description=?, h1=?, is_published=?, sort_order=?, category=?, updated_at=NOW() WHERE id=?');
                $stmt->execute([$data['title'], $data['slug'], $data['short'], $data['body'], $this->nullableInt($data['price'] ?? null), $data['image_path'] ?? '', $data['image_alt'] ?? '', $data['meta_title'] ?? '', $data['meta_description'] ?? '', $data['h1'] ?? '', (int)!empty($data['is_published']), (int)($data['sort_order'] ?? 100), $category, (int)$data['id']]);
                return;
            }
            $stmt = $pdo->prepare('UPDATE services SET title=?, slug=?, short=?, body=?, price=?, image_path=?, image_alt=?, meta_title=?, meta_description=?, h1=?, is_published=?, sort_order=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$data['title'], $data['slug'], $data['short'], $data['body'], $this->nullableInt($data['price'] ?? null), $data['image_path'] ?? '', $data['image_alt'] ?? '', $data['meta_title'] ?? '', $data['meta_description'] ?? '', $data['h1'] ?? '', (int)!empty($data['is_published']), (int)($data['sort_order'] ?? 100), (int)$data['id']]);
            return;
        }
        if ($hasCategory) {
            $stmt = $pdo->prepare('INSERT INTO services (title, slug, short, body, price, image_path, image_alt, meta_title, meta_description, h1, is_published, sort_order, category, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $stmt->execute([$data['title'], $data['slug'], $data['short'], $data['body'], $this->nullableInt($data['price'] ?? null), $data['image_path'] ?? '', $data['image_alt'] ?? '', $data['meta_title'] ?? '', $data['meta_description'] ?? '', $data['h1'] ?? '', (int)!empty($data['is_published']), (int)($data['sort_order'] ?? 100), $category]);
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO services (title, slug, short, body, price, image_path, image_alt, meta_title, meta_description, h1, is_published, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$data['title'], $data['slug'], $data['short'], $data['body'], $this->nullableInt($data['price'] ?? null), $data['image_path'] ?? '', $data['image_alt'] ?? '', $data['meta_title'] ?? '', $data['meta_description'] ?? '', $data['h1'] ?? '', (int)!empty($data['is_published']), (int)($data['sort_order'] ?? 100)]);
    }

    public function geoPages(bool $publishedOnly = true): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return $this->seed['geo'];
        }
        try {
            $where = $publishedOnly ? 'WHERE is_published = 1' : '';
            $rows = $pdo->query("SELECT * FROM geo_pages $where ORDER BY priority, name")->fetchAll();
            return $rows ?: $this->seed['geo'];
        } catch (\Throwable) {
            return $this->seed['geo'];
        }
    }

    public function geoPage(string $slug): ?array
    {
        foreach ($this->geoPages(true) as $page) {
            if (($page['slug'] ?? '') === $slug) {
                return $page;
            }
        }
        return null;
    }

    public function upsertGeo(array $data): void
    {
        $pdo = $this->requirePdo();
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare('UPDATE geo_pages SET name=?, slug=?, title=?, description=?, h1=?, body=?, priority=?, is_published=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$data['name'], $data['slug'], $data['title'], $data['description'], $data['h1'], $data['body'], (int)$data['priority'], (int)!empty($data['is_published']), (int)$data['id']]);
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO geo_pages (name, slug, title, description, h1, body, priority, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$data['name'], $data['slug'], $data['title'], $data['description'], $data['h1'], $data['body'], (int)$data['priority'], (int)!empty($data['is_published'])]);
    }

    public function faqs(?string $category = 'apartment'): array
    {
        $fallback = $category === 'commercial' ? CommercialCatalog::faqs() : $this->seed['faqs'];
        $pdo = $this->pdo();
        if (!$pdo) {
            return $fallback;
        }
        try {
            if ($this->hasColumn('faqs', 'category')) {
                $stmt = $pdo->prepare("SELECT question AS q, answer AS a FROM faqs WHERE is_published = 1 AND (category = ? OR category = 'all') ORDER BY sort_order, id");
                $stmt->execute([$category]);
                $rows = $stmt->fetchAll();
            } else {
                $rows = $category === 'commercial' ? [] : $pdo->query('SELECT question AS q, answer AS a FROM faqs WHERE is_published = 1 ORDER BY sort_order, id')->fetchAll();
            }
            if (!$rows) {
                return $fallback;
            }
            return $category === 'apartment' ? $this->publicFaqs($rows) : $rows;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public function advantages(?string $category = 'apartment'): array
    {
        $fallback = $category === 'commercial' ? CommercialCatalog::advantages() : $this->seed['advantages'];
        $pdo = $this->pdo();
        if (!$pdo) {
            return $fallback;
        }
        try {
            if ($this->hasColumn('advantages', 'category')) {
                $stmt = $pdo->prepare("SELECT title, text FROM advantages WHERE is_published = 1 AND (category = ? OR category = 'all') ORDER BY sort_order, id");
                $stmt->execute([$category]);
                $rows = $stmt->fetchAll();
            } else {
                $rows = $category === 'commercial' ? [] : $pdo->query('SELECT title, text FROM advantages WHERE is_published = 1 ORDER BY sort_order, id')->fetchAll();
            }
            return $rows ?: $fallback;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public function portfolio(?string $category = 'apartment'): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return $category === 'commercial' ? [] : SiteImages::normalizePortfolio($this->seed['portfolio']);
        }
        try {
            if ($this->hasColumn('portfolio_items', 'category')) {
                $stmt = $pdo->prepare('SELECT * FROM portfolio_items WHERE is_published = 1 AND category = ? ORDER BY sort_order, id DESC');
                $stmt->execute([$category]);
                $items = $stmt->fetchAll();
            } else {
                if ($category === 'commercial') {
                    return [];
                }
                $items = $pdo->query('SELECT * FROM portfolio_items WHERE is_published = 1 ORDER BY sort_order, id DESC')->fetchAll();
            }
            foreach ($items as &$item) {
                $stmt = $pdo->prepare('SELECT * FROM portfolio_images WHERE portfolio_id = ? ORDER BY sort_order, id');
                $stmt->execute([(int)$item['id']]);
                $item['images'] = $stmt->fetchAll();
            }
            unset($item);
            if ($category === 'commercial') {
                return $items ?: [];
            }
            return SiteImages::normalizePortfolio($items ?: $this->seed['portfolio']);
        } catch (\Throwable) {
            return $category === 'commercial' ? [] : SiteImages::normalizePortfolio($this->seed['portfolio']);
        }
    }

    public function tariffs(?string $category = 'apartment'): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return [];
        }
        try {
            if ($this->hasColumn('repair_tariffs', 'category')) {
                $stmt = $pdo->prepare('SELECT * FROM repair_tariffs WHERE is_active = 1 AND category = ? ORDER BY sort_order, id');
                $stmt->execute([$category]);
                return $stmt->fetchAll();
            }
            return $category === 'commercial' ? [] : $pdo->query('SELECT * FROM repair_tariffs WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function pages(): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return [];
        }
        try {
            return $pdo->query('SELECT * FROM pages ORDER BY updated_at DESC, id DESC')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function page(string $slug): ?array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_published = 1 LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }

    public function upsertPage(array $data): void
    {
        $pdo = $this->requirePdo();
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare('UPDATE pages SET title=?, slug=?, meta_title=?, meta_description=?, h1=?, body=?, canonical_url=?, robots_index=?, is_published=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$data['title'], $data['slug'], $data['meta_title'], $data['meta_description'], $data['h1'], $data['body'], $data['canonical_url'], (int)!empty($data['robots_index']), (int)!empty($data['is_published']), (int)$data['id']]);
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO pages (title, slug, meta_title, meta_description, h1, body, canonical_url, robots_index, is_published, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$data['title'], $data['slug'], $data['meta_title'], $data['meta_description'], $data['h1'], $data['body'], $data['canonical_url'], (int)!empty($data['robots_index']), (int)!empty($data['is_published'])]);
    }

    public function media(): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return [];
        }
        try {
            return $pdo->query('SELECT * FROM media ORDER BY id DESC LIMIT 80')->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function attachPortfolioImage(int $portfolioId, string $path, string $alt = '', string $kind = 'gallery'): void
    {
        $stmt = $this->requirePdo()->prepare('INSERT INTO portfolio_images (portfolio_id, file_path, kind, alt_text, sort_order, created_at) VALUES (?, ?, ?, ?, 100, NOW())');
        $stmt->execute([$portfolioId, $path, $kind, $alt]);
    }

    public function leads(array $filters = []): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return [];
        }
        $type = (string)($filters['lead_type'] ?? '');
        try {
            if (in_array($type, ['apartment', 'commercial'], true) && $this->hasColumn('leads', 'lead_type')) {
                $stmt = $pdo->prepare('SELECT * FROM leads WHERE lead_type = ? ORDER BY created_at DESC LIMIT 200');
                $stmt->execute([$type]);
                $rows = $stmt->fetchAll();
            } else {
                $rows = $pdo->query('SELECT * FROM leads ORDER BY created_at DESC LIMIT 200')->fetchAll();
            }
            foreach ($rows as &$row) {
                $row['files'] = $this->leadFiles((int)$row['id']);
            }
            unset($row);
            return $rows;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function leadFiles(int $leadId): array
    {
        $pdo = $this->pdo();
        if (!$pdo || $leadId <= 0 || !self::tableExists($pdo, 'lead_files')) {
            return [];
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM lead_files WHERE lead_id = ? ORDER BY id');
            $stmt->execute([$leadId]);
            return $stmt->fetchAll();
        } catch (\Throwable) {
            return [];
        }
    }

    public function leadFile(int $id): ?array
    {
        $pdo = $this->pdo();
        if (!$pdo || $id <= 0 || !self::tableExists($pdo, 'lead_files')) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM lead_files WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function attachLeadFile(int $leadId, array $file): void
    {
        $stmt = $this->requirePdo()->prepare('INSERT INTO lead_files (lead_id, stored_name, original_name, mime_type, size_bytes, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$leadId, $file['stored_name'], $file['original_name'], $file['mime_type'] ?? '', (int)($file['size_bytes'] ?? 0)]);
    }

    public function dashboardStats(): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return ['total' => 0, 'new' => 0, 'week' => 0, 'telegram_pending' => 0];
        }
        return [
            'total' => (int)$pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn(),
            'new' => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'new'")->fetchColumn(),
            'week' => (int)$pdo->query('SELECT COUNT(*) FROM leads WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchColumn(),
            'telegram_pending' => (int)$pdo->query("SELECT COUNT(*) FROM telegram_queue WHERE status IN ('pending','failed')")->fetchColumn(),
        ];
    }

    public function deleteById(string $table, int $id): void
    {
        $allowed = ['pages', 'services', 'geo_pages', 'faqs', 'advantages', 'portfolio_items', 'media'];
        if (!in_array($table, $allowed, true)) {
            throw new \InvalidArgumentException('Недопустимая таблица');
        }
        $this->requirePdo()->prepare("DELETE FROM $table WHERE id = ?")->execute([$id]);
    }

    private function requirePdo(): PDO
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            throw new \RuntimeException($this->db->error() ?: 'Нет подключения к базе данных');
        }
        return $pdo;
    }

    /**
     * @param list<array{q:string,a:string}> $rows
     * @return list<array{q:string,a:string}>
     */
    private function publicFaqs(array $rows): array
    {
        foreach ($rows as &$row) {
            $question = (string)($row['q'] ?? '');
            $answer = (string)($row['a'] ?? '');
            $best = null;
            $bestScore = 0.0;
            foreach ($this->seed['faqs'] as $faq) {
                similar_text($question, (string)$faq['q'], $percent);
                if ($percent > $bestScore) {
                    $bestScore = $percent;
                    $best = (string)$faq['a'];
                }
            }
            if ($best && ($bestScore >= 55 || preg_match('/админ/ui', $answer))) {
                $row['a'] = $best;
            }
        }
        unset($row);
        return $rows;
    }

    /**
     * @param list<array<string,mixed>> $rows
     * @return list<array<string,mixed>>
     */
    private function mergeCommercialRows(array $rows, bool $publishedOnly): array
    {
        $bySlug = [];
        foreach ($rows as $row) {
            $bySlug[(string)$row['slug']] = $row;
        }
        $merged = [];
        foreach (CommercialCatalog::services() as $catalog) {
            $row = $bySlug[$catalog['slug']] ?? null;
            $item = CommercialCatalog::merge($row, $catalog);
            if ($publishedOnly && (int)($item['is_published'] ?? 1) === 0) {
                continue;
            }
            $merged[] = $item;
            unset($bySlug[$catalog['slug']]);
        }
        foreach ($bySlug as $row) {
            if ($publishedOnly && (int)($row['is_published'] ?? 1) === 0) {
                continue;
            }
            $merged[] = $row;
        }
        usort($merged, static fn($a, $b) => ((int)($a['sort_order'] ?? 100)) <=> ((int)($b['sort_order'] ?? 100)));
        return $merged;
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $pdo = $this->pdo();
        if (!$pdo) {
            return $cache[$key] = false;
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return $cache[$key] = (int)$stmt->fetchColumn() > 0;
    }

    private static function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === '' || $value === null) {
            return null;
        }
        return (int)$value;
    }
}
