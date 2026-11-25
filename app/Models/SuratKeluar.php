<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SuratKeluar extends Model
{
    use MemilikiPembuat;

    public const SIFAT = [
        'biasa' => 'Biasa',
        'penting' => 'Penting',
        'segera' => 'Segera',
        'rahasia' => 'Rahasia',
    ];

    protected $table = 'surat_keluar';

    protected $fillable = [
        'uuid',
        'nomor',
        'jenis_surat_id',
        'template_surat_id',
        'surat_masuk_id',
        'tanggal',
        'sifat',
        'lampiran',
        'tujuan',
        'alamat_tujuan',
        'perihal',
        'isi',
        'data_placeholder',
        'tembusan',
        'nama_penandatangan',
        'jabatan_penandatangan',
        'status_kode',
        'status_id',
        'berkas_lampiran_id',
        'dibuat_oleh',
        'diajukan_at',
        'disetujui_oleh',
        'disetujui_at',
        'catatan_revisi',
        'dibatalkan_oleh',
        'dibatalkan_at',
        'alasan_batal',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'data_placeholder' => 'array',
            'diajukan_at' => 'datetime',
            'disetujui_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $surat): void {
            if (empty($surat->uuid)) {
                $surat->uuid = (string) Str::uuid();
            }
        });
    }

    public static function labelStatus(string $kode): string
    {
        return match ($kode) {
            'draft' => 'Draft',
            'menunggu_persetujuan' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui / Final',
            'dibatalkan' => 'Dibatalkan',
            default => $kode,
        };
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }

    public function templateSurat(): BelongsTo
    {
        return $this->belongsTo(TemplateSurat::class, 'template_surat_id');
    }

    public function suratMasuk(): BelongsTo
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function statusDokumen(): BelongsTo
    {
        return $this->belongsTo(StatusDokumen::class, 'status_id');
    }

    public function berkasLampiran(): BelongsTo
    {
        return $this->belongsTo(BerkasPrivat::class, 'berkas_lampiran_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function pembatal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function cetakLogs(): HasMany
    {
        return $this->hasMany(SuratKeluarCetakLog::class, 'surat_keluar_id')->orderBy('waktu');
    }
}
