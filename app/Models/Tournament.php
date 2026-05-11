<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = [
        'name',
        'rank',
        'country',
        'stoiximan_url',
        'guardian_standings_url',
        'guardian_results_url',
        'standings',
        'standings_updated_at',
        'standings_promrel',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'standings' => 'array',
            'standings_updated_at' => 'datetime',
            'standings_promrel' => 'array',
        ];
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
