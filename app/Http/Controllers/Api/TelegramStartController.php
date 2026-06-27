<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramInteractionService;
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
        TelegramInteractionService $telegramInteractionService,
        TelegramPromocodeService $telegramPromocodeService,
        TelegramPromobotMessenger $telegramPromobotMessenger,
    ): JsonResponse {
        $validated = TelegramStartUpdate::validated($request);
        $telegramInteractionService->recordLastInteraction($validated);

        $telegramId = (int) data_get($validated, 'message.from.id');
        $text = trim((string) data_get($validated, 'message.text', ''));

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
