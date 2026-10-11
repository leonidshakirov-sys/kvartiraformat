<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\SiteRepository;
use App\Support\CommercialCatalog;
use App\Support\HouseCatalog;
use PDO;

final class LeadService
{
    public function __construct(
        private Database $db,
        private Config $config,
        private SiteRepository $repo,
        private TelegramService $telegram,
        private ?UploadService $uploads = null,
        private ?MailNotifyService $mail = null
    ) {
    }

    public function submit(array $data, Request $request, array $files = []): array
    {
        $files = $this->normalizeIncoming($files);
        $errors = $this->validate($data, $files);
        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }
        $pdo = $this->db->pdo();
        if (!$pdo) {
            return ['ok' => false, 'errors' => ['Сервис заявок временно недоступен: база данных не настроена.']];
        }

        $idempotency = trim((string)($data['form_uid'] ?? '')) ?: bin2hex(random_bytes(16));
        $dupe = $this->duplicateExists($data, $idempotency);
        if ($dupe) {
            return ['ok' => true, 'lead_id' => $dupe, 'duplicate' => true];
        }

        $leadType = $this->leadType($data);
        if (trim((string)($data['premise_condition'] ?? '')) === '' && trim((string)($data['house_condition'] ?? '')) !== '') {
            $data['premise_condition'] = trim((string)$data['house_condition']);
        }
        $special = trim((string)($data['special_requirements'] ?? ''));
        if ($special !== '') {
            $data['comment'] = trim((string)($data['comment'] ?? '') . "\nОсобые требования: " . $special);
        }
        $estimate = $this->estimate($data);
        $hasLeadType = $this->hasLeadExtraColumns($pdo);
        if ($hasLeadType) {
            $stmt = $pdo->prepare('INSERT INTO leads (public_id, name, phone, city, repair_type, property_type, area, rooms, package_type, extras, comment, estimate_text, page_url, source, utm_source, utm_medium, utm_campaign, utm_content, utm_term, referrer, ip_address, user_agent, consent_version, consent_text, status, idempotency_key, lead_type, work_start, has_project, premise_condition, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "new", ?, ?, ?, ?, ?, NOW(), NOW())');
        } else {
            $stmt = $pdo->prepare('INSERT INTO leads (public_id, name, phone, city, repair_type, property_type, area, rooms, package_type, extras, comment, estimate_text, page_url, source, utm_source, utm_medium, utm_campaign, utm_content, utm_term, referrer, ip_address, user_agent, consent_version, consent_text, status, idempotency_key, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "new", ?, NOW(), NOW())');
        }
        $publicId = 'KF-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $settings = $this->repo->settings();
        $consentVersion = $settings['privacy_version'] ?? date('Y-m-d');
        $consentText = 'Пользователь согласился на обработку персональных данных и ознакомился с политикой. Версия: ' . $consentVersion;
        $params = [
            $publicId,
            trim((string)$data['name']),
            trim((string)$data['phone']),
            trim((string)($data['city'] ?? '')),
            trim((string)($data['repair_type'] ?? ($data['premise_condition'] ?? ''))),
            trim((string)($data['property_type'] ?? '')),
            $this->nullableFloat($data['area'] ?? null),
            trim((string)($data['rooms'] ?? ($data['floors'] ?? ''))),
            trim((string)($data['package_type'] ?? '')),
            $this->cleanExtras($data['extras'] ?? []),
            trim((string)($data['comment'] ?? '')),
            $estimate,
            substr((string)($data['page_url'] ?? ($_SERVER['REQUEST_URI'] ?? '/')), 0, 500),
            substr((string)($data['source'] ?? 'site'), 0, 120),
            substr((string)($data['utm_source'] ?? ''), 0, 120),
            substr((string)($data['utm_medium'] ?? ''), 0, 120),
            substr((string)($data['utm_campaign'] ?? ''), 0, 180),
            substr((string)($data['utm_content'] ?? ''), 0, 180),
            substr((string)($data['utm_term'] ?? ''), 0, 180),
            $request->referer(),
            $request->ip(),
            $request->userAgent(),
            $consentVersion,
            $consentText,
            $idempotency,
        ];
        if ($hasLeadType) {
            $params[] = $leadType;
            $params[] = trim((string)($data['work_start'] ?? ''));
            $params[] = trim((string)($data['has_project'] ?? ''));
            $params[] = trim((string)($data['premise_condition'] ?? ''));
        }
        $stmt->execute($params);
        $leadId = (int)$pdo->lastInsertId();

