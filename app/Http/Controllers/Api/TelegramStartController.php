<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramPromobotMessenger;
use App\Services\TelegramPromocodeService;
use App\Support\TelegramStartUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramStartController extends Controller
{
    public function __invoke(
        Request $request,
        TelegramPromocodeService $telegramPromocodeService,
        TelegramPromobotMessenger $telegramPromobotMessenger,
    ): JsonResponse {
        $telegramId = TelegramStartUpdate::telegramUserId($request);

        $promocode = $telegramPromocodeService->issueForTelegramId($telegramId);
        $link = $telegramPromocodeService->registrationLink($promocode);

        $telegramPromobotMessenger->sendStartMessage($telegramId, $promocode->days, $link);

        return response()->json([
            'link' => $link,
        ]);
    }
}
