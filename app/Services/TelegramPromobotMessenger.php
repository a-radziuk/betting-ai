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

    public function sendWelcomeMessage(int $chatId): bool
    {
        $text = site_text('telegram.start.welcome', [
            'app' => config('app.name'),
        ], default: "👋 Welcome to :app!\n\nEnter your 5-digit promotion code here to unlock your trial access.\n\nDon't have a code? Send any message and we'll share our standard free trial.");

        return $this->sendMessage($chatId, $text);
    }

    public function sendPromoMatchedMessage(int $chatId, int $days, string $link): bool
    {
        $text = site_text('telegram.start.promo_matched', [
            'days' => (string) $days,
            'app' => config('app.name'),
            'link' => $link,
        ], default: "🚀 Your trial is ready!\n\nHere is your instant access to the :app AI football analytics platform.\n\nTap the link below to create your account and enjoy :days days fully free:\n:link");

        return $this->sendMessage($chatId, $text, $link);
    }

    public function sendPromoNotFoundMessage(int $chatId, string $link): bool
    {
        $text = site_text('telegram.start.promo_not_found', [
            'app' => config('app.name'),
            'link' => $link,
        ], default: "⚠️ Code not found.\n\nPlease check the digits and try again.\n\nOr grab your standard free access below:\n:link");

        return $this->sendMessage($chatId, $text, $link);
    }

    /** @deprecated Use sendPromoMatchedMessage() */
    public function sendStartMessage(int $chatId, int $days, string $link): bool
    {
        return $this->sendPromoMatchedMessage($chatId, $days, $link);
    }

    public function sendMessage(int $chatId, string $text, ?string $link = null): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $token = config('telegram_promobot.token');

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($link !== null && $link !== '') {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => __('Register and activate access'), 'url' => $link],
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
