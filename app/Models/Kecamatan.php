<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kecamatan extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'kecamatan';

    protected $fillable = [
        'kabupaten_id',
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

    public function kabupaten(): BelongsTo
    {
        return $this->belongsTo(Kabupaten::class, 'kabupaten_id');
    }

    public function desa(): HasMany
    {
        return $this->hasMany(Desa::class, 'kecamatan_id');
    }
}
