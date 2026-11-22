<?php

namespace App\Models;

use App\Models\Concerns\CakupanUpz;
use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BerkasPrivat extends Model
{
    use CakupanUpz, MemilikiPembuat, SoftDeletes;

    protected $table = 'berkas_privat';

    protected $fillable = [
        'uuid',
        'kelompok',
        'entitas_tipe',
        'entitas_id',
        'disk',
        'path',
        'nama_asli',
        'mime',
        'ukuran',
        'checksum_sha256',
        'bidang_kode',
        'upz_id',
        'izinkan_publik_bertanda_tangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entitas_id' => 'integer',
            'ukuran' => 'integer',
            'upz_id' => 'integer',
            'izinkan_publik_bertanda_tangan' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $berkas): void {
            if (empty($berkas->uuid)) {
                $berkas->uuid = (string) Str::uuid();
            }
        });
    }

    public function unduhLogs(): HasMany
    {
        return $this->hasMany(BerkasUnduhLog::class, 'berkas_privat_id');
    }
}
