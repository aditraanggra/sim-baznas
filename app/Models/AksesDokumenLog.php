<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AksesDokumenLog extends Model
{
    public $timestamps = false;

    protected $table = 'akses_dokumen_log';

    protected $fillable = [
        'dokumen_arsip_id',
        'tautan_akses_id',
        'user_id',
        'konteks',
        'aksi',
        'tahun_filter',
        'ip_address',
        'user_agent',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'dokumen_arsip_id' => 'integer',
            'tautan_akses_id' => 'integer',
            'user_id' => 'integer',
            'tahun_filter' => 'integer',
            'waktu' => 'datetime',
        ];
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(DokumenArsip::class, 'dokumen_arsip_id');
    }

    public function tautanAkses(): BelongsTo
    {
        return $this->belongsTo(TautanAkses::class, 'tautan_akses_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
