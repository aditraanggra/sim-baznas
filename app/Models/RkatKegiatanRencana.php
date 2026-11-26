<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkatKegiatanRencana extends Model
{
    use MemilikiPembuat;

    protected $table = 'rkat_kegiatan_rencana';

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Tanpa hard delete pada rincian kegiatan RKAT; gunakan arsip (BR-11).'));
    }

    protected $fillable = [
        'rkat_version_id',
        'bidang_kode',
        'rkat_jenis_kegiatan_id',
        'nama_kegiatan',
        'pos_anggaran_id',
        'pos_program_id',
        'jumlah',
        'satuan',
        'frekuensi',
        'besaran',
        'total',
        'keterangan',
        'diarsipkan_pada',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rkat_version_id' => 'integer',
            'rkat_jenis_kegiatan_id' => 'integer',
            'pos_anggaran_id' => 'integer',
            'pos_program_id' => 'integer',
            'jumlah' => 'integer',
            'frekuensi' => 'integer',
            'besaran' => 'integer',
            'total' => 'integer',
            'diarsipkan_pada' => 'datetime',
        ];
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->whereNull('diarsipkan_pada');
    }

    public function versi(): BelongsTo
    {
        return $this->belongsTo(RkatVersion::class, 'rkat_version_id');
    }

    public function jenisKegiatan(): BelongsTo
    {
        return $this->belongsTo(RkatJenisKegiatan::class, 'rkat_jenis_kegiatan_id');
    }

    public function posAnggaran(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_anggaran_id');
    }

    public function posProgram(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_program_id');
    }
}
