<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SuratMasuk extends Model
{
    use MemilikiPembuat;

    public const SIFAT = [
        'biasa' => 'Biasa',
        'penting' => 'Penting',
        'segera' => 'Segera',
        'rahasia' => 'Rahasia',
    ];

    protected $table = 'surat_masuk';

    protected $fillable = [
        'uuid',
        'nomor_agenda',
        'nomor_surat',
        'tanggal_surat',
        'tanggal_terima',
        'pengirim',
        'perihal',
        'ringkasan',
        'sifat',
        'status_kode',
        'status_id',
        'berkas_privat_id',
        'dicatat_oleh',
        'selesai_at',
        'catatan_penutupan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_terima' => 'date',
            'selesai_at' => 'datetime',
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
            'diterima_arsip' => 'Diterima Admin Arsip',
            'diteruskan_ketua' => 'Diteruskan ke Ketua',
            'disposisi_waka' => 'Disposisi Wakil Ketua',
            'tindak_lanjut' => 'Tindak Lanjut Bawahan',
            'selesai' => 'Selesai',
            default => $kode,
        };
    }

    public function statusDokumen(): BelongsTo
    {
        return $this->belongsTo(StatusDokumen::class, 'status_id');
    }

    public function berkasPrivat(): BelongsTo
    {
        return $this->belongsTo(BerkasPrivat::class, 'berkas_privat_id');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function statusLog(): HasMany
    {
        return $this->hasMany(SuratMasukStatusLog::class, 'surat_masuk_id')->orderBy('waktu')->orderBy('id');
    }

    public function disposisi(): HasMany
    {
        return $this->hasMany(DisposisiSurat::class, 'surat_masuk_id')->orderBy('urutan')->orderBy('id');
    }

    public function suratBalasan(): HasMany
    {
        return $this->hasMany(SuratKeluar::class, 'surat_masuk_id');
    }
}
