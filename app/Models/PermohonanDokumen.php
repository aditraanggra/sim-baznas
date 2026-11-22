<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermohonanDokumen extends Model
{
    use MemilikiPembuat;

    protected $table = 'permohonan_dokumen';

    protected $fillable = [
        'permohonan_id',
        'jenis_dokumen_id',
        'berkas_privat_id',
        'keterangan',
        'created_by',
    ];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    public function jenisDokumen(): BelongsTo
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen_id');
    }

    public function berkasPrivat(): BelongsTo
    {
        return $this->belongsTo(BerkasPrivat::class, 'berkas_privat_id');
    }
}
