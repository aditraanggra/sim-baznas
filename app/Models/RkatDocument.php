<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkatDocument extends Model
{
    public const JENIS = ['surat_pengesahan', 'lembar_penetapan'];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Tanpa hard delete pada RKAT; gunakan arsip (BR-11)'));
    }

    protected $fillable = ['rkat_version_id', 'jenis', 'nama_berkas', 'path', 'mime', 'ukuran', 'diunggah_oleh', 'diarsipkan_pada'];

    protected function casts(): array
    {
        return ['diarsipkan_pada' => 'datetime'];
    }

    public function versi(): BelongsTo
    {
        return $this->belongsTo(RkatVersion::class, 'rkat_version_id');
    }
}
