<?php

use App\Http\Controllers\Pkp\BandingkanController;
use App\Http\Controllers\Pkp\EksporRencanaController;
use App\Http\Controllers\Pkp\ImporRencanaController;
use App\Http\Controllers\Pkp\RencanaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('pkp/rencana')->group(function () {
    Route::get('/', [RencanaController::class, 'index'])->name('pkp.rencana');
    Route::get('bandingkan', [BandingkanController::class, 'index'])->name('pkp.rencana.bandingkan');
    Route::get('versi/{versi}', [RencanaController::class, 'show'])->name('pkp.rencana.show');
    Route::get('versi/{versi}/ekspor/excel', [EksporRencanaController::class, 'excel'])->name('pkp.rencana.ekspor.excel');
    Route::get('versi/{versi}/ekspor/pdf', [EksporRencanaController::class, 'pdf'])->name('pkp.rencana.ekspor.pdf');
    Route::get('dokumen/{dokumen}', [RencanaController::class, 'unduhDokumen'])->name('pkp.rencana.dokumen.unduh');

    // Tulis: perencanaan dan admin (Action menegakkan ulang peran dan status).
    Route::middleware('peran:perencanaan,admin')->group(function () {
        Route::post('/', [RencanaController::class, 'buat'])->name('pkp.rencana.buat');
        Route::post('versi/{versi}/nilai', [RencanaController::class, 'simpanNilai'])->name('pkp.rencana.nilai');
        Route::post('versi/{versi}/catatan', [RencanaController::class, 'simpanCatatan'])->name('pkp.rencana.catatan');
        Route::post('versi/{versi}/ajukan', [RencanaController::class, 'ajukan'])->name('pkp.rencana.ajukan');
        Route::post('versi/{versi}/kembalikan', [RencanaController::class, 'kembalikan'])->name('pkp.rencana.kembalikan');
        Route::post('versi/{versi}/sahkan', [RencanaController::class, 'sahkan'])->name('pkp.rencana.sahkan');
        Route::post('versi/{versi}/arsip', [RencanaController::class, 'arsipkan'])->name('pkp.rencana.arsip');
        Route::post('versi/{versi}/dokumen', [RencanaController::class, 'unggahDokumen'])->name('pkp.rencana.dokumen');

        Route::get('impor', [ImporRencanaController::class, 'form'])->name('pkp.rencana.impor');
        Route::post('impor', [ImporRencanaController::class, 'unggah'])->name('pkp.rencana.impor.unggah');
        Route::post('impor/alias', [ImporRencanaController::class, 'alias'])->name('pkp.rencana.impor.alias');
        Route::get('impor/{job}', [ImporRencanaController::class, 'pratinjau'])->name('pkp.rencana.impor.pratinjau');
        Route::post('impor/{job}/terapkan', [ImporRencanaController::class, 'terapkan'])->name('pkp.rencana.impor.terapkan');
    });
});
