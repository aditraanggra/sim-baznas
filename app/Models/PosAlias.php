<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosAlias extends Model
{
    protected $table = 'pos_alias';

    protected $fillable = ['pos_anggaran_id', 'tahun', 'kode_alias', 'nama_alias'];

    protected function casts(): array
    {
        return ['tahun' => 'integer'];
    }

    public function pos(): BelongsTo
    {
        return $this->belongsTo(PosAnggaran::class, 'pos_anggaran_id');
    }
}
