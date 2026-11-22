<?php

use App\Http\Controllers\Publik\TrackingPermohonanController;
use App\Http\Controllers\Publik\VerifikasiKwitansiController;
use App\Http\Controllers\Publik\VerifikasiSkUpzController;
use Illuminate\Support\Facades\Route;

Route::prefix('publik')->name('publik.')->group(function () {
    Route::get('verifikasi-kwitansi/{token}', [VerifikasiKwitansiController::class, 'show'])
        ->middleware('throttle:30,1')
        ->name('verifikasi-kwitansi');

    Route::get('verifikasi-sk-upz', [VerifikasiSkUpzController::class, 'index'])
        ->middleware('throttle:20,1')
        ->name('verifikasi-sk-upz');

    Route::get('lacak-permohonan', [TrackingPermohonanController::class, 'index'])
        ->middleware('throttle:10,1')
        ->name('lacak-permohonan');
});
