<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventPrediction extends Model
{
    public const PREDICTION_TYPE_GET_ONE_BEST_FOR_EVENT_DEFAULT = 'GET_ONE_BEST_FOR_EVENT_DEFAULT';

    protected $fillable = [
        'event_id',
        'prediction_type',
        'odds_id',
        'bank_percentage',
        'explanation',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'odds_id' => 'integer',
            'bank_percentage' => 'integer',
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
