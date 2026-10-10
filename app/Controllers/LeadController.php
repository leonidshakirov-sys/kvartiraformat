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
        if (!Csrf::verify((string)$request->input('_csrf', ''))) {
            Response::json(['ok' => false, 'errors' => ['Сессия формы истекла. Обновите страницу и попробуйте снова.']], 419);
        }
        $result = $this->leadService->submit($request->post(), $request, $_FILES['brief_file'] ?? []);
        if (!$result['ok']) {
            Response::json($result, 422);
        }
        Response::json($result);
    }

    public function estimate(Request $request): never
    {
        Response::json(['estimate' => $this->leadService->estimate($request->post())]);
    }
}
