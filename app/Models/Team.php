<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'league',
    ];

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
