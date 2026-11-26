<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RkatTahapBidang extends Model
{
    use MemilikiPembuat;

    protected $table = 'rkat_tahap_bidang';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_TERVERIFIKASI = 'terverifikasi';

    public const BIDANG = ['pengumpulan', 'distribusi', 'umum'];

    protected $fillable = [
        'rkat_version_id',
        'bidang_kode',
        'status',
        'diajukan_pada',
        'diajukan_oleh',
        'diverifikasi_pada',
        'diverifikasi_oleh',
        'catatan_verifikasi',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rkat_version_id' => 'integer',
            'diajukan_pada' => 'datetime',
            'diajukan_oleh' => 'integer',
            'diverifikasi_pada' => 'datetime',
            'diverifikasi_oleh' => 'integer',
        ];
    }

    public function versi(): BelongsTo
    {
        return $this->belongsTo(RkatVersion::class, 'rkat_version_id');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
