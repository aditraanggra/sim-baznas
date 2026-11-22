<?php

use App\Http\Controllers\Pengumpulan\BeritaAcaraPenerimaanController;
use App\Http\Controllers\Pengumpulan\DasborPengumpulanController;
use App\Http\Controllers\Pengumpulan\LaporanPenerimaanController;
use App\Http\Controllers\Pengumpulan\MuzakkiController;
use App\Http\Controllers\Pengumpulan\PenerimaanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'bidang:pengumpulan,view'])
    ->prefix('pengumpulan')
    ->name('pengumpulan.')
    ->group(function () {
        Route::get('/', [DasborPengumpulanController::class, 'index'])->name('index');

        Route::get('muzakki', [MuzakkiController::class, 'index'])->name('muzakki.index');
        Route::get('muzakki/cek-duplikat', [MuzakkiController::class, 'cekDuplikat'])->name('muzakki.cek-duplikat');
        Route::post('muzakki', [MuzakkiController::class, 'store'])
            ->middleware('bidang:pengumpulan,create')
            ->name('muzakki.store');
        Route::get('muzakki/{muzakki}', [MuzakkiController::class, 'show'])->name('muzakki.show');

        Route::get('penerimaan', [PenerimaanController::class, 'index'])->name('penerimaan.index');
        Route::get('penerimaan/buat', [PenerimaanController::class, 'create'])
            ->middleware('bidang:pengumpulan,create')
            ->name('penerimaan.create');
        Route::post('penerimaan/draft', [PenerimaanController::class, 'storeDraft'])
            ->middleware('bidang:pengumpulan,create')
            ->name('penerimaan.draft');
        Route::post('penerimaan/final', [PenerimaanController::class, 'finalkan'])
            ->middleware('bidang:pengumpulan,create')
            ->name('penerimaan.final');
        Route::get('penerimaan/{penerimaan}', [PenerimaanController::class, 'show'])->name('penerimaan.show');
        Route::get('penerimaan/{penerimaan}/kwitansi', [PenerimaanController::class, 'cetakKwitansi'])
            ->middleware('bidang:pengumpulan,print')
            ->name('penerimaan.kwitansi');
        Route::post('penerimaan/{penerimaan}/batal-ajukan', [PenerimaanController::class, 'ajukanPembatalan'])
            ->middleware('bidang:pengumpulan,create')
            ->name('penerimaan.batal-ajukan');
        Route::post('penerimaan/pembatalan/{pembatalan}/setujui', [PenerimaanController::class, 'setujuiPembatalan'])
            ->middleware('bidang:pengumpulan,approve')
            ->name('penerimaan.batal-setujui');

        Route::get('berita-acara', [BeritaAcaraPenerimaanController::class, 'index'])->name('berita-acara.index');
        Route::post('berita-acara', [BeritaAcaraPenerimaanController::class, 'store'])
            ->middleware('bidang:pengumpulan,create')
            ->name('berita-acara.store');
        Route::put('berita-acara/{beritaAcara}', [BeritaAcaraPenerimaanController::class, 'update'])
            ->middleware('bidang:pengumpulan,create')
            ->name('berita-acara.update');
        Route::post('berita-acara/{beritaAcara}/batalkan', [BeritaAcaraPenerimaanController::class, 'batalkan'])
            ->middleware('bidang:pengumpulan,create')
            ->name('berita-acara.batalkan');
        Route::post('berita-acara/{beritaAcara}/finalkan', [BeritaAcaraPenerimaanController::class, 'finalkan'])
            ->middleware('bidang:pengumpulan,create')
            ->name('berita-acara.finalkan');
        Route::get('berita-acara/{beritaAcara}/pdf', [BeritaAcaraPenerimaanController::class, 'pdf'])
            ->middleware('bidang:pengumpulan,print')
            ->name('berita-acara.pdf');

        Route::get('laporan', [LaporanPenerimaanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/pdf', [LaporanPenerimaanController::class, 'pdf'])
            ->middleware('bidang:pengumpulan,export')
            ->name('laporan.pdf');
        Route::get('laporan/excel', [LaporanPenerimaanController::class, 'excel'])
            ->middleware('bidang:pengumpulan,export')
            ->name('laporan.excel');
    });
