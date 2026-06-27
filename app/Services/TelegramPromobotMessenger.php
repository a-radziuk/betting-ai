<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPromobotMessenger
{
    public function isConfigured(): bool
    {
        $token = config('telegram_promobot.token');

        return is_string($token) && $token !== '';
    }

    public function sendWelcomeMessage(int $chatId, string $trialLink): bool
    {
        $text = site_text('telegram.start.welcome', [
            'app' => config('app.name'),
        ], default: "👋 <b>Welcome to :app!</b>\n\nIf a friend invited you, please <b>type their 5-digit code</b> below to activate your joint bonus.\n\nDon't have a code? No problem! Just tap the button below to get your standard free trial:");

        return $this->sendMessage(
            $chatId,
            $text,
            $trialLink,
            site_text('telegram.start.trial_button', [], default: '⚡️ Claim Free 3-Day Trial'),
        );
    }

    public function sendPartnerMatchedMessage(int $chatId, string $partnerCode, string $referralLink): bool
    {
        $text = site_text('telegram.start.partner_matched', [
            'code' => $partnerCode,
            'app' => config('app.name'),
        ], default: "🤝 <b>Invite verified!</b>\nPartner code <b>#:code</b> accepted. Bonus access has been credited to your friend.\n\nAs a thank-you for joining via a member invite, you've been upgraded to a <b>3-Day VIP Guest Pass</b>. Your portal is ready:");

        return $this->sendMessage(
            $chatId,
            $text,
            $referralLink,
            site_text('telegram.start.partner_button', [], default: 'Register and activate access'),
        );
    }

    public function sendPromoNotFoundMessage(int $chatId, string $trialLink): bool
    {
        $text = site_text('telegram.start.promo_not_found', [
            'app' => config('app.name'),
        ], default: "⚠️ <b>Code not found.</b>\n\nPlease check the digits and try typing them again. Or simply grab your standard free access below:");

        return $this->sendMessage(
            $chatId,
            $text,
            $trialLink,
            site_text('telegram.start.trial_button', [], default: '⚡️ Claim Free 3-Day Trial'),
        );
    }

    /** @deprecated Use sendPartnerMatchedMessage() */
    public function sendPromoMatchedMessage(int $chatId, int $days, string $link): bool
    {
        return $this->sendPartnerMatchedMessage($chatId, (string) $days, $link);
    }

    /** @deprecated Use sendWelcomeMessage() with trial link */
    public function sendStartMessage(int $chatId, int $days, string $link): bool
    {
        return $this->sendWelcomeMessage($chatId, $link);
    }

    public function sendMessage(int $chatId, string $text, ?string $link = null, ?string $buttonText = null): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $token = config('telegram_promobot.token');

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ];

        if ($link !== null && $link !== '') {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => $buttonText ?? __('Register and activate access'),
                            'url' => $link,
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR);
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->connectTimeout(5)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

            if (! $response->successful()) {
                Log::warning('Telegram promobot sendMessage failed', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Telegram promobot sendMessage exception', [
                'chat_id' => $chatId,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
