<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramPromocodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramStartController extends Controller
{
    public function __invoke(Request $request, TelegramPromocodeService $telegramPromocodeService): JsonResponse
    {
        $validated = $request->validate([
            'tg_id' => ['required', 'integer', 'min:1'],
        ]);

        $promocode = $telegramPromocodeService->issueForTelegramId($validated['tg_id']);

        return response()->json([
            'link' => $telegramPromocodeService->registrationLink($promocode),
        ]);
    }
}
