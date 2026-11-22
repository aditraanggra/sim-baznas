<?php

namespace Database\Seeders;

use App\Models\Desa;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Provinsi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('docs/Daerah Kabupaten Cianjur.csv');
        if (! is_file($path)) {
            throw new RuntimeException("Berkas wilayah tidak ditemukan: {$path}");
        }

        $baris = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($baris === false) {
            throw new RuntimeException("Gagal membaca berkas wilayah: {$path}");
        }

        DB::transaction(function () use ($baris): void {
            $provinsi = Provinsi::updateOrCreate(
                ['kode' => '32'],
                ['nama' => 'JAWA BARAT', 'aktif' => true],
            );

            $kabupaten = Kabupaten::updateOrCreate(
                ['kode' => '32.03'],
                [
                    'provinsi_id' => $provinsi->id,
                    'nama' => 'KABUPATEN CIANJUR',
                    'aktif' => true,
                ],
            );

            $petaKecamatan = [];
            $urutKecamatan = 0;
            $urutDesa = [];

            foreach ($baris as $indeks => $isi) {
                if ($indeks === 0) {
                    continue;
                }

                $kolom = explode(';', trim($isi));
                if (count($kolom) < 2) {
                    continue;
                }

                $namaDesa = trim($kolom[0]);
                $namaKecamatan = trim($kolom[1]);

                if ($namaDesa === '' || $namaKecamatan === '') {
                    continue;
                }

                if (! isset($petaKecamatan[$namaKecamatan])) {
                    $urutKecamatan++;
                    $kodeKecamatan = sprintf('32.03.%02d', $urutKecamatan);

                    $kecamatan = Kecamatan::updateOrCreate(
                        ['kode' => $kodeKecamatan],
                        [
                            'kabupaten_id' => $kabupaten->id,
                            'nama' => $namaKecamatan,
                            'aktif' => true,
                        ],
                    );

                    $petaKecamatan[$namaKecamatan] = [
                        'id' => $kecamatan->id,
                        'kode' => $kodeKecamatan,
                    ];
                    $urutDesa[$namaKecamatan] = 0;
                }

                $urutDesa[$namaKecamatan]++;
                $kodeDesa = sprintf('%s.%02d', $petaKecamatan[$namaKecamatan]['kode'], $urutDesa[$namaKecamatan]);

                Desa::updateOrCreate(
                    ['kode' => $kodeDesa],
                    [
                        'kecamatan_id' => $petaKecamatan[$namaKecamatan]['id'],
                        'nama' => $namaDesa,
                        'aktif' => true,
                    ],
                );
            }
        });
    }
}
