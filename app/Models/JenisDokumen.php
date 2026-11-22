<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisDokumen extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'jenis_dokumen';

    protected $fillable = [
        'kode',
        'nama',
        'kelompok',
        'wajib',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'wajib' => 'boolean',
            'aktif' => 'boolean',
        ];
    }
}
