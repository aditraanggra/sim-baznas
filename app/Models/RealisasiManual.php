<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealisasiManual extends Model
{
    use HasFactory, MemilikiPembuat;

    protected $table = 'realisasi_manual';

    protected $fillable = [
        'tahun',
        'bulan',
        'pos_anggaran_id',
        'nilai',
        'sumber',
        'terkunci',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'nilai' => 'integer',
            'terkunci' => 'boolean',
        ];
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_anggaran_id');
    }
}
