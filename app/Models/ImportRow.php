<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    protected $fillable = ['import_job_id', 'jenis', 'nomor_baris', 'kunci', 'status', 'galat', 'data'];

    protected function casts(): array
    {
        return [
            'galat' => 'array',
            'data' => 'array',
            'nomor_baris' => 'integer',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'import_job_id');
    }
}
