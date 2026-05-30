<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetamaskPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PENDING_ADMIN_REVIEW = 'pending_admin_review';

    public const STATUS_APPROVED = 'approved';

    public const TOKEN_ETH = 'eth';

    public const TOKEN_USDT = 'usdt';

    public const TOKEN_USDC = 'usdc';

    protected $fillable = [
        'user_id',
        'plan_id',
        'tx_hash',
        'token',
        'amount_cents',
        'recipient_address',
        'status',
        'payment_payload',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_payload' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
