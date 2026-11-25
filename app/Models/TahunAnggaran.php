<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAnggaran extends Model
{
    use HasFactory, MemilikiPembuat;

    protected $table = 'tahun_anggaran';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_BERJALAN = 'berjalan';
    public const STATUS_TUTUP = 'tutup';

    public const STATUS = [
        self::STATUS_DRAFT,
        self::STATUS_BERJALAN,
        self::STATUS_TUTUP,
    ];

    protected $fillable = [
        'tahun',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'ditutup_pada',
        'ditutup_oleh',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'ditutup_pada' => 'datetime',
        ];
    }

    public function rkatVersions(): HasMany
    {
        return $this->hasMany(RkatVersion::class, 'tahun_anggaran_id');
    }

    public function penutup(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }

    public function isTutup(): bool
    {
        return $this->status === self::STATUS_TUTUP;
    }

    public function isBerjalan(): bool
    {
        return $this->status === self::STATUS_BERJALAN;
    }
}
