<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisUpz extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'jenis_upz';

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

    public function upz(): HasMany
    {
        return $this->hasMany(Upz::class, 'jenis_upz_id');
    }
}
