<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RkatJenisKegiatan extends Model
{
    use MemilikiPembuat;

    protected $table = 'rkat_jenis_kegiatan';

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Tanpa hard delete pada jenis kegiatan RKAT; gunakan status nonaktif (BR-11).'));
    }

    protected $fillable = [
        'bidang_kode',
        'kode',
        'nama',
        'pos_anggaran_id',
        'pos_program_id',
        'satuan_default',
        'keterangan',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pos_anggaran_id' => 'integer',
            'pos_program_id' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_anggaran_id');
    }

    public function posProgram(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_program_id');
    }

    public function rencana(): HasMany
    {
        return $this->hasMany(RkatKegiatanRencana::class, 'rkat_jenis_kegiatan_id');
    }
}
