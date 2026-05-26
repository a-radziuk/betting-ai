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
     * @return HasMany<TournamentTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(TournamentTranslation::class);
    }

    public function translationForCurrentLocale(): ?TournamentTranslation
    {
        $locale = app()->getLocale();

        if ($this->relationLoaded('translations')) {
            /** @var \Illuminate\Database\Eloquent\Collection<int, TournamentTranslation> $translations */
            $translations = $this->getRelation('translations');

            return $translations->firstWhere('locale', $locale);
        }

        return $this->translations()
            ->where('locale', $locale)
            ->first();
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
     * @return list<array<string, mixed>>
     */
    public function localizedStandingsRows(): array
    {
        $rows = is_array($this->standings) ? ($this->standings['rows'] ?? []) : [];
        if (! is_array($rows) || $rows === []) {
            return [];
        }

        $teams = $this->relationLoaded('teams')
            ? $this->teams
            : $this->teams()->with('translations')->get();

        $labelMap = [];
        foreach ($teams as $team) {
            $localized = $team->resolvedDisplayName();
            foreach ([$team->name, $team->display_name, $team->external_name, $team->guardian_name] as $sourceLabel) {
                if (is_string($sourceLabel) && $sourceLabel !== '') {
                    $labelMap[$sourceLabel] = $localized;
                }
            }
        }

        return array_map(function (mixed $row) use ($labelMap): array {
            if (! is_array($row)) {
                return [];
            }

            $sourceLabel = $row['team_display_name'] ?? $row['team'] ?? null;
            if (is_string($sourceLabel) && isset($labelMap[$sourceLabel])) {
                $row['team_display_name'] = $labelMap[$sourceLabel];
            }

            return $row;
        }, $rows);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
