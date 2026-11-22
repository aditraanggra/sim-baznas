<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penyaluran extends Model
{
    use MemilikiPembuat;

    protected $table = 'penyaluran';

    protected $fillable = [
        'nomor',
        'tanggal',
        'status',
        'permohonan_id',
        'mustahik_id',
        'program_id',
        'sub_bidang',
        'metode_bayar_id',
        'rekening_id',
        'berita_acara_id',
        'petugas_id',
        'disetujui_oleh',
        'disetujui_at',
        'dicairkan_oleh',
        'dicairkan_at',
        'bukti_serah_terima_id',
        'peringatan_saldo',
        'catatan',
        'alasan_batal',
        'dibatalkan_oleh',
        'dibatalkan_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'disetujui_at' => 'datetime',
            'dicairkan_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function totalRupiah(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum('jumlah');
        }

        return (int) $this->items()->sum('jumlah');
    }

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class, 'permohonan_id');
    }

    public function mustahik(): BelongsTo
    {
        return $this->belongsTo(Mustahik::class, 'mustahik_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function metodeBayar(): BelongsTo
    {
        return $this->belongsTo(MetodeBayar::class, 'metode_bayar_id');
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(RekeningBank::class, 'rekening_id');
    }

    public function beritaAcara(): BelongsTo
    {
        return $this->belongsTo(BeritaAcaraPenyaluran::class, 'berita_acara_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function dicairkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicairkan_oleh');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function buktiSerahTerima(): BelongsTo
    {
        return $this->belongsTo(BerkasPrivat::class, 'bukti_serah_terima_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PenyaluranItem::class, 'penyaluran_id');
    }
}