        $fileCount = 0;
        if ($this->uploads && $files) {
            foreach ($files as $file) {
                $stored = $this->uploads->handlePrivateDocument($file);
                if (!empty($stored['stored_name'])) {
                    try {
                        $this->repo->attachLeadFile($leadId, $stored);
                        $fileCount++;
                    } catch (\Throwable) {
                        $this->uploads->deleteStored([(string)$stored['stored_name']]);
                    }
                }
            }
        }

        $message = $this->telegramMessage($leadId, $publicId, $data, $estimate, $leadType, $fileCount);
        $this->telegram->queue($leadId, $message);
        $delivery = $this->telegram->sendPending(1);
        if (($delivery['sent'] ?? 0) < 1 && $this->mail) {
            $this->mail->sendLeadCopy(
                'КвартираФормат — новая заявка ' . $publicId,
                strip_tags(str_replace('</b>', '', str_replace('<b>', '', $message)))
            );
        }

        return ['ok' => true, 'lead_id' => $leadId, 'public_id' => $publicId, 'estimate' => $estimate];
    }

    public function estimate(array $data): string
    {
        $leadType = $this->leadType($data);
        if ($leadType === 'house') {
            return $this->pricedEstimate($data, 'house') ?? HouseCatalog::INDIVIDUAL_ESTIMATE;
        }
        if ($leadType === 'commercial') {
            return $this->pricedEstimate($data, 'commercial') ?? CommercialCatalog::INDIVIDUAL_ESTIMATE;
        }
        return $this->pricedEstimate($data, 'apartment') ?? 'Индивидуальный расчёт';
    }

    private function pricedEstimate(array $data, string $category): ?string
    {
        $area = $this->nullableFloat($data['area'] ?? null);
        if (!$area || $area <= 0) {
            return null;
        }
        $repairType = mb_strtolower(trim((string)($data['repair_type'] ?? $data['premise_condition'] ?? '')));
        $package = mb_strtolower(trim((string)($data['package_type'] ?? 'Только работы')));
        foreach ($this->repo->tariffs($category) as $tariff) {
            if ((int)($tariff['is_approved'] ?? 0) !== 1 || (int)($tariff['price_per_m2'] ?? 0) <= 0) {
                continue;
            }
            $sameRepair = $repairType === '' || mb_strtolower((string)$tariff['repair_type']) === $repairType;
            $samePackage = $package === '' || mb_strtolower((string)$tariff['package_type']) === $package;
            if ($sameRepair && $samePackage) {
                $total = (int)round($area * (int)$tariff['price_per_m2']);
                return 'Предварительно от ' . number_format($total, 0, ',', ' ') . ' ₽';
            }
        }
        return null;
    }

    private function commercialEstimate(array $data): string
    {
        $area = $this->nullableFloat($data['area'] ?? null);
        $tariffs = $this->repo->tariffs('commercial');
        if (!$area || $area <= 0 || !$tariffs) {
            return CommercialCatalog::INDIVIDUAL_ESTIMATE;
        }
        $repairType = mb_strtolower(trim((string)($data['repair_type'] ?? $data['premise_condition'] ?? '')));
        $package = mb_strtolower(trim((string)($data['package_type'] ?? '')));
        foreach ($tariffs as $tariff) {
            $sameRepair = $repairType === '' || mb_strtolower((string)$tariff['repair_type']) === $repairType;
            $samePackage = $package === '' || mb_strtolower((string)$tariff['package_type']) === $package;
            if ($sameRepair && $samePackage && (int)$tariff['price_per_m2'] > 0) {
                $total = (int)round($area * (int)$tariff['price_per_m2']);
                return 'Предварительно от ' . number_format($total, 0, ',', ' ') . ' ₽';
            }
        }
        return CommercialCatalog::INDIVIDUAL_ESTIMATE;
    }

    private function validate(array $data, array $files = []): array
    {
        $errors = [];
        $leadType = $this->leadType($data);
        if (!empty($data['website'])) {
            $errors[] = 'Форма отклонена антиспам-защитой.';
        }
        if (mb_strlen(trim((string)($data['name'] ?? ''))) < 2) {
            $errors[] = 'Укажите имя.';
        }
        $phone = preg_replace('/[^0-9+]/', '', (string)($data['phone'] ?? ''));
        if (mb_strlen((string)$phone) < 10 || mb_strlen((string)$phone) > 18) {
            $errors[] = 'Укажите корректный телефон.';
        }
        if (empty($data['privacy_consent'])) {
            $errors[] = 'Необходимо согласие на обработку персональных данных.';
        }
        $maxArea = $leadType === 'apartment' ? 1000 : 20000;
        if (isset($data['area']) && $data['area'] !== '' && (!$this->nullableFloat($data['area']) || (float)$data['area'] <= 0 || (float)$data['area'] > $maxArea)) {
            $errors[] = $leadType === 'apartment' ? 'Проверьте площадь квартиры.' : 'Проверьте площадь объекта.';
        }
        if ($this->uploads && $files) {
            $check = $this->uploads->validatePrivateDocuments($files);
            if (empty($check['ok'])) {
                $errors[] = $check['error'] ?? 'Не удалось прикрепить файлы.';
            }
        }
        return $errors;
    }

    private function duplicateExists(array $data, string $idempotency): ?int
    {
        $pdo = $this->db->pdo();
        if (!$pdo) {
            return null;
        }
        $minutes = (int)$this->config->get('security.lead_duplicate_window_minutes', 15);
        $stmt = $pdo->prepare('SELECT id FROM leads WHERE idempotency_key = ? OR (phone = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)) ORDER BY id DESC LIMIT 1');
        $stmt->execute([$idempotency, trim((string)($data['phone'] ?? '')), $minutes]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    private function telegramMessage(int $leadId, string $publicId, array $data, string $estimate, string $leadType, int $fileCount): string
    {
        $direction = match ($leadType) {
            'house' => 'ремонт дома',
            'commercial' => 'коммерческий ремонт',
            default => 'ремонт квартиры',
        };
        $adminUrl = 'https://kvartiraformat.ru/admin/';
        $header = 'КвартираФормат — новая заявка №' . $leadId . ".\n"
            . 'Направление: ' . $direction . ".\n"
            . 'Прикреплено файлов: ' . $fileCount . ".\n"
            . 'Просмотреть заявку: ' . $adminUrl;
        $line = static fn(string $label, mixed $value): string => htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ': ' . htmlspecialchars(trim((string)$value) !== '' ? trim((string)$value) : 'не указано', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $header . "\n\n"
            . $line('ID', $publicId) . "\n"
            . $line('Имя', $data['name'] ?? '') . "\n"
            . $line('Телефон', $data['phone'] ?? '') . "\n"
            . $line('Город или адрес', $data['city'] ?? '') . "\n"
            . $line('Объект', $data['property_type'] ?? '') . "\n"
            . $line('Вид работ', $data['repair_type'] ?? ($data['premise_condition'] ?? '')) . "\n"
            . $line('Площадь', ($data['area'] ?? '') !== '' ? ($data['area'] . ' м²') : '') . "\n"
            . $line('Ориентир', $estimate) . "\n"
            . $line('Страница', $data['page_url'] ?? '/');
    }

    private function cleanExtras(mixed $extras): string
    {
        if (is_array($extras)) {
            return implode(', ', array_map(static fn($v) => trim(strip_tags((string)$v)), $extras));
        }
        return trim(strip_tags((string)$extras));
    }

    private function leadType(array $data): string
    {
        $type = (string)($data['lead_type'] ?? 'apartment');
        return in_array($type, ['apartment', 'house', 'commercial'], true) ? $type : 'apartment';
    }

    /**
     * @param array<string,mixed>|list<array<string,mixed>> $files
     * @return list<array<string,mixed>>
     */
    private function normalizeIncoming(array $files): array
    {
        if ($this->uploads) {
            $fromRequest = $this->uploads->incomingLeadFiles();
            if ($fromRequest) {
                return $fromRequest;
            }
        }
        if ($files === []) {
            return [];
        }
        if (isset($files['tmp_name']) && !is_array($files['tmp_name'])) {
            return (($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) ? [] : [$files];
        }
        return [];
    }

    private function hasLeadExtraColumns(PDO $pdo): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute(['leads', 'lead_type']);
        return $cached = (int)$stmt->fetchColumn() > 0;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = str_replace(',', '.', (string)$value);
        return is_numeric($value) ? (float)$value : null;
    }
}
