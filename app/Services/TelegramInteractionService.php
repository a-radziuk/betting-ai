<?php

namespace App\Services;

use App\Models\TelegramInteraction;

class TelegramInteractionService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function recordLastInteraction(array $validated): TelegramInteraction
    {
        /** @var array<string, mixed> $from */
        $from = data_get($validated, 'message.from', []);

        return TelegramInteraction::query()->updateOrCreate(
            ['telegram_id' => (int) $from['id']],
            [
                'is_bot' => (bool) ($from['is_bot'] ?? false),
                'first_name' => $from['first_name'] ?? null,
                'last_name' => $from['last_name'] ?? null,
                'username' => $from['username'] ?? null,
                'language_code' => $from['language_code'] ?? null,
                'text' => data_get($validated, 'message.text'),
                'created_at' => now(),
            ],
        );
    }
}
