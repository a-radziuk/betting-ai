<?php

namespace App\Models;

use App\Services\SeoPageRepository;
use Illuminate\Database\Eloquent\Model;

class SeoPage extends Model
{
    public const KEY_HOMEPAGE = 'homepage';

    public const KEY_PLAYERS_INDEX = 'players_index';

    public const KEY_PLAYER_SHOW = 'player_show';

    public const KEY_TOURNAMENT_SHOW = 'tournament_show';

    public const KEY_EVENT_SHOW = 'event_show';

    public const KEY_LOGIN = 'login';

    public const KEY_REGISTER = 'register';

    public const KEY_FORGOT_PASSWORD = 'forgot_password';

    protected $fillable = [
        'key',
        'label',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(SeoPageRepository::class)->forget());
        static::deleted(fn () => app(SeoPageRepository::class)->forget());
    }

    /**
     * @return list<string>
     */
    public static function placeholderKeys(string $key): array
    {
        return match ($key) {
            self::KEY_PLAYER_SHOW => ['app', 'name'],
            self::KEY_TOURNAMENT_SHOW => ['app', 'tournament'],
            self::KEY_EVENT_SHOW => ['app', 'event'],
            default => ['app'],
        };
    }

    public function placeholderHint(): string
    {
        $keys = array_map(fn (string $placeholder): string => ':'.$placeholder, self::placeholderKeys($this->key));

        return __('Available placeholders: :placeholders', [
            'placeholders' => implode(', ', $keys),
        ]);
    }
}
