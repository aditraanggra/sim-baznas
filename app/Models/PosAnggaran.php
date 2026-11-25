<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PosAnggaran extends Model
{
    use HasFactory;

    protected $table = 'pos_anggaran';

    protected $fillable = [
        'kode', 'tipe_nilai', 'nama', 'induk_id', 'path', 'lampiran', 'tingkat',
        'agregasi', 'sifat_waktu', 'rumus', 'berlaku_dari_tahun',
        'berlaku_sampai_tahun', 'aktif', 'urutan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'berlaku_dari_tahun' => 'integer',
            'berlaku_sampai_tahun' => 'integer',
            'tingkat' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'induk_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(self::class, 'induk_id')->orderBy('urutan');
    }

    public function alias(): HasMany
    {
        return $this->hasMany(PosAlias::class);
    }

    public function pemetaan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PosAnggaranPemetaan::class, 'pos_anggaran_id');
    }

    public function realisasiManual(): HasMany
    {
        return $this->hasMany(RealisasiManual::class, 'pos_anggaran_id');
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'entitas');
    }
}
