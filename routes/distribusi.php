<?php

use App\Http\Controllers\Distribusi\BeritaAcaraPenyaluranController;
use App\Http\Controllers\Distribusi\DasborPendistribusianController;
use App\Http\Controllers\Distribusi\LaporanPendistribusianController;
use App\Http\Controllers\Distribusi\MustahikController;
use App\Http\Controllers\Distribusi\PenyaluranController;
use App\Http\Controllers\Distribusi\PermohonanController;
use Illuminate\Support\Facades\Route;

// Keuangan dapat menandai kesiapan dana tanpa membutuhkan izin tulis bidang distribusi.
Route::middleware(['auth', 'peran:keuangan,admin'])
    ->post('distribusi/permohonan/{permohonan}/siap-keuangan', [PermohonanController::class, 'siapkanKeuangan'])
    ->name('distribusi.permohonan.siap-keuangan');

Route::middleware(['auth', 'bidang:distribusi,view'])
    ->prefix('distribusi')
    ->name('distribusi.')
    ->group(function () {
        Route::get('/', [DasborPendistribusianController::class, 'index'])->name('index');
        Route::get('disposisi-saya', [DasborPendistribusianController::class, 'disposisiSaya'])->name('disposisi-saya');

        Route::get('mustahik', [MustahikController::class, 'index'])->name('mustahik.index');
        Route::get('mustahik/cek-duplikat', [MustahikController::class, 'cekDuplikat'])->name('mustahik.cek-duplikat');
        Route::post('mustahik', [MustahikController::class, 'store'])
            ->middleware('bidang:distribusi,create')
            ->name('mustahik.store');
        Route::get('mustahik/{mustahik}', [MustahikController::class, 'show'])->name('mustahik.show');
        Route::post('mustahik/{mustahik}/verifikasi', [MustahikController::class, 'verifikasi'])
            ->middleware('bidang:distribusi,create')
            ->name('mustahik.verifikasi');
        Route::post('mustahik/{mustahik}/dokumen', [MustahikController::class, 'unggahDokumen'])
            ->middleware('bidang:distribusi,create')
            ->name('mustahik.dokumen');

        Route::get('permohonan', [PermohonanController::class, 'index'])->name('permohonan.index');
        Route::post('permohonan', [PermohonanController::class, 'store'])
            ->middleware('bidang:distribusi,create')
            ->name('permohonan.store');
        Route::get('permohonan/{permohonan}', [PermohonanController::class, 'show'])->name('permohonan.show');
        Route::post('permohonan/{permohonan}/proses-langkah', [PermohonanController::class, 'prosesLangkah'])
            ->name('permohonan.proses-langkah');
        Route::post('permohonan/{permohonan}/tolak', [PermohonanController::class, 'tolak'])
            ->name('permohonan.tolak');
        Route::get('permohonan/{permohonan}/lembar-disposisi', [PermohonanController::class, 'lembarDisposisiPdf'])
            ->middleware('bidang:distribusi,print')
            ->name('permohonan.lembar-disposisi');

        Route::get('penyaluran', [PenyaluranController::class, 'index'])->name('penyaluran.index');
        Route::get('penyaluran/buat', [PenyaluranController::class, 'create'])
            ->middleware('bidang:distribusi,create')
            ->name('penyaluran.create');
        Route::post('penyaluran/cek-saldo', [PenyaluranController::class, 'cekSaldo'])
            ->name('penyaluran.cek-saldo');
        Route::post('penyaluran/draft', [PenyaluranController::class, 'storeDraft'])
            ->middleware('bidang:distribusi,create')
            ->name('penyaluran.draft');
        Route::post('penyaluran/{penyaluran}/setujui', [PenyaluranController::class, 'setujui'])
            ->middleware('bidang:distribusi,approve')
            ->name('penyaluran.setujui');
        Route::post('penyaluran/{penyaluran}/cairkan', [PenyaluranController::class, 'cairkan'])
            ->name('penyaluran.cairkan');
        Route::post('penyaluran/{penyaluran}/batalkan', [PenyaluranController::class, 'batalkan'])
            ->middleware('bidang:distribusi,approve')
            ->name('penyaluran.batalkan');

        Route::get('berita-acara', [BeritaAcaraPenyaluranController::class, 'index'])->name('berita-acara.index');
        Route::post('berita-acara', [BeritaAcaraPenyaluranController::class, 'store'])
            ->middleware('bidang:distribusi,create')
            ->name('berita-acara.store');
        Route::put('berita-acara/{beritaAcara}', [BeritaAcaraPenyaluranController::class, 'update'])
            ->middleware('bidang:distribusi,create')
            ->name('berita-acara.update');
        Route::post('berita-acara/{beritaAcara}/batalkan', [BeritaAcaraPenyaluranController::class, 'batalkan'])
            ->middleware('bidang:distribusi,create')
            ->name('berita-acara.batalkan');
        Route::post('berita-acara/{beritaAcara}/finalkan', [BeritaAcaraPenyaluranController::class, 'finalkan'])
            ->middleware('bidang:distribusi,create')
            ->name('berita-acara.finalkan');
        Route::get('berita-acara/{beritaAcara}/pdf', [BeritaAcaraPenyaluranController::class, 'pdf'])
            ->middleware('bidang:distribusi,print')
            ->name('berita-acara.pdf');

        Route::get('laporan', [LaporanPendistribusianController::class, 'index'])->name('laporan.index');
        Route::get('laporan/pdf', [LaporanPendistribusianController::class, 'pdf'])
            ->middleware('bidang:distribusi,export')
            ->name('laporan.pdf');
        Route::get('laporan/excel', [LaporanPendistribusianController::class, 'excel'])
            ->middleware('bidang:distribusi,export')
            ->name('laporan.excel');
    });
