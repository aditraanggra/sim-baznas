<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'jenis', 'nama_berkas', 'path', 'status',
        'total_baris', 'baris_sukses', 'baris_gagal', 'ringkasan',
        'mulai_pada', 'selesai_pada',
    ];

    protected function casts(): array
    {
        return [
            'ringkasan' => 'array',
            'mulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class)->orderBy('nomor_baris');
    }
}
