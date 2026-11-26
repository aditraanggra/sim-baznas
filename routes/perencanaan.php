<?php

use App\Http\Controllers\Pkp\ArsipDokumenController;
use App\Http\Controllers\Pkp\DasborPkpController;
use App\Http\Controllers\Pkp\PemetaanKegiatanController;
use App\Http\Controllers\Pkp\RealisasiController;
use App\Http\Controllers\Pkp\TahunAnggaranController;
use App\Http\Controllers\Umum\UpzController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'bidang:pkp,view'])->prefix('pkp')->name('pkp.')->group(function () {
    Route::get('/', [DasborPkpController::class, 'index'])->name('index');
    Route::get('kinerja-pdf', [DasborPkpController::class, 'unduhPdf'])
        ->middleware('bidang:pkp,print')
        ->name('kinerja.pdf');
    Route::get('laporan-setoran-upz', [UpzController::class, 'laporanSetoran'])->name('laporan-setoran-upz');

    // Arsip Dokumen PKP (ARS-T01 / ARS-T03)
    Route::get('arsip', [ArsipDokumenController::class, 'index'])->name('arsip.index');
    Route::post('arsip', [ArsipDokumenController::class, 'simpan'])
        ->middleware('bidang:pkp,create')
        ->name('arsip.simpan');
    Route::post('arsip/{dokumen}/sahkan', [ArsipDokumenController::class, 'sahkan'])
        ->middleware('bidang:pkp,approve')
        ->name('arsip.sahkan');
    Route::post('arsip/{dokumen}/cabut', [ArsipDokumenController::class, 'cabut'])
        ->middleware('bidang:pkp,approve')
        ->name('arsip.cabut');
    Route::get('arsip/{dokumen}/berkas', [ArsipDokumenController::class, 'berkas'])
        ->name('arsip.berkas');

    // Tahun Anggaran
    Route::post('tahun-anggaran', [TahunAnggaranController::class, 'store'])
        ->middleware('bidang:pkp,create')
        ->name('tahun-anggaran.store');
    Route::post('tahun-anggaran/status', [TahunAnggaranController::class, 'ubahStatus'])
        ->middleware('bidang:pkp,approve')
        ->name('tahun-anggaran.status');

    // Kegiatan & Pemetaan Pos (PRC-06)
    Route::get('kegiatan', [PemetaanKegiatanController::class, 'index'])->name('kegiatan.index');
    Route::post('kegiatan', [PemetaanKegiatanController::class, 'store'])
        ->middleware('bidang:pkp,update')
        ->name('kegiatan.store');

    // Realisasi (PRC-02 / PRC-T04)
    Route::get('realisasi', [RealisasiController::class, 'index'])->name('realisasi');
    Route::post('realisasi/manual', [RealisasiController::class, 'storeManual'])
        ->middleware('bidang:pkp,create')
        ->name('realisasi.manual');
    Route::post('realisasi/kunci', [RealisasiController::class, 'kunci'])
        ->middleware('bidang:pkp,approve')
        ->name('realisasi.kunci');
    Route::get('realisasi/pdf', [RealisasiController::class, 'cetakPdf'])
        ->middleware('bidang:pkp,print')
        ->name('realisasi.pdf');
    Route::get('realisasi/excel', [RealisasiController::class, 'eksporExcel'])
        ->middleware('bidang:pkp,export')
        ->name('realisasi.excel');
});
