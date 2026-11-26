<?php

namespace Database\Seeders;

use App\Models\KategoriDokumen;
use Illuminate\Database\Seeder;

class KategoriDokumenSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['kode' => 'RKAT', 'nama' => 'RKAT', 'urutan' => 1],
            ['kode' => 'RENJA', 'nama' => 'RENJA', 'urutan' => 2],
            ['kode' => 'RENSTRA', 'nama' => 'RENSTRA', 'urutan' => 3],
            ['kode' => 'LAP_KINERJA', 'nama' => 'Laporan Kinerja', 'urutan' => 4],
            ['kode' => 'LAP_KEU_AUDITED', 'nama' => 'Laporan Keuangan Audited', 'urutan' => 5],
            ['kode' => 'LAP_KEU_UNAUDITED', 'nama' => 'Laporan Keuangan Unaudited', 'urutan' => 6],
            ['kode' => 'SOP', 'nama' => 'SOP', 'urutan' => 7],
            ['kode' => 'LAINNYA', 'nama' => 'Dokumen Lainnya', 'urutan' => 8],
        ];

        foreach ($daftar as $item) {
            KategoriDokumen::updateOrCreate(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'urutan' => $item['urutan'],
                    'aktif' => true,
                ],
            );
        }
    }
}
