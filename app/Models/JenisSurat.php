<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisSurat extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'jenis_surat';

    protected $fillable = [
        'kode',
        'nama',
        'format_nomor',
        'pisah_urutan',
        'keterangan',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'pisah_urutan' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function templates(): HasMany
    {
        return $this->hasMany(TemplateSurat::class, 'jenis_surat_id');
    }

    public function suratKeluar(): HasMany
    {
        return $this->hasMany(SuratKeluar::class, 'jenis_surat_id');
    }
}
