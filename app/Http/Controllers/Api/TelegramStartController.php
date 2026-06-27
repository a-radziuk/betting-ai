<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramInteractionService;
use App\Services\TelegramPromobotMessenger;
use App\Services\TelegramPromocodeService;
use App\Support\TelegramPartnerCodes;
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

        if (TelegramPartnerCodes::isStartCommand($text)) {
            $trialLink = $this->trialRegistrationLink($telegramPromocodeService, $telegramId);
            $telegramPromobotMessenger->sendWelcomeMessage($telegramId, $trialLink);

            return response()->json([
                'status' => 'welcome',
                'link' => $trialLink,
            ]);
        }

        if (TelegramPartnerCodes::isFiveDigitCode($text)) {
            $partnerCode = TelegramPartnerCodes::matchPartnerCode($text);

            if ($partnerCode !== null) {
                $referralLink = TelegramPartnerCodes::referralLink($partnerCode);
                $telegramPromobotMessenger->sendPartnerMatchedMessage($telegramId, $partnerCode, $referralLink);

                return response()->json([
                    'status' => 'partner_matched',
                    'link' => $referralLink,
                    'partner_code' => $partnerCode,
                ]);
            }

            $trialLink = $this->trialRegistrationLink($telegramPromocodeService, $telegramId);
            $telegramPromobotMessenger->sendPromoNotFoundMessage($telegramId, $trialLink);

            return response()->json([
                'status' => 'promo_not_found',
                'link' => $trialLink,
            ]);
        }

        $trialLink = $this->trialRegistrationLink($telegramPromocodeService, $telegramId);
        $telegramPromobotMessenger->sendPromoNotFoundMessage($telegramId, $trialLink);

        return response()->json([
            'status' => 'invalid_input',
            'link' => $trialLink,
        ]);
    }

    private function trialRegistrationLink(TelegramPromocodeService $telegramPromocodeService, int $telegramId): string
    {
        $promocode = $telegramPromocodeService->issueForTelegramId($telegramId);

        return $telegramPromocodeService->registrationLink($promocode);
    }
}
