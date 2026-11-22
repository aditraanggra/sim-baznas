<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provinsi extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'provinsi';

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

    public function kabupaten(): HasMany
    {
        return $this->hasMany(Kabupaten::class, 'provinsi_id');
    }
}
