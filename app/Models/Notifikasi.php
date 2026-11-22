<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    use MemilikiPembuat;

    protected $table = 'notifikasi';

    protected $fillable = [
        'user_id',
        'jenis',
        'judul',
        'pesan',
        'tautan',
        'ref_tabel',
        'ref_id',
        'dibaca_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'ref_id' => 'integer',
            'dibaca_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->whereNull('dibaca_at');
    }
}
