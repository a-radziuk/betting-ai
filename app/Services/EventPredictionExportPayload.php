<?php

namespace App\Services;

use App\Models\EventPrediction;
use Illuminate\Database\Eloquent\Builder;

final class EventPredictionExportPayload
{
    /**
     * @return Builder<EventPrediction>
     */
    public static function activePredictionsQuery(): Builder
    {
        return EventPrediction::query()
            ->active()
            ->orderBy('id');
    }

    /**
     * @return array{type: string, description: string, odd_id: int, stake: int, confidence?: int}
     */
    public static function buildForUpload(EventPrediction $prediction): array
    {
        $row = [
            'type' => (string) $prediction->prediction_type,
            'description' => (string) $prediction->explanation,
            'odd_id' => (int) $prediction->odds_id,
            'stake' => self::stakeFromBankPercentage((int) $prediction->bank_percentage),
        ];

        if ($prediction->confidence !== null) {
            $row['confidence'] = (int) $prediction->confidence;
        }

        return $row;
    }

    public static function stakeFromBankPercentage(int $bankPercentage): int
    {
        return (int) round(($bankPercentage / 100) * 1000);
    }
}
