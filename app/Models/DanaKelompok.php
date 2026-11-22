<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DanaKelompok extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'dana_kelompok';

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

    public function sumberDana(): HasMany
    {
        return $this->hasMany(SumberDana::class, 'dana_kelompok_id');
    }
}
