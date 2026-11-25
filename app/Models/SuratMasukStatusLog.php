<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratMasukStatusLog extends Model
{
    use MemilikiPembuat;

    protected $table = 'surat_masuk_status_log';

    protected $fillable = [
        'surat_masuk_id',
        'status_kode',
        'status_id',
        'user_id',
        'waktu',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function statusDokumen(): BelongsTo
    {
        return $this->belongsTo(StatusDokumen::class, 'status_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
