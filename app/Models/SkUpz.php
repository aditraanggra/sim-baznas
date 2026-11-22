<?php

namespace App\Models;

use App\Models\Concerns\CakupanUpz;
use App\Models\Concerns\MemilikiPembuat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkUpz extends Model
{
    use CakupanUpz, MemilikiPembuat, SoftDeletes;

    protected $table = 'sk_upz';

    protected $fillable = [
        'upz_id',
        'nomor',
        'tanggal_sk',
        'berlaku_mulai',
        'berlaku_sampai',
        'berkas_privat_id',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_sk' => 'date',
            'berlaku_mulai' => 'date',
            'berlaku_sampai' => 'date',
        ];
    }

    public function upz(): BelongsTo
    {
        return $this->belongsTo(Upz::class, 'upz_id');
    }

    public function berkasPrivat(): BelongsTo
    {
        return $this->belongsTo(BerkasPrivat::class, 'berkas_privat_id');
    }

    public function sisaHari(): int
    {
        return (int) Carbon::today()->diffInDays($this->berlaku_sampai, false);
    }

    public function statusBerlaku(): string
    {
        $sisa = $this->sisaHari();
        if ($sisa < 0) {
            return 'habis';
        }
        if ($sisa <= 90) {
            return 'segera_habis';
        }

        return 'aktif';
    }
}
