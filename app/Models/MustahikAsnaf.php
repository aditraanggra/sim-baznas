<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MustahikAsnaf extends Model
{
    use MemilikiPembuat;

    protected $table = 'mustahik_asnaf';

    protected $fillable = [
        'mustahik_id',
        'asnaf_id',
        'utama',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'utama' => 'boolean',
        ];
    }

    public function mustahik(): BelongsTo
    {
        return $this->belongsTo(Mustahik::class, 'mustahik_id');
    }

    public function asnaf(): BelongsTo
    {
        return $this->belongsTo(Asnaf::class, 'asnaf_id');
    }
}
