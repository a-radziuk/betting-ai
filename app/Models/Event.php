<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'id',
        'home_team_id',
        'away_team_id',
        'tournament_id',
        'start_time',
        'status',
        'score',
        'score_aet',
        'score_pen',
        'additional_data',
        'comment',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'additional_data' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'int';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_LIVE = 'live';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_FINISHED = 'finished';

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SCHEDULED => __('Scheduled'),
            self::STATUS_LIVE => __('Live'),
            self::STATUS_PROCESSING => __('Processing'),
            self::STATUS_FINISHED => __('Finished'),
            default => (string) $this->status,
        };
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_FINISHED);
    }

    /**
     * Unresolved events whose kickoff was more than two hours ago.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeReadyToResolve(Builder $query): Builder
    {
        return $query
            ->unresolved()
            ->where('start_time', '<', now()->subHours(2));
    }

    /**
     * @return HasMany<Market, $this>
     */
    public function markets(): HasMany
    {
        return $this->hasMany(Market::class);
    }

    /**
     * @return HasMany<EventPrediction, $this>
     */
    public function eventPredictions(): HasMany
    {
        return $this->hasMany(EventPrediction::class);
    }

    /**
     * @return HasMany<EventAnalysis, $this>
     */
    public function eventAnalyses(): HasMany
    {
        return $this->hasMany(EventAnalysis::class);
    }

    /**
     * @return HasMany<UserBet, $this>
     */
    public function userBets(): HasMany
    {
        return $this->hasMany(UserBet::class, 'event_id', 'id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
