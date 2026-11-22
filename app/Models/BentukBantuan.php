<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BentukBantuan extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'bentuk_bantuan';

    protected $fillable = [
        'kode',
        'nama',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}
