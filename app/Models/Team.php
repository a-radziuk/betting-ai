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
        'display_name',
        'external_name',
        'short_name',
        'league',
        'country',
        'guardian_name',
        'fifa_name',
        'fifa_rank',
        'fifa_points',
    ];

    protected function casts(): array
    {
        return [
            'fifa_rank' => 'integer',
            'fifa_points' => 'decimal:2',
        ];
    }

    /**
     * Label shown on the site: `display_name` when set, otherwise `name`.
     */
    public function resolvedDisplayName(): string
    {
        $translation = $this->translationForCurrentLocale();
        if ($translation !== null && $translation->display_name !== null && $translation->display_name !== '') {
            return (string) $translation->display_name;
        }

        if ($this->display_name !== null && $this->display_name !== '') {
            return (string) $this->display_name;
        }

        if ($translation !== null && $translation->name !== null && $translation->name !== '') {
            return (string) $translation->name;
        }

        return (string) $this->name;
    }

    public function localizedName(): string
    {
        $translation = $this->translationForCurrentLocale();

        if ($translation !== null && $translation->name !== null && $translation->name !== '') {
            return (string) $translation->name;
        }

        return (string) $this->name;
    }

    /**
     * @return HasMany<TeamTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(TeamTranslation::class);
    }

    public function translationForCurrentLocale(): ?TeamTranslation
    {
        $locale = app()->getLocale();

        if ($this->relationLoaded('translations')) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, TeamTranslation> $translations */
            $translations = $this->getRelation('translations');

            return $translations->firstWhere('locale', $locale);
        }

        return $this->translations()
            ->where('locale', $locale)
            ->first();
    }

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
