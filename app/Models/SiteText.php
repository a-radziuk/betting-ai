<?php

namespace App\Models;

use App\Services\SiteTextRepository;
use Illuminate\Database\Eloquent\Model;

class SiteText extends Model
{
    protected $fillable = [
        'key',
        'group',
        'label',
        'value',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => app(SiteTextRepository::class)->forget());
        static::deleted(fn () => app(SiteTextRepository::class)->forget());
    }
}
