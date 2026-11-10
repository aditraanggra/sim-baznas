<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkatValue extends Model
{
    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Tanpa hard delete pada RKAT; gunakan arsip (BR-11)'));
    }

    protected $fillable = ['rkat_version_id', 'pos_anggaran_id', 'kode_asal', 'nama_asal', 'tipe_nilai', 'nilai'];

    protected function casts(): array
    {
        return ['nilai' => 'integer'];
    }

    public function versi(): BelongsTo
    {
        return $this->belongsTo(RkatVersion::class, 'rkat_version_id');
    }

    public function pos(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_anggaran_id');
    }
}
