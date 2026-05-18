<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPrediction extends Model
{
    public const PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT = 'GET_ONE_BEST_FOR_EVENT_DEFAULT';

    public const PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT = 'GET_ONE_SAFEST_FOR_EVENT_DEFAULT';

    public const PREDICTION_TYPE_GET_ONE_UPSET_FOR_EVENT_DEFAULT = 'GET_ONE_UPSET_FOR_EVENT_DEFAULT';

    public static function predictionTypeFor(int $typeKey): ?string
    {
        return match ($typeKey) {
            1 => self::PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT,
            2 => self::PREDICTION_TYPE_GET_ONE_SAFEST_FOR_EVENT_DEFAULT,
            3 => self::PREDICTION_TYPE_GET_ONE_UPSET_FOR_EVENT_DEFAULT,
            default => null,
        };
    }

    protected $fillable = [
        'event_id',
        'prediction_type',
        'odds_id',
        'bank_percentage',
        'explanation',
        'confidence',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'odds_id' => 'integer',
            'bank_percentage' => 'integer',
            'confidence' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<EventPrediction>  $query
     * @return Builder<EventPrediction>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
