<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'tournament_id',
        'name',
        'external_name',
        'short_name',
        'league',
        'country',
    ];

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function homeEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'home_team_id');
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function awayEvents(): HasMany
    {
        return $this->hasMany(Event::class, 'away_team_id');
    }
}
