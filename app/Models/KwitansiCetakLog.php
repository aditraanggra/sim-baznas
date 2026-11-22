<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KwitansiCetakLog extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'waktu';

    protected $table = 'kwitansi_cetak_log';

    protected $fillable = [
        'kwitansi_id',
        'user_id',
        'cetak_ke',
        'ip_address',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'cetak_ke' => 'integer',
            'waktu' => 'datetime',
        ];
    }

    public function kwitansi(): BelongsTo
    {
        return $this->belongsTo(Kwitansi::class, 'kwitansi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
