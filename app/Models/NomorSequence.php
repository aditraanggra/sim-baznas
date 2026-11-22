<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomorSequence extends Model
{
    protected $table = 'nomor_sequence';

    protected $fillable = [
        'jenis_nomor',
        'tahun',
        'nilai_terakhir',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'nilai_terakhir' => 'integer',
        ];
    }
}
