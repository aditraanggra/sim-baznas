<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratKeluarCetakLog extends Model
{
    public const CREATED_AT = 'waktu';

    public const UPDATED_AT = null;

    protected $table = 'surat_keluar_cetak_log';

    protected $fillable = [
        'surat_keluar_id',
        'user_id',
        'waktu',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function suratKeluar(): BelongsTo
    {
        return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
