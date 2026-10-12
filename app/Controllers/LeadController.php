<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Services\LeadService;

final class LeadController
{
    public function __construct(private LeadService $leadService)
    {
    }

    public function submit(Request $request): never
    {
        if ($this->postDropped()) {
            Response::json(['ok' => false, 'errors' => ['Слишком большой объём данных. Каждый файл — до 15 МБ, все файлы вместе — до 50 МБ.']], 413);
        }
        if (!Csrf::verify((string)$request->input('_csrf', ''))) {
            Response::json(['ok' => false, 'errors' => ['Сессия формы истекла. Обновите страницу и попробуйте снова.']], 419);
        }
        $result = $this->leadService->submit($request->post(), $request, $_FILES['attachments'] ?? $_FILES['brief_file'] ?? []);
        if (!$result['ok']) {
            Response::json($result, 422);
        }
        Response::json($result);
    }

    public function estimate(Request $request): never
    {
        if ($this->postDropped()) {
            Response::json(['ok' => false, 'errors' => ['Слишком большой объём данных. Уменьшите размер файлов.']], 413);
        }
        if (!Csrf::verify((string)$request->input('_csrf', ''))) {
            Response::json(['ok' => false, 'errors' => ['Сессия формы истекла. Обновите страницу и попробуйте снова.']], 419);
        }
        $post = $request->post();
        if (isset($post['quiz']) && is_array($post['quiz'])) {
            $quote = $this->leadService->quote($post);
            Response::json([
                'ok' => !empty($quote['ok']),
                'estimate' => (string)($quote['summary'] ?? 'Индивидуальный расчёт'),
                'quote' => $quote,
                'errors' => $quote['errors'] ?? [],
            ], !empty($quote['ok']) ? 200 : 422);
        }
        Response::json(['ok' => true, 'estimate' => $this->leadService->estimate($post)]);
    }

    private function postDropped(): bool
    {
        $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        return $length > 0 && $_POST === [] && $_FILES === [];
    }
}
