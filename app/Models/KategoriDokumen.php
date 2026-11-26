<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriDokumen extends Model
{
    protected $table = 'kategori_dokumen';

    protected $fillable = [
        'kode',
        'nama',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function dokumenArsip(): HasMany
    {
        return $this->hasMany(DokumenArsip::class, 'kategori_dokumen_id');
    }
}
