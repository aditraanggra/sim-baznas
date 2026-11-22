<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SumberDana extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'sumber_dana';

    protected $fillable = [
        'kode',
        'nama',
        'jenis_dana_id',
        'dana_kelompok_id',
        'terikat',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'terikat' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function jenisDana(): BelongsTo
    {
        return $this->belongsTo(JenisDana::class, 'jenis_dana_id');
    }

    public function danaKelompok(): BelongsTo
    {
        return $this->belongsTo(DanaKelompok::class, 'dana_kelompok_id');
    }
}
