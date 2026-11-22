<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JenisDana extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'jenis_dana';

    protected $fillable = [
        'kode',
        'nama',
        'wajib_asnaf',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'wajib_asnaf' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function sumberDana(): HasMany
    {
        return $this->hasMany(SumberDana::class, 'jenis_dana_id');
    }
}
