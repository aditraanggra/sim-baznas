<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kwitansi extends Model
{
    use MemilikiPembuat;

    protected $table = 'kwitansi';

    protected $fillable = [
        'nomor',
        'token_verifikasi',
        'penerimaan_id',
        'tanggal_terbit',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
        ];
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(Penerimaan::class, 'penerimaan_id');
    }

    public function cetakLogs(): HasMany
    {
        return $this->hasMany(KwitansiCetakLog::class, 'kwitansi_id')->orderBy('cetak_ke');
    }
}
