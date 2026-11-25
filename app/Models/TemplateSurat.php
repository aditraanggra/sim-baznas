<?php

namespace App\Models;

use App\Models\Concerns\MemilikiPembuat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TemplateSurat extends Model
{
    use MemilikiPembuat, SoftDeletes;

    protected $table = 'template_surat';

    protected $fillable = [
        'jenis_surat_id',
        'nama',
        'isi',
        'penutup_default',
        'aktif',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'jenis_surat_id' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class, 'jenis_surat_id');
    }
}
