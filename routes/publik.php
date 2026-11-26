<?php

use App\Http\Controllers\Publik\BerandaPublikController;
use App\Http\Controllers\Publik\PortalPimpinanController;
use App\Http\Controllers\Publik\TrackingPermohonanController;
use App\Http\Controllers\Publik\VerifikasiKwitansiController;
use App\Http\Controllers\Publik\VerifikasiSkUpzController;
use App\Http\Controllers\Publik\VerifikasiSuratController;
use Illuminate\Support\Facades\Route;

Route::prefix('publik')->name('publik.')->group(function () {
    Route::get('/', [BerandaPublikController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('index');

    Route::get('verifikasi-kwitansi/{token}', [VerifikasiKwitansiController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('verifikasi-kwitansi');

    Route::get('verifikasi-surat/{uuid}', [VerifikasiSuratController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('verifikasi-surat');

    Route::get('verifikasi-sk-upz', [VerifikasiSkUpzController::class, 'index'])
        ->middleware('throttle:20,1')
        ->name('verifikasi-sk-upz');

    Route::get('lacak-permohonan', [TrackingPermohonanController::class, 'index'])
        ->middleware('throttle:10,1')
        ->name('lacak-permohonan');
});

Route::prefix('akses')->name('akses.')->middleware('throttle:60,1')->group(function () {
    Route::get('{token}', [PortalPimpinanController::class, 'index'])
        ->name('portal');

    Route::get('{token}/kinerja/pdf', [PortalPimpinanController::class, 'unduhPdfKinerja'])
        ->name('kinerja.pdf');

    Route::get('{token}/arsip/{uuid}', [PortalPimpinanController::class, 'berkasArsip'])
        ->name('arsip.berkas');
});
