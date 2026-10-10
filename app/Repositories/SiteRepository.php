<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
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

    public function services(): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return $this->seed['services'];
        }
        try {
            $rows = $pdo->query('SELECT * FROM services WHERE is_published = 1 ORDER BY sort_order, id')->fetchAll();
            return $rows ?: $this->seed['services'];
        } catch (\Throwable) {
            return $this->seed['services'];
        }
    }

    public function service(string $slug): ?array
    {
        foreach ($this->services() as $service) {
            if (($service['slug'] ?? '') === $slug) {
                return $service;
            }
        }
        return null;
    }

    public function upsertService(array $data): void
    {
        $pdo = $this->requirePdo();
        if (!empty($data['id'])) {
            $stmt = $pdo->prepare('UPDATE services SET title=?, slug=?, short=?, body=?, price=?, image_path=?, image_alt=?, meta_title=?, meta_description=?, h1=?, is_published=?, sort_order=?, updated_at=NOW() WHERE id=?');
            $stmt->execute([$data['title'], $data['slug'], $data['short'], $data['body'], $this->nullableInt($data['price']), $data['image_path'], $data['image_alt'], $data['meta_title'], $data['meta_description'], $data['h1'], (int)!empty($data['is_published']), (int)$data['sort_order'], (int)$data['id']]);
            return;
        }
        $stmt = $pdo->prepare('INSERT INTO services (title, slug, short, body, price, image_path, image_alt, meta_title, meta_description, h1, is_published, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$data['title'], $data['slug'], $data['short'], $data['body'], $this->nullableInt($data['price']), $data['image_path'], $data['image_alt'], $data['meta_title'], $data['meta_description'], $data['h1'], (int)!empty($data['is_published']), (int)$data['sort_order']]);
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

    public function faqs(): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return $this->seed['faqs'];
        }
        try {
            $rows = $pdo->query('SELECT question AS q, answer AS a FROM faqs WHERE is_published = 1 ORDER BY sort_order, id')->fetchAll();
            $rows = $rows ? $this->publicFaqs($rows) : $this->seed['faqs'];
            return $rows;
        } catch (\Throwable) {
            return $this->seed['faqs'];
        }
    }

    public function advantages(): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return $this->seed['advantages'];
        }
        try {
            $rows = $pdo->query('SELECT title, text FROM advantages WHERE is_published = 1 ORDER BY sort_order, id')->fetchAll();
            return $rows ?: $this->seed['advantages'];
        } catch (\Throwable) {
            return $this->seed['advantages'];
        }
    }

    public function portfolio(): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return SiteImages::normalizePortfolio($this->seed['portfolio']);
        }
        try {
            $items = $pdo->query('SELECT * FROM portfolio_items WHERE is_published = 1 ORDER BY sort_order, id DESC')->fetchAll();
            foreach ($items as &$item) {
                $stmt = $pdo->prepare('SELECT * FROM portfolio_images WHERE portfolio_id = ? ORDER BY sort_order, id');
                $stmt->execute([(int)$item['id']]);
                $item['images'] = $stmt->fetchAll();
            }
            unset($item);
            return SiteImages::normalizePortfolio($items ?: $this->seed['portfolio']);
        } catch (\Throwable) {
            return SiteImages::normalizePortfolio($this->seed['portfolio']);
        }
    }

    public function tariffs(): array
    {
        $pdo = $this->pdo();
        if (!$pdo) {
            return [];
        }
        try {
            return $pdo->query('SELECT * FROM repair_tariffs WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
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
        return $pdo->query('SELECT * FROM leads ORDER BY created_at DESC LIMIT 200')->fetchAll();
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

    private function nullableInt(mixed $value): ?int
    {
        if ($value === '' || $value === null) {
            return null;
        }
        return (int)$value;
    }
}
