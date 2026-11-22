<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bidang extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'bidang';

    protected $fillable = [
        'kode',
        'nama',
        'warna',
        'urutan',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function program(): HasMany
    {
        return $this->hasMany(Program::class, 'bidang_id');
    }
}
