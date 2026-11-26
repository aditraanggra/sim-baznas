<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TautanAkses extends Model
{
    public const JENIS_PORTAL_PIMPINAN = 'portal_pimpinan';

    public const JENIS_LAPORAN_KINERJA = 'laporan_kinerja';

    public const JENIS_ARSIP = 'arsip';

    public const DAFTAR_JENIS = [
        self::JENIS_PORTAL_PIMPINAN,
        self::JENIS_LAPORAN_KINERJA,
        self::JENIS_ARSIP,
    ];

    protected $table = 'tautan_akses';

    protected $fillable = [
        'label',
        'jenis',
        'token_hash',
        'token_prefix',
        'kadaluarsa_at',
        'dicabut_at',
        'catatan',
        'dibuat_oleh',
        'dicabut_oleh',
        'terakhir_diakses_at',
        'jumlah_akses',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'kadaluarsa_at' => 'datetime',
            'dicabut_at' => 'datetime',
            'terakhir_diakses_at' => 'datetime',
            'jumlah_akses' => 'integer',
            'dibuat_oleh' => 'integer',
            'dicabut_oleh' => 'integer',
        ];
    }

    public function masihAktif(): bool
    {
        return $this->dicabut_at === null
            && ($this->kadaluarsa_at === null || $this->kadaluarsa_at->isFuture());
    }

    public function statusKode(): string
    {
        if ($this->dicabut_at !== null) {
            return 'dicabut';
        }

        if ($this->kadaluarsa_at !== null && $this->kadaluarsa_at->isPast()) {
            return 'kadaluarsa';
        }

        return 'aktif';
    }

    public function bolehKinerja(): bool
    {
        return in_array($this->jenis, [self::JENIS_PORTAL_PIMPINAN, self::JENIS_LAPORAN_KINERJA], true);
    }

    public function bolehArsip(): bool
    {
        return in_array($this->jenis, [self::JENIS_PORTAL_PIMPINAN, self::JENIS_ARSIP], true);
    }

    public static function labelJenis(string $jenis): string
    {
        return match ($jenis) {
            self::JENIS_PORTAL_PIMPINAN => 'Portal Pimpinan (Kinerja + Arsip PKP)',
            self::JENIS_LAPORAN_KINERJA => 'Laporan Kinerja ZIS',
            self::JENIS_ARSIP => 'Arsip Dokumen PKP (PPID)',
            default => $jenis,
        };
    }

    public static function cariDariToken(string $token): ?self
    {
        if ($token === '') {
            return null;
        }

        return self::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }

    public static function cariAktifDariToken(string $token): ?self
    {
        $tautan = self::cariDariToken($token);

        if (! $tautan || ! $tautan->masihAktif()) {
            return null;
        }

        return $tautan;
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pencabut(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicabut_oleh');
    }

    public function aksesLogs(): HasMany
    {
        return $this->hasMany(AksesDokumenLog::class, 'tautan_akses_id');
    }
}
