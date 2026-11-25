<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permohonan extends Model
{
    use MemilikiPembuat;

    protected $table = 'permohonan';

    protected $fillable = [
        'nomor',
        'kode_tracking',
        'tanggal',
        'mustahik_id',
        'pemohon_sama_mustahik',
        'pemohon_nama',
        'pemohon_telepon',
        'pemohon_hubungan',
        'pemohon_alamat',
        'program_id',
        'sub_bidang',
        'jenis_dana_id',
        'asnaf_id',
        'nominal_diajukan',
        'nominal_disetujui',
        'status_kode',
        'status_publik',
        'langkah_sekarang',
        'petugas_lapangan_id',
        'kesiapan_keuangan',
        'keuangan_diproses_oleh',
        'keuangan_siap_at',
        'catatan_asesmen',
        'uraian',
        'alasan_tolak',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'pemohon_sama_mustahik' => 'boolean',
            'nominal_diajukan' => 'integer',
            'nominal_disetujui' => 'integer',
            'langkah_sekarang' => 'integer',
            'keuangan_siap_at' => 'datetime',
        ];
    }

    public function namaPemohonEfektif(): ?string
    {
        return $this->pemohon_sama_mustahik
            ? $this->mustahik?->nama
            : ($this->pemohon_nama ?: $this->mustahik?->nama);
    }

    public function teleponPemohonEfektif(): ?string
    {
        return $this->pemohon_sama_mustahik
            ? $this->mustahik?->telepon
            : ($this->pemohon_telepon ?: $this->mustahik?->telepon);
    }

    /**
     * Pemetaan 8 status internal K8 -> 7 status publik Q-14.
     */
    public static function statusPublikUntuk(string $statusKode): string
    {
        return match ($statusKode) {
            'diajukan' => 'diajukan',
            'disposisi_ketua' => 'disposisi',
            'asesmen_kabag' => 'verifikasi',
            'ajuan_waka_2' => 'acc_pimpinan',
            'acc_ketua', 'minta_keuangan' => 'disetujui',
            'diterima_mustahik' => 'dicairkan',
            'ditolak' => 'ditolak',
            default => 'diajukan',
        };
    }

    public static function judulPublikUntuk(string $statusPublik): string
    {
        return match ($statusPublik) {
            'diajukan' => 'Permohonan Diajukan',
            'disposisi' => 'Dalam Disposisi Pimpinan',
            'verifikasi' => 'Verifikasi dan Asesmen Lapangan',
            'acc_pimpinan' => 'Pembahasan Pimpinan',
            'disetujui' => 'Permohonan Disetujui',
            'dicairkan' => 'Bantuan Telah Diserahkan',
            'ditolak' => 'Permohonan Tidak Disetujui',
            default => 'Dalam Proses',
        };
    }

    public function mustahik(): BelongsTo
    {
        return $this->belongsTo(Mustahik::class, 'mustahik_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function jenisDana(): BelongsTo
    {
        return $this->belongsTo(JenisDana::class, 'jenis_dana_id');
    }

    public function asnaf(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'asnaf_id');
    }

    public function petugasLapangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_lapangan_id');
    }

    public function keuanganDiprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'keuangan_diproses_oleh');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(PermohonanDokumen::class, 'permohonan_id');
    }

    public function disposisi(): HasMany
    {
        return $this->hasMany(Disposisi::class, 'permohonan_id')->orderBy('langkah')->orderBy('id');
    }

    public function statusLog(): HasMany
    {
        return $this->hasMany(PermohonanStatusLog::class, 'permohonan_id')->orderBy('waktu')->orderBy('id');
    }

    public function penyaluran(): HasMany
    {
        return $this->hasMany(Penyaluran::class, 'permohonan_id');
    }
}
