<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusDokumen extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'status_dokumen';

    protected $fillable = [
        'kelompok',
        'kode',
        'nama',
        'urutan',
        'warna',
        'tampil_publik',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'tampil_publik' => 'boolean',
            'aktif' => 'boolean',
        ];
    }
}
