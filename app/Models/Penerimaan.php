<?php

namespace App\Models;

use App\Models\Concerns\CakupanUpz;
use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Penerimaan extends Model
{
    use CakupanUpz, MemilikiPembuat;

    protected $table = 'penerimaan';

    protected $fillable = [
        'nomor',
        'tanggal',
        'status',
        'muzakki_id',
        'upz_id',
        'metode_bayar_id',
        'rekening_id',
        'berita_acara_id',
        'petugas_id',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function muzakki(): BelongsTo
    {
        return $this->belongsTo(Muzakki::class, 'muzakki_id');
    }

    public function upz(): BelongsTo
    {
        return $this->belongsTo(Upz::class, 'upz_id');
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
        return $this->belongsTo(BeritaAcaraPenerimaan::class, 'berita_acara_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PenerimaanItem::class, 'penerimaan_id');
    }

    public function kwitansi(): HasOne
    {
        return $this->hasOne(Kwitansi::class, 'penerimaan_id');
    }

    public function pembatalan(): HasOne
    {
        return $this->hasOne(PembatalanPenerimaan::class, 'penerimaan_id');
    }

    public function totalRupiah(): int
    {
        return (int) $this->items->sum('jumlah');
    }
}
