<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkatNote extends Model
{
    public const KOLOM = [
        'batas_amil_zakat_bp', 'batas_amil_infak_bp', 'batas_amil_csr_bp', 'batas_amil_dskl_bp',
        'target_orang', 'target_pengentasan_ekstrem', 'target_pengentasan_garis', 'catatan',
    ];

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Tanpa hard delete pada RKAT; gunakan arsip (BR-11)'));
    }

    protected $fillable = ['rkat_version_id', ...self::KOLOM];

    public function versi(): BelongsTo
    {
        return $this->belongsTo(RkatVersion::class, 'rkat_version_id');
    }

    /** @return array{zakat: ?int, infak: ?int, csr: ?int, dskl: ?int} batas dalam basis poin */
    public function batasBp(): array
    {
        return [
            'zakat' => $this->batas_amil_zakat_bp,
            'infak' => $this->batas_amil_infak_bp,
            'csr' => $this->batas_amil_csr_bp,
            'dskl' => $this->batas_amil_dskl_bp,
        ];
    }
}
