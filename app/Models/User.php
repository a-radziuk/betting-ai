<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'provider', 'provider_id', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            $user->wallet()->create([
                'balance' => 0,
                'currency' => 'EUR',
            ]);
        });

        static::deleting(function (User $user): void {
            self::deleteStoredAvatarFile($user->avatar);
        });
    }

    /**
     * Public URL for profile avatar (uploaded path on public disk, or external URL from OAuth).
     */
    public function profileAvatarUrl(): ?string
    {
        $avatar = $this->avatar;
        if ($avatar === null || $avatar === '') {
            return null;
        }
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        return Storage::disk('public')->url($avatar);
    }

    public static function deleteStoredAvatarFile(?string $avatar): void
    {
        if ($avatar === null || $avatar === '') {
            return;
        }
        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return;
        }
        if (! str_starts_with($avatar, 'avatars/')) {
            return;
        }
        Storage::disk('public')->delete($avatar);
    }

    /**
     * @return HasOne<UserWallet, $this>
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(UserWallet::class);
    }

    /**
     * @return HasMany<UserBet, $this>
     */
    public function bets(): HasMany
    {
        return $this->hasMany(UserBet::class);
    }

    /**
     * @return HasMany<UserPredictionSubscription, $this>
     */
    public function predictionSubscriptions(): HasMany
    {
        return $this->hasMany(UserPredictionSubscription::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
