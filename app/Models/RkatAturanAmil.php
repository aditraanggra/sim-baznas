<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkatAturanAmil extends Model
{
    use MemilikiPembuat;

    protected $table = 'rkat_aturan_amil';

    protected $fillable = [
        'rkat_version_id',
        'pos_anggaran_id',
        'urutan',
        'uraian',
        'kelompok_penyaluran',
        'persen_bp',
        'persen_maks_bp',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rkat_version_id' => 'integer',
            'pos_anggaran_id' => 'integer',
            'urutan' => 'integer',
            'persen_bp' => 'integer',
            'persen_maks_bp' => 'integer',
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
