<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermohonanStatusLog extends Model
{
    protected $table = 'permohonan_status_log';

    public $timestamps = false;

    protected $fillable = [
        'permohonan_id',
        'status_kode',
        'status_publik',
        'judul_publik',
        'keterangan_internal',
        'keterangan_publik',
        'user_id',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
