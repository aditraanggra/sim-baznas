<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembatalanPenerimaan extends Model
{
    use MemilikiPembuat;

    protected $table = 'pembatalan_penerimaan';

    protected $fillable = [
        'penerimaan_id',
        'alasan',
        'status',
        'diminta_oleh',
        'disetujui_oleh',
        'catatan_penyetuju',
        'waktu_diminta',
        'waktu_disetujui',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'waktu_diminta' => 'datetime',
            'waktu_disetujui' => 'datetime',
        ];
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(Penerimaan::class, 'penerimaan_id');
    }

    public function peminta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diminta_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }
}
