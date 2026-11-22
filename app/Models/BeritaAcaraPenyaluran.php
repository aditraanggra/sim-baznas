<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BeritaAcaraPenyaluran extends Model
{
    use MemilikiPembuat;

    protected $table = 'berita_acara_penyaluran';

    protected $fillable = [
        'nomor',
        'tanggal',
        'revisi_ke',
        'status',
        'total_rupiah',
        'jumlah_transaksi',
        'penanggung_jawab_id',
        'catatan',
        'final_at',
        'alasan_batal',
        'dibatalkan_oleh',
        'dibatalkan_at',
        'dibuat_oleh',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'revisi_ke' => 'integer',
            'total_rupiah' => 'integer',
            'jumlah_transaksi' => 'integer',
            'final_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function penyaluran(): HasMany
    {
        return $this->hasMany(Penyaluran::class, 'berita_acara_id');
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penanggung_jawab_id');
    }

    public function pembuatDokumen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
