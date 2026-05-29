<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FULFILLED = 'fulfilled';

    protected $fillable = [
        'user_id',
        'plan_id',
        'stripe_payment_intent_id',
        'amount_cents',
        'currency',
        'status',
        'fulfilled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fulfilled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFulfilled(): bool
    {
        return $this->fulfilled_at !== null;
    }
}
