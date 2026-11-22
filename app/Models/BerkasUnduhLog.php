<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasUnduhLog extends Model
{
    protected $table = 'berkas_unduh_log';

    public $timestamps = false;

    protected $fillable = [
        'berkas_privat_id',
        'user_id',
        'aksi',
        'ip_address',
        'user_agent',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(BerkasPrivat::class, 'berkas_privat_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
