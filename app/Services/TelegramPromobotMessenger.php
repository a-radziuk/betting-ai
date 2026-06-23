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

    public function sendStartMessage(int $chatId, int $days, string $link): bool
    {
        $text = site_text('telegram.start.message', [
            'days' => (string) $days,
            'link' => $link,
        ], default: "Here's your free :days-day subscription to the best AI football analytics website — :app.\n\nRegister: :link");

        return $this->sendMessage($chatId, $text, $link);
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
