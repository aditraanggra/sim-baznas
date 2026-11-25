<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisposisiSurat extends Model
{
    use MemilikiPembuat;

    protected $table = 'disposisi_surat';

    protected $fillable = [
        'surat_masuk_id',
        'parent_disposisi_id',
        'tahap',
        'urutan',
        'dari_user_id',
        'kepada_user_id',
        'kepada_peran',
        'instruksi',
        'tenggat',
        'keputusan',
        'status',
        'catatan_tindak_lanjut',
        'berkas_bukti_id',
        'waktu',
        'selesai_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'tenggat' => 'date',
            'waktu' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_disposisi_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(self::class, 'parent_disposisi_id')->orderBy('id');
    }

    public function dariUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function kepadaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kepada_user_id');
    }

    public function berkasBukti(): BelongsTo
    {
        return $this->belongsTo(BerkasPrivat::class, 'berkas_bukti_id');
    }
}
