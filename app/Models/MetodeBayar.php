<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetodeBayar extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'metode_bayar';

    protected $fillable = [
        'kode',
        'nama',
        'butuh_rekening',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'butuh_rekening' => 'boolean',
            'aktif' => 'boolean',
        ];
    }
}
