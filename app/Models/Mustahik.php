<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mustahik extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'mustahik';

    protected $fillable = [
        'kode',
        'jenis',
        'nama',
        'nik_encrypted',
        'nik_hash',
        'nik_masked',
        'telepon',
        'telepon_norm',
        'alamat',
        'kecamatan_id',
        'desa_id',
        'penanggung_jawab',
        'status_verifikasi',
        'diverifikasi_oleh',
        'waktu_verifikasi',
        'catatan_verifikasi',
        'aktif',
        'created_by',
    ];

    protected $hidden = [
        'nik_encrypted',
        'nik_hash',
    ];

    protected function casts(): array
    {
        return [
            'waktu_verifikasi' => 'datetime',
            'aktif' => 'boolean',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function asnaf(): BelongsToMany
    {
        return $this->belongsToMany(Asnaf::class, 'mustahik_asnaf', 'mustahik_id', 'asnaf_id')
            ->withPivot('utama')
            ->withTimestamps();
    }

    public function asnafLinks(): HasMany
    {
        return $this->hasMany(MustahikAsnaf::class, 'mustahik_id');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(MustahikDokumen::class, 'mustahik_id');
    }

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class, 'mustahik_id');
    }

    public function penyaluran(): HasMany
    {
        return $this->hasMany(Penyaluran::class, 'mustahik_id');
    }
}
