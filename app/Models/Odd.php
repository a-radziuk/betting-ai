<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Odd extends Model
{
    protected $fillable = [
        'id',
        'selection_id',
        'odds',
        'probability',
        'is_active',
        'created_at',
    ];

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    /**
     * @return BelongsTo<Selection, $this>
     */
    public function selection(): BelongsTo
    {
        return $this->belongsTo(Selection::class);
    }
}
