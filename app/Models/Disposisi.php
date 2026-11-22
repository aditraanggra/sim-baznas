<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Disposisi extends Model
{
    use MemilikiPembuat;

    protected $table = 'disposisi';

    protected $fillable = [
        'nomor',
        'permohonan_id',
        'langkah',
        'status_dari',
        'status_ke',
        'dari_user_id',
        'ke_user_id',
        'ke_peran',
        'status',
        'nominal_usulan',
        'instruksi',
        'catatan',
        'waktu_tindak_lanjut',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'langkah' => 'integer',
            'nominal_usulan' => 'integer',
            'waktu_tindak_lanjut' => 'datetime',
        ];
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    public function dariUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function keUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ke_user_id');
    }
}
