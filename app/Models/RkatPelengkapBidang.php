<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkatPelengkapBidang extends Model
{
    use MemilikiPembuat;

    protected $table = 'rkat_pelengkap_bidang';

    protected $fillable = [
        'rkat_version_id',
        'bidang_kode',
        'pos_anggaran_id',
        'nilai',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rkat_version_id' => 'integer',
            'pos_anggaran_id' => 'integer',
            'nilai' => 'integer',
        ];
    }

    public function versi(): BelongsTo
    {
        return $this->belongsTo(RkatVersion::class, 'rkat_version_id');
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_anggaran_id');
    }
}
