<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asnaf extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'asnaf';

    protected $fillable = [
        'kode',
        'nama',
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
        return $this->hasMany(Program::class, 'asnaf_id');
    }
}
