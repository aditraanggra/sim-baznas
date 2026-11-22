<?php

use App\Http\Controllers\Umum\UpzController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'bidang:umum,view'])
    ->prefix('umum')
    ->name('umum.')
    ->group(function () {
        Route::get('upz', [UpzController::class, 'index'])->name('upz.index');
        Route::post('upz', [UpzController::class, 'store'])
            ->middleware('bidang:umum,create')
            ->name('upz.store');
        Route::post('upz/toggle-aktif', [UpzController::class, 'toggleAktif'])
            ->middleware('bidang:umum,update')
            ->name('upz.toggle-aktif');
        Route::get('upz/laporan-setoran', [UpzController::class, 'laporanSetoran'])->name('upz.laporan-setoran');
        Route::get('upz/{upz}', [UpzController::class, 'show'])->name('upz.show');
        Route::post('upz/{upz}/sk', [UpzController::class, 'storeSk'])
            ->middleware('bidang:umum,create')
            ->name('upz.sk.store');
    });
