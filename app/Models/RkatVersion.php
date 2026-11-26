<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RkatVersion extends Model
{
    public const STATUS = ['draft', 'diajukan', 'disahkan', 'digantikan'];

    public const MODE_MANUAL = 'manual';

    public const MODE_KOLABORATIF = 'kolaboratif';

    protected static function booted(): void
    {
        static::deleting(fn () => throw new \LogicException('Tanpa hard delete pada RKAT; gunakan arsip (BR-11)'));
    }

    protected $fillable = [
        'tahun', 'tahun_anggaran_id', 'jenis', 'nomor_perubahan', 'status', 'sumber',
        'mode_penyusunan', 'target_pengumpulan', 'target_ditetapkan_pada', 'target_ditetapkan_oleh',
        'nomor_surat', 'tanggal_surat', 'disahkan_pada', 'disahkan_oleh', 'dibuat_oleh', 'disalin_dari_id', 'arsip',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'tahun_anggaran_id' => 'integer',
            'nomor_perubahan' => 'integer',
            'target_pengumpulan' => 'integer',
            'target_ditetapkan_pada' => 'datetime',
            'target_ditetapkan_oleh' => 'integer',
            'tanggal_surat' => 'date',
            'disahkan_pada' => 'datetime',
            'arsip' => 'boolean',
        ];
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class, 'tahun_anggaran_id');
    }

    public function nilai(): HasMany
    {
        return $this->hasMany(RkatValue::class);
    }

    public function catatan(): HasOne
    {
        return $this->hasOne(RkatNote::class);
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(RkatDocument::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penetapTarget(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_ditetapkan_oleh');
    }

    public function aturanAmil(): HasMany
    {
        return $this->hasMany(RkatAturanAmil::class, 'rkat_version_id')->orderBy('urutan');
    }

    public function tahapBidang(): HasMany
    {
        return $this->hasMany(RkatTahapBidang::class, 'rkat_version_id');
    }

    public function kegiatanRencana(): HasMany
    {
        return $this->hasMany(RkatKegiatanRencana::class, 'rkat_version_id');
    }

    public function pelengkapBidang(): HasMany
    {
        return $this->hasMany(RkatPelengkapBidang::class, 'rkat_version_id');
    }

    public function isKolaboratif(): bool
    {
        return ($this->mode_penyusunan ?? self::MODE_MANUAL) === self::MODE_KOLABORATIF;
    }

    /** BR-02: hanya draft yang boleh diubah. */
    public function terkunci(): bool
    {
        return $this->status !== 'draft';
    }

    public function label(): string
    {
        return $this->jenis === 'awal' ? 'Awal' : 'Perubahan '.$this->nomor_perubahan;
    }
}
