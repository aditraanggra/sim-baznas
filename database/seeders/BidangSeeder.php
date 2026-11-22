<?php

namespace Database\Seeders;

use App\Models\Bidang;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BidangSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (config('sisfozis.bidang', []) as $item) {
                Bidang::updateOrCreate(
                    ['kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'warna' => $item['warna'],
                        'urutan' => $item['urutan'],
                        'aktif' => true,
                    ],
                );
            }
        });
    }
}
