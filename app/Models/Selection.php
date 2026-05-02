<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Selection extends Model
{
    protected $fillable = [
        'id',
        'market_id',
        'name',
        'participant_id',
        'handicap',
        'handicap_home',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'handicap' => 'decimal:2',
            'handicap_home' => 'decimal:2',
        ];
    }

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    public const NAME_HOME = 'HOME';
    public const NAME_DRAW = 'DRAW';
    public const NAME_AWAY = 'AWAY';
    public const NAME_OVER = 'OVER';
    public const NAME_UNDER = 'UNDER';
    public const NAME_YES = 'YES';
    public const NAME_NO = 'NO';

    /**
     * @return BelongsTo<Market, $this>
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * @return HasMany<Odd, $this>
     */
    public function odds(): HasMany
    {
        return $this->hasMany(Odd::class);
    }
}
