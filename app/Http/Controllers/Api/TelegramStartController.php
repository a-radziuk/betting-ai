<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramPromocodeService;
use App\Support\TelegramStartUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramStartController extends Controller
{
    public function __invoke(Request $request, TelegramPromocodeService $telegramPromocodeService): JsonResponse
    {
        $telegramId = TelegramStartUpdate::telegramUserId($request);

        $promocode = $telegramPromocodeService->issueForTelegramId($telegramId);

        return response()->json([
            'link' => $telegramPromocodeService->registrationLink($promocode),
        ]);
    }
}
