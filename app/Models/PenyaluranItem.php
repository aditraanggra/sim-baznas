<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenyaluranItem extends Model
{
    use MemilikiPembuat;

    protected $table = 'penyaluran_item';

    protected $fillable = [
        'penyaluran_id',
        'sumber_dana_id',
        'asnaf_id',
        'bentuk_bantuan_id',
        'uraian_barang',
        'kuantitas',
        'satuan',
        'jumlah',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'kuantitas' => 'float',
            'jumlah' => 'integer',
        ];
    }

    public function penyaluran(): BelongsTo
    {
        return $this->belongsTo(Penyaluran::class, 'penyaluran_id');
    }

    public function sumberDana(): BelongsTo
    {
        return $this->belongsTo(SumberDana::class, 'sumber_dana_id');
    }

    public function asnaf(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'asnaf_id');
    }

    public function bentukBantuan(): BelongsTo
    {
        return $this->belongsTo(BentukBantuan::class, 'bentuk_bantuan_id');
    }
}
