<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Muzakki extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'muzakki';

    protected $fillable = [
        'kode',
        'npwz',
        'jenis',
        'nama',
        'jenis_kelamin',
        'nik_encrypted',
        'nik_hash',
        'nik_masked',
        'npwp',
        'telepon',
        'telepon_norm',
        'email',
        'alamat',
        'kecamatan_id',
        'desa_id',
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

    public function penerimaan(): HasMany
    {
        return $this->hasMany(Penerimaan::class, 'muzakki_id');
    }
}
