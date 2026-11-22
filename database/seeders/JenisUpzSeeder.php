<?php

namespace Database\Seeders;

use App\Models\JenisUpz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisUpzSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ([
                ['kode' => 'DKM', 'nama' => 'DKM / Masjid'],
                ['kode' => 'DESA', 'nama' => 'Desa / Kelurahan'],
                ['kode' => 'DIBALEKA', 'nama' => 'DIBALEKA'],
                ['kode' => 'PERUSAHAAN', 'nama' => 'Perusahaan / BUMN / BUMD'],
                ['kode' => 'SEKOLAH', 'nama' => 'Sekolah / Madrasah'],
                ['kode' => 'YAYASAN', 'nama' => 'Yayasan / Lembaga'],
                ['kode' => 'INSTANSI', 'nama' => 'Instansi Pemerintah / OPD'],
            ] as $item) {
                JenisUpz::updateOrCreate(
                    ['kode' => $item['kode']],
                    ['nama' => $item['nama'], 'aktif' => true],
                );
            }
        });
    }
}
