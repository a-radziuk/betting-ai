<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBet extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_WON = 'won';

    public const STATUS_LOST = 'lost';

    public const STATUS_VOID = 'void';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'event_id',
        'odd_id',
        'stake',
        'odds_at_bet',
        'potential_return',
        'real_return',
        'wallet_total_result',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'stake' => 'decimal:2',
            'odds_at_bet' => 'decimal:4',
            'potential_return' => 'decimal:2',
            'real_return' => 'decimal:2',
            'wallet_total_result' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Odd rows may be deleted and recreated by the scraper; there is no DB FK on odd_id.
     *
     * @return BelongsTo<Odd, $this>
     */
    public function odd(): BelongsTo
    {
        return $this->belongsTo(Odd::class);
    }
}
