<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MustahikDokumen extends Model
{
    use MemilikiPembuat;

    protected $table = 'mustahik_dokumen';

    protected $fillable = [
        'mustahik_id',
        'jenis_dokumen_id',
        'berkas_privat_id',
        'keterangan',
        'created_by',
    ];

    public function mustahik(): BelongsTo
    {
        return $this->belongsTo(Mustahik::class, 'mustahik_id');
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
