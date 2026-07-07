<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promocode extends Model
{
    protected $fillable = [
        'code',
        'days',
        'is_multiple',
        'telegram_id',
        'partner_code',
        'owner_user_id',
        'used_at',
        'used_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'is_multiple' => 'boolean',
            'used_at' => 'datetime',
        ];
    }

    public function isMultiple(): bool
    {
        return (bool) $this->is_multiple;
    }

    public function isUsed(): bool
    {
        if ($this->isMultiple()) {
            return false;
        }

        return $this->used_at !== null;
    }

    public function hasBeenUsedByUser(User $user): bool
    {
        if ($this->isMultiple()) {
            return $this->redemptions()
                ->where('used_by_user_id', $user->id)
                ->exists();
        }

        return $this->used_by_user_id === $user->id;
    }

    public function redemptionLink(): string
    {
        return route('referral.promocode', [
            'promocode' => $this->code,
        ], absolute: true);
    }

    /**
     * @return HasMany<PromocodeRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(PromocodeRedemption::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }
}
