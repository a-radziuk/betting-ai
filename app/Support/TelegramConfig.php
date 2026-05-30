<?php

namespace App\Support;

final class TelegramConfig
{
    public static function isConfigured(): bool
    {
        $apiKey = config('telegram.api_key');
        $chatId = config('telegram.chat_id');

        return is_string($apiKey) && $apiKey !== ''
            && is_string($chatId) && $chatId !== '';
    }
}
