<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CookieConsent extends Model
{
    public const ACTION_ACCEPTED_ALL = 'accepted_all';

    public const ACTION_REJECTED_ALL = 'rejected_all';

    public const ACTION_CUSTOMIZED = 'customized';

    public const ACTION_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'consent_uuid',
        'user_id',
        'version',
        'action',
        'categories',
        'ip_hash',
        'user_agent',
        'withdrawn_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'withdrawn_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
