<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RekeningBank extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'rekening_bank';

    protected $fillable = [
        'bank',
        'no_rekening',
        'atas_nama',
        'jenis_dana_id',
        'coa_id',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function jenisDana(): BelongsTo
    {
        return $this->belongsTo(JenisDana::class, 'jenis_dana_id');
    }
}
