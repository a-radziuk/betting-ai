<?php

namespace App\Services;

use App\Models\Promocode;
use App\Support\PromocodeGenerator;

class TelegramPromocodeService
{
    public function issueForTelegramId(int $telegramId): Promocode
    {
        $existing = Promocode::query()
            ->where('telegram_id', $telegramId)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $promocode = PromocodeGenerator::generateUnique($this->days());
        $promocode->update(['telegram_id' => $telegramId]);

        return $promocode->fresh();
    }

    public function registrationLink(Promocode $promocode): string
    {
        return route('integration.telegram.promocode', [
            'promocode' => $promocode->code,
        ], absolute: true);
    }

    public function days(): int
    {
        return max(1, (int) config('telegram_promobot.days', 3));
    }
}
