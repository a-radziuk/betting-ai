<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramInteraction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'telegram_id',
        'is_bot',
        'first_name',
        'last_name',
        'username',
        'language_code',
        'text',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'telegram_id' => 'integer',
            'is_bot' => 'boolean',
            'created_at' => 'datetime',
        ];
    }
}
