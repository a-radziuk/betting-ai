<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMetric extends Model
{
    public const TYPE_TOTAL_RESULT_POSITIVE = 'total_result_positive';

    public const TYPE_LAST_10_POSITIVE = 'last_10_positive';

    public const TYPE_LAST_20_POSITIVE = 'last_20_positive';

    public const TYPE_LAST_30_POSITIVE = 'last_30_positive';

    public const TYPE_WINNING_STREAK = 'winning_streak';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'length',
        'bets_stats',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'length' => 'integer',
            'bets_stats' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_TOTAL_RESULT_POSITIVE => __('Total result'),
            self::TYPE_LAST_10_POSITIVE => __('Last 10 bets'),
            self::TYPE_LAST_20_POSITIVE => __('Last 20 bets'),
            self::TYPE_LAST_30_POSITIVE => __('Last 30 bets'),
            self::TYPE_WINNING_STREAK => __('Winning streak'),
            default => __('Performance metric'),
        };
    }
}
