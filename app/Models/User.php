<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password', 'email_verified_at', 'is_superadmin', 'is_hidden', 'is_metrics_available', 'priveleges', 'see_tips_expires_at', 'provider', 'provider_id', 'avatar', 'tagline', 'bio', 'hidden_description', 'city', 'country'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    public const PRIVELEGE_SEE_TIPS = 'SEE_TIPS';

    public const PRIVELEGE_PLACE_BETS = 'PLACE_BETS';

    public const PRIVELEGE_EDITOR = 'EDITOR';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function isSuperadmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    public function canAccessAdmin(): bool
    {
        return $this->isSuperadmin() || $this->hasPrivelege(self::PRIVELEGE_EDITOR);
    }

    public function canDeleteInAdmin(): bool
    {
        return $this->isSuperadmin();
    }

    public function isHidden(): bool
    {
        return (bool) $this->is_hidden;
    }

    public function isMetricsAvailable(): bool
    {
        return (bool) $this->is_metrics_available;
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeDueForMetricsUpdate(Builder $query, ?Carbon $now = null): Builder
    {
        $cutoff = ($now ?? now())->copy()->subDay();

        return $query->where(function (Builder $query) use ($cutoff): void {
            $query->whereNull('metrics_updated_at')
                ->orWhere('metrics_updated_at', '<', $cutoff);
        });
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeVisibleOnSite(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    public function hasPrivelege(string $privelege): bool
    {
        if ($privelege === self::PRIVELEGE_EDITOR) {
            return $this->hasStoredPrivelege($privelege);
        }

        if ($this->isSuperadmin()) {
            return true;
        }

        if ($privelege === self::PRIVELEGE_SEE_TIPS && $this->seeTipsAccessExpired()) {
            return false;
        }

        return $this->hasStoredPrivelege($privelege);
    }

    private function hasStoredPrivelege(string $privelege): bool
    {
        if ($this->priveleges === null || $this->priveleges === '') {
            return false;
        }

        $granted = array_map(trim(...), explode(',', $this->priveleges));

        return in_array($privelege, $granted, true);
    }

    public function seeTipsAccessExpired(): bool
    {
        return $this->see_tips_expires_at !== null
            && $this->see_tips_expires_at->isPast();
    }

    public function hasActiveSeeTipsAccess(): bool
    {
        return $this->hasPrivelege(self::PRIVELEGE_SEE_TIPS);
    }

    public function grantSeeTipsTrial(int $months = 1): void
    {
        $this->grantPrivelege(self::PRIVELEGE_SEE_TIPS);
        $this->see_tips_expires_at = now()->addMonths($months);
        $this->save();
    }

    public function extendSeeTipsAccessForPlan(string $planId): void
    {
        $this->grantPrivelege(self::PRIVELEGE_SEE_TIPS);

        $startsAt = ($this->see_tips_expires_at !== null && $this->see_tips_expires_at->isFuture())
            ? $this->see_tips_expires_at
            : now();

        $this->see_tips_expires_at = \App\Support\SubscriptionPlans::accessExpiresAtFrom($startsAt, $planId);
        $this->save();
    }

    public function extendSeeTipsAccessForDays(int $days): void
    {
        if ($days < 1) {
            return;
        }

        $this->grantPrivelege(self::PRIVELEGE_SEE_TIPS);

        $startsAt = ($this->see_tips_expires_at !== null && $this->see_tips_expires_at->isFuture())
            ? $this->see_tips_expires_at
            : now();

        $this->see_tips_expires_at = $startsAt->copy()->addDays($days);
        $this->save();
    }

    public function grantPrivelege(string $privelege): void
    {
        if ($this->priveleges === null || $this->priveleges === '') {
            $this->priveleges = $privelege;

            return;
        }

        $granted = array_map(trim(...), explode(',', $this->priveleges));
        if (! in_array($privelege, $granted, true)) {
            $granted[] = $privelege;
            $this->priveleges = implode(',', $granted);
        }
    }

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
     * @return HasMany<UserMetric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(UserMetric::class);
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
            'is_superadmin' => 'boolean',
            'is_hidden' => 'boolean',
            'is_metrics_available' => 'boolean',
            'metrics_updated_at' => 'datetime',
            'see_tips_expires_at' => 'datetime',
        ];
    }
}
