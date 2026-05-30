<?php

namespace App\Services;

use App\Support\TelegramConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function sendMessage(string $text): bool
    {
        if (! TelegramConfig::isConfigured()) {
            return false;
        }

        $apiKey = config('telegram.api_key');
        $chatId = config('telegram.chat_id');

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->connectTimeout(5)
                ->post("https://api.telegram.org/bot{$apiKey}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);

            if (! $response->successful()) {
                Log::warning('Telegram sendMessage failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Telegram sendMessage exception', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
