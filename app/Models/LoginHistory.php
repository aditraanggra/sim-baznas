<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'waktu';

    protected $table = 'login_history';

    protected $fillable = [
        'user_id',
        'identitas',
        'ip_address',
        'user_agent',
        'hasil',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
