<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kabupaten extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'kabupaten';

    protected $fillable = [
        'provinsi_id',
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

    public function provinsi(): BelongsTo
    {
        return $this->belongsTo(Provinsi::class, 'provinsi_id');
    }

    public function kecamatan(): HasMany
    {
        return $this->hasMany(Kecamatan::class, 'kabupaten_id');
    }
}
