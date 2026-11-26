<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DokumenArsip extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_DISAHKAN = 'disahkan';

    public const STATUS_DICABUT = 'dicabut';

    protected $table = 'dokumen_arsip';

    protected $fillable = [
        'uuid',
        'kategori_dokumen_id',
        'bidang_id',
        'tahun',
        'nomor',
        'judul',
        'versi',
        'induk_versi_id',
        'tanggal_pengesahan',
        'ringkasan',
        'berkas_privat_id',
        'status',
        'diunggah_oleh',
        'disahkan_oleh',
        'disahkan_at',
        'dicabut_oleh',
        'dicabut_at',
        'alasan_cabut',
    ];

    protected function casts(): array
    {
        return [
            'kategori_dokumen_id' => 'integer',
            'bidang_id' => 'integer',
            'tahun' => 'integer',
            'versi' => 'integer',
            'induk_versi_id' => 'integer',
            'tanggal_pengesahan' => 'date',
            'berkas_privat_id' => 'integer',
            'diunggah_oleh' => 'integer',
            'disahkan_oleh' => 'integer',
            'disahkan_at' => 'datetime',
            'dicabut_oleh' => 'integer',
            'dicabut_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $dokumen): void {
            if (empty($dokumen->uuid)) {
                $dokumen->uuid = (string) Str::uuid();
            }
        });
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriDokumen::class, 'kategori_dokumen_id');
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(BerkasPrivat::class, 'berkas_privat_id');
    }

    public function indukVersi(): BelongsTo
    {
        return $this->belongsTo(self::class, 'induk_versi_id');
    }

    public function versiTurunan(): HasMany
    {
        return $this->hasMany(self::class, 'induk_versi_id');
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    public function pengesah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disahkan_oleh');
    }

    public function pencabut(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicabut_oleh');
    }

    public function aksesLogs(): HasMany
    {
        return $this->hasMany(AksesDokumenLog::class, 'dokumen_arsip_id');
    }
}
