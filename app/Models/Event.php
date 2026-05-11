<?php

namespace App\Models;

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
        'additional_data',
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
