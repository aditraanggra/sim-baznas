<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'program';

    protected $fillable = [
        'kode',
        'nama',
        'bidang_id',
        'sub_bidang',
        'asnaf_id',
        'parent_id',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    public function asnaf(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'asnaf_id');
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function subProgram(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
