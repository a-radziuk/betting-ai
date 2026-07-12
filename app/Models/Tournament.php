<?php

namespace App\Models;

use App\Casts\AsStandingsPromrel;
use App\Services\HomepageCache;
use App\Services\TournamentShowCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = [
        'name',
        'rank',
        'country',
        'source',
        'export_marker',
        'is_playoff',
        'is_active',
        'is_fifa',
        'stoiximan_url',
        'parimatch_url',
        'guardian_standings_url',
        'guardian_results_url',
        'bbc_standings_url',
        'bbc_results_url',
        'standings',
        'standings_updated_at',
        'standings_promrel',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'is_playoff' => 'boolean',
            'is_active' => 'boolean',
            'is_fifa' => 'boolean',
            'standings' => 'array',
            'standings_updated_at' => 'datetime',
            'standings_promrel' => AsStandingsPromrel::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Tournament $tournament): void {
            if ($tournament->wasChanged(['standings', 'standings_promrel', 'is_active'])) {
                app(TournamentShowCache::class)->forgetAllLocales($tournament);
            }

            if ($tournament->wasChanged('is_active')) {
                app(HomepageCache::class)->forgetAllLocales();
            }
        });
    }

    /**
     * @param  Builder<Tournament>  $query
     * @return Builder<Tournament>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function localizedName(): string
    {
        return (string) $this->name;
    }

    /**
     * @return list<array{name: string, rows: list<array<string, mixed>>}>
     */
    public function localizedStandingsGroups(): array
    {
        $groups = is_array($this->standings) ? ($this->standings['groups'] ?? []) : [];
        if (! is_array($groups) || $groups === []) {
            return [];
        }

        $localizedGroups = [];
        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $name = isset($group['name']) ? trim((string) $group['name']) : '';
            if ($name === '' && $rows === []) {
                continue;
            }

            $localizedGroups[] = [
                'name' => $name !== '' ? $name : __('Group'),
                'rows' => $this->localizeStandingsRowList($rows),
            ];
        }

        return $localizedGroups;
    }

    public function hasGroupedStandings(): bool
    {
        return $this->localizedStandingsGroups() !== [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function localizedStandingsRows(): array
    {
        if ($this->hasGroupedStandings()) {
            $rows = [];
            foreach ($this->localizedStandingsGroups() as $group) {
                foreach ($group['rows'] as $row) {
                    $row['group'] = $group['name'];
                    $rows[] = $row;
                }
            }

            return $rows;
        }

        $rows = is_array($this->standings) ? ($this->standings['rows'] ?? []) : [];
        if (! is_array($rows) || $rows === []) {
            return [];
        }

        return $this->localizeStandingsRowList($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function localizeStandingsRowList(array $rows): array
    {
        $teams = $this->relationLoaded('teams')
            ? $this->teams
            : $this->teams()->get();

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
