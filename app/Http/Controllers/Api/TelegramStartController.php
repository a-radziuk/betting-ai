<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramPromobotMessenger;
use App\Services\TelegramPromocodeService;
use App\Support\TelegramPromoDigitalCodes;
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
        $text = TelegramStartUpdate::messageText($request);

        if (TelegramPromoDigitalCodes::isStartCommand($text)) {
            $telegramPromobotMessenger->sendWelcomeMessage($telegramId);

            return response()->json([
                'status' => 'welcome',
            ]);
        }

        $promocode = $telegramPromocodeService->issueForTelegramId($telegramId);
        $link = $telegramPromocodeService->registrationLink($promocode);

        if (TelegramPromoDigitalCodes::matchInText($text) !== null) {
            $telegramPromobotMessenger->sendPromoMatchedMessage($telegramId, $promocode->days, $link);

            return response()->json([
                'status' => 'promo_matched',
                'link' => $link,
            ]);
        }

        $telegramPromobotMessenger->sendPromoNotFoundMessage($telegramId, $link);

        return response()->json([
            'status' => 'promo_not_found',
            'link' => $link,
        ]);
    }
}
