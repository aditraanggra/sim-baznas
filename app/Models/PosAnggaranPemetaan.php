<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosAnggaranPemetaan extends Model
{
    use HasFactory, MemilikiPembuat;

    protected $table = 'pos_anggaran_pemetaan';

    public const SUMBER_PENERIMAAN = 'penerimaan';
    public const SUMBER_PENYALURAN = 'penyaluran';
    public const SUMBER_PENERIMAAN_ORANG = 'penerimaan_orang';
    public const SUMBER_PENYALURAN_ORANG = 'penyaluran_orang';
    public const SUMBER_MANUAL = 'manual';

    public const SUMBER_TRANSAKSI = [
        self::SUMBER_PENERIMAAN,
        self::SUMBER_PENYALURAN,
        self::SUMBER_PENERIMAAN_ORANG,
        self::SUMBER_PENYALURAN_ORANG,
        self::SUMBER_MANUAL,
    ];

    protected $fillable = [
        'pos_anggaran_id',
        'bidang_id',
        'sumber_transaksi',
        'jenis_dana_id',
        'sumber_dana_id',
        'asnaf_id',
        'program_id',
        'filter_via_upz',
        'filter_jenis_muzakki',
        'nama_kegiatan',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'filter_via_upz' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_anggaran_id');
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    public function jenisDana(): BelongsTo
    {
        return $this->belongsTo(JenisDana::class, 'jenis_dana_id');
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }

    public function asnaf(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'asnaf_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
}
