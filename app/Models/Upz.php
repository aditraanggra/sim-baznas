<?php

namespace App\Models;

use App\Models\Concerns\CakupanUpz;
use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upz extends Model
{
    use CakupanUpz, MemilikiPembuat, SoftDeletes;

    protected $table = 'upz';

    protected $fillable = [
        'kode',
        'nama',
        'nomor_sk',
        'jenis_upz_id',
        'desa_id',
        'ketua',
        'sekretaris',
        'bendahara',
        'alamat',
        'pic_nama',
        'pic_telepon',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function scopeUntukPengguna($query, ?User $user = null, string $kolomUpz = 'id')
    {
        $user ??= auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->adalahUpzEksternal()) {
            if ($user->upz_id === null) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where($query->qualifyColumn($kolomUpz), $user->upz_id);
        }

        return $query;
    }

    public function jenisUpz(): BelongsTo
    {
        return $this->belongsTo(JenisUpz::class, 'jenis_upz_id');
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    public function skUpz(): HasMany
    {
        return $this->hasMany(SkUpz::class, 'upz_id')->orderByDesc('berlaku_sampai');
    }

    public function skTerbaru(): HasOne
    {
        return $this->hasOne(SkUpz::class, 'upz_id')->latestOfMany('berlaku_sampai');
    }

    public function penerimaan(): HasMany
    {
        return $this->hasMany(Penerimaan::class, 'upz_id');
    }
}
