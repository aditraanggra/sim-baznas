<?php

use App\Services\LaporanPendistribusianService;
use App\Services\LaporanPenerimaanService;
use Database\Seeders\BidangSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\VolumeLimaTahunSeeder;
use Database\Seeders\WilayahSeeder;

beforeEach(function () {
    $this->seed([
        PeranSeeder::class,
        BidangSeeder::class,
        WilayahSeeder::class,
        MasterDanaSeeder::class,
        MasterOperasionalSeeder::class,
        VolumeLimaTahunSeeder::class,
    ]);
});

it('X1-04 NFR: query laporan bulanan di atas data 5 tahun selesai di bawah 5000 ms', function () {
    $awalMasuk = microtime(true);
    $laporanMasuk = app(LaporanPenerimaanService::class)->ringkas([
        'jenis' => 'bulanan',
        'tahun' => 2024,
        'bulan' => 6,
    ]);
    $durasiMasukMs = (microtime(true) - $awalMasuk) * 1000;

    $awalKeluar = microtime(true);
    $laporanKeluar = app(LaporanPendistribusianService::class)->ringkas([
        'jenis' => 'bulanan',
        'tahun' => 2024,
        'bulan' => 6,
    ]);
    $durasiKeluarMs = (microtime(true) - $awalKeluar) * 1000;

    expect($durasiMasukMs)->toBeLessThan(5000.0)
        ->and($durasiKeluarMs)->toBeLessThan(5000.0)
        ->and($laporanMasuk['jumlah_transaksi'])->toBeGreaterThan(0)
        ->and($laporanKeluar['jumlah_transaksi'])->toBeGreaterThan(0);
});
