<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Market extends Model
{
    protected $fillable = [
        'id',
        'event_id',
        'type',
        'period',
        'line',
        'status',
        'is_supported_market',
    ];

    protected function casts(): array
    {
        return [
            'is_supported_market' => 'boolean',
        ];
    }

    public $incrementing = false;

    protected $keyType = 'int';

    public const TYPE_MATCH_RESULT = 'MATCH_RESULT';
    public const TYPE_OVER_UNDER = 'OVER_UNDER';
    public const TYPE_BTTS = 'BTTS';
    public const TYPE_HANDICAP = 'HANDICAP';
    public const TYPE_CORRECT_SCORE = 'CORRECT_SCORE';
    public const TYPE_GOALSCORER = 'GOALSCORER';
    public const TYPE_DOUBLE_CHANCE = 'DOUBLE_CHANCE';
    public const TYPE_OVER_UNDER_TOTAL_GOALS = 'OVER_UNDER_TOTAL_GOALS';
    public const TYPE_OVER_UNDER_TOTAL_GOALS_EXTRA = 'OVER_UNDER_TOTAL_GOALS_EXTRA';
    public const TYPE_HOME_OVER_UNDER_TOTAL_GOALS = 'HOME_OVER_UNDER_TOTAL_GOALS';
    public const TYPE_AWAY_OVER_UNDER_TOTAL_GOALS = 'AWAY_OVER_UNDER_TOTAL_GOALS';
    public const TYPE_DRAW_NO_BET = 'DRAW_NO_BET';
    public const TYPE_TOTAL_ASIAN = 'TOTAL_ASIAN';
    public const TYPE_HOME_TOTAL_ASIAN = 'HOME_TOTAL_ASIAN';
    public const TYPE_AWAY_TOTAL_ASIAN = 'AWAY_TOTAL_ASIAN';
    public const TYPE_HOME_TO_SCORE = 'HOME_TO_SCORE';
    public const TYPE_AWAY_TO_SCORE = 'AWAY_TO_SCORE';
    public const TYPE_HANDICAP_ASIAN = 'HANDICAP_ASIAN';
    public const PERIOD_FULL_TIME = 'FT';
    public const PERIOD_HALF_TIME = 'HT';

    public const STATUS_OPEN = 'open';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_SETTLED = 'settled';

    public const SUPPORTED_TYPES = [
        self::TYPE_MATCH_RESULT,
        self::TYPE_OVER_UNDER,
        self::TYPE_BTTS,
        self::TYPE_HANDICAP,
        self::TYPE_CORRECT_SCORE,
        self::TYPE_DOUBLE_CHANCE,
        self::TYPE_OVER_UNDER_TOTAL_GOALS,
        self::TYPE_OVER_UNDER_TOTAL_GOALS_EXTRA,
        self::TYPE_HOME_OVER_UNDER_TOTAL_GOALS,
        self::TYPE_AWAY_OVER_UNDER_TOTAL_GOALS,
        self::TYPE_DRAW_NO_BET,
        self::TYPE_TOTAL_ASIAN,
        self::TYPE_HOME_TOTAL_ASIAN,
        self::TYPE_AWAY_TOTAL_ASIAN,
        self::TYPE_HOME_TO_SCORE,
        self::TYPE_AWAY_TO_SCORE,
        self::TYPE_HANDICAP_ASIAN,
    ];

    /**
     * @var array<string, string>
     */
    private const TYPE_LABELS = [
        self::TYPE_MATCH_RESULT => 'Match Result',
        self::TYPE_OVER_UNDER => 'Over/Under',
        self::TYPE_BTTS => 'Both Teams To Score',
        self::TYPE_HANDICAP => 'Handicap',
        self::TYPE_CORRECT_SCORE => 'Correct Score',
        self::TYPE_GOALSCORER => 'Goalscorer',
        self::TYPE_DOUBLE_CHANCE => 'Double Chance',
        self::TYPE_OVER_UNDER_TOTAL_GOALS => 'Over/Under Total Goals',
        self::TYPE_OVER_UNDER_TOTAL_GOALS_EXTRA => 'Over/Under Total Goals',
        self::TYPE_HOME_OVER_UNDER_TOTAL_GOALS => 'Home Over/Under Total Goals',
        self::TYPE_AWAY_OVER_UNDER_TOTAL_GOALS => 'Away Over/Under Total Goals',
        self::TYPE_DRAW_NO_BET => 'Draw No Bet',
        self::TYPE_TOTAL_ASIAN => 'Total',
        self::TYPE_HOME_TOTAL_ASIAN => 'Home Total',
        self::TYPE_AWAY_TOTAL_ASIAN => 'Away Total',
        self::TYPE_HOME_TO_SCORE => 'Home To Score',
        self::TYPE_AWAY_TO_SCORE => 'Away To Score',
        self::TYPE_HANDICAP_ASIAN => 'Handicap',
    ];

    /**
     * @return array<string, array<int, string>>
     */
    public static function availableSelectionsByType(): array
    {
        return [
            self::TYPE_MATCH_RESULT => ['HOME', 'DRAW', 'AWAY'],
            self::TYPE_OVER_UNDER => ['OVER', 'UNDER'],
            self::TYPE_BTTS => ['YES', 'NO'],
            self::TYPE_HANDICAP => ['HOME', 'AWAY'],
            self::TYPE_CORRECT_SCORE => [
                '0-0', '1-0', '0-1', '1-1', '2-0', '0-2', '2-1', '1-2',
                '2-2', '3-0', '0-3', '3-1', '1-3', '3-2', '2-3', '3-3',
                '4-0', '0-4', '4-1', '1-4', '4-2', '2-4', '4-3', '3-4',
                '4-4', '5-0', '0-5', '5-1', '1-5', '5-2', '2-5', '5-3',
                '3-5', '5-4', '4-5', '5-5', 'OTHER',
            ],
            self::TYPE_GOALSCORER => [],
            self::TYPE_DOUBLE_CHANCE => ['1X', '12', 'X2'],
            self::TYPE_TOTAL_ASIAN => ['OVER', 'UNDER'],
            self::TYPE_HOME_TOTAL_ASIAN => ['OVER', 'UNDER'],
            self::TYPE_AWAY_TOTAL_ASIAN => ['OVER', 'UNDER'],
            self::TYPE_HOME_TO_SCORE => ['YES', 'NO'],
            self::TYPE_AWAY_TO_SCORE => ['YES', 'NO'],
            self::TYPE_HANDICAP_ASIAN => ['HOME', 'AWAY'],
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabelFor($this->type);
    }

    public function typeLabelForEvent(?Event $event = null): string
    {
        return self::typeLabelFor($this->type, $event ?? $this->event);
    }

    public static function typeLabelFor(?string $type, ?Event $event = null): string
    {
        if ($type === null || $type === '') {
            return __('Market');
        }

        if ($event !== null) {
            $eventLabel = self::eventTeamTypeLabel($type, $event);
            if ($eventLabel !== null) {
                return $eventLabel;
            }
        }

        return __(
            self::TYPE_LABELS[$type]
                ?? str_replace('_', ' ', ucwords(strtolower($type), '_'))
        );
    }

    private static function eventTeamTypeLabel(string $type, Event $event): ?string
    {
        $home = $event->homeTeam?->resolvedDisplayName() ?? __('Home');
        $away = $event->awayTeam?->resolvedDisplayName() ?? __('Away');

        return match ($type) {
            self::TYPE_HOME_OVER_UNDER_TOTAL_GOALS => $home.' '.__('Over/Under Total Goals'),
            self::TYPE_AWAY_OVER_UNDER_TOTAL_GOALS => $away.' '.__('Over/Under Total Goals'),
            self::TYPE_HOME_TOTAL_ASIAN => $home.' '.__('Total'),
            self::TYPE_AWAY_TOTAL_ASIAN => $away.' '.__('Total'),
            self::TYPE_HOME_TO_SCORE => $home.' '.__('To Score'),
            self::TYPE_AWAY_TO_SCORE => $away.' '.__('To Score'),
            default => null,
        };
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<Selection, $this>
     */
    public function selections(): HasMany
    {
        return $this->hasMany(Selection::class);
    }
}
