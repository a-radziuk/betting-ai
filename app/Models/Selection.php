<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Selection extends Model
{
    protected $fillable = [
        'id',
        'market_id',
        'name',
        'participant_id',
        'handicap',
        'value',
        'handicap_home',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'handicap' => 'decimal:2',
            'value' => 'decimal:2',
            'handicap_home' => 'decimal:2',
        ];
    }

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    public const NAME_HOME = 'HOME';
    public const NAME_DRAW = 'DRAW';
    public const NAME_AWAY = 'AWAY';
    public const NAME_OVER = 'OVER';
    public const NAME_UNDER = 'UNDER';
    public const NAME_YES = 'YES';
    public const NAME_NO = 'NO';

    public function displayName(?Event $event = null): string
    {
        return self::displayNameFor($this->name, $event ?? $this->market?->event);
    }

    public function displayNameWithValue(?Event $event = null): string
    {
        $name = $this->displayName($event);

        if (! $this->shouldDisplayValue()) {
            return $name;
        }

        $value = $this->formattedValue();
        if ($value === null) {
            return $name;
        }

        return $name.' '.$value;
    }

    public function shouldDisplayValue(): bool
    {
        $type = $this->market?->type;

        return in_array($type, [
            Market::TYPE_TOTAL_ASIAN,
            Market::TYPE_HOME_TOTAL_ASIAN,
            Market::TYPE_AWAY_TOTAL_ASIAN,
            Market::TYPE_HANDICAP_ASIAN,
        ], true);
    }

    public function formattedValue(): ?string
    {
        if ($this->value === null) {
            return null;
        }

        $numeric = (float) $this->value;
        $formatted = rtrim(rtrim(number_format($numeric, 2, '.', ''), '0'), '.');

        if ($this->market?->type === Market::TYPE_HANDICAP_ASIAN) {
            return ($numeric > 0 ? '+' : '').$formatted;
        }

        return $formatted;
    }

    public static function displayNameFor(?string $name, ?Event $event = null): string
    {
        if ($name === null || trim($name) === '') {
            return __('Selection');
        }

        $trimmed = trim($name);
        $upper = strtoupper($trimmed);
        ['home' => $homeTeam, 'away' => $awayTeam] = self::teamLabels($event);

        return match ($upper) {
            self::NAME_HOME => $homeTeam,
            self::NAME_DRAW => __('Draw'),
            self::NAME_AWAY => $awayTeam,
            self::NAME_OVER => __('Over'),
            self::NAME_UNDER => __('Under'),
            self::NAME_YES => __('Yes'),
            self::NAME_NO => __('No'),
            '1X', '1/X' => __(':home or Draw', ['home' => $homeTeam]),
            'X2' => __('Draw or :away', ['away' => $awayTeam]),
            '12', '1/2' => __(':home or :away', ['home' => $homeTeam, 'away' => $awayTeam]),
            default => self::humanizeName($trimmed, $homeTeam, $awayTeam),
        };
    }

    /**
     * @return array{home: string, away: string}
     */
    private static function teamLabels(?Event $event): array
    {
        $homeTeam = __('Home');
        $awayTeam = __('Away');

        if ($event !== null) {
            $homeTeam = $event->homeTeam?->resolvedDisplayName() ?? $homeTeam;
            $awayTeam = $event->awayTeam?->resolvedDisplayName() ?? $awayTeam;
        }

        return [
            'home' => $homeTeam,
            'away' => $awayTeam,
        ];
    }

    private static function humanizeName(string $name, string $homeTeam, string $awayTeam): string
    {
        $normalized = str_replace('_', ' ', strtoupper($name));
        $tokens = preg_split('/(\s+|\/|-)/', $normalized, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$normalized];
        $map = [
            self::NAME_HOME => $homeTeam,
            self::NAME_DRAW => __('Draw'),
            self::NAME_AWAY => $awayTeam,
            self::NAME_OVER => __('Over'),
            self::NAME_UNDER => __('Under'),
            self::NAME_YES => __('Yes'),
            self::NAME_NO => __('No'),
        ];

        foreach ($tokens as $index => $token) {
            $trimmed = trim($token);
            if ($trimmed === '') {
                continue;
            }

            $tokens[$index] = $map[$trimmed]
                ?? ucwords(strtolower($trimmed));
        }

        return preg_replace('/\s+/', ' ', trim(implode('', $tokens))) ?? $name;
    }

    /**
     * @return BelongsTo<Market, $this>
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * @return HasMany<Odd, $this>
     */
    public function odds(): HasMany
    {
        return $this->hasMany(Odd::class);
    }
}
