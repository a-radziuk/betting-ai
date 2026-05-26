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
        'handicap_home',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'handicap' => 'decimal:2',
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

    public static function displayNameFor(?string $name, ?Event $event = null): string
    {
        if ($name === null || trim($name) === '') {
            return 'Selection';
        }

        $trimmed = trim($name);
        $upper = strtoupper($trimmed);

        $homeTeam = 'Home';
        $awayTeam = 'Away';

        return match ($upper) {
            self::NAME_HOME => $homeTeam,
            self::NAME_DRAW => 'Draw',
            self::NAME_AWAY => $awayTeam,
            self::NAME_OVER => 'Over',
            self::NAME_UNDER => 'Under',
            self::NAME_YES => 'Yes',
            self::NAME_NO => 'No',
            '1X', '1/X' => $homeTeam.' or Draw',
            'X2' => 'Draw or '.$awayTeam,
            '12', '1/2' => $homeTeam.' or '.$awayTeam,
            default => self::humanizeName($trimmed, $homeTeam, $awayTeam),
        };
    }

    private static function humanizeName(string $name, string $homeTeam, string $awayTeam): string
    {
        $normalized = str_replace('_', ' ', strtoupper($name));
        $tokens = preg_split('/(\s+|\/|-)/', $normalized, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$normalized];
        $map = [
            self::NAME_HOME => $homeTeam,
            self::NAME_DRAW => 'Draw',
            self::NAME_AWAY => $awayTeam,
            self::NAME_OVER => 'Over',
            self::NAME_UNDER => 'Under',
            self::NAME_YES => 'Yes',
            self::NAME_NO => 'No',
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
