<?php

use App\Http\Controllers\Pengaturan\ImporController;
use App\Http\Controllers\Pengaturan\PenggunaController;
use App\Http\Controllers\Pengaturan\PosAnggaranController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'peran:admin'])->prefix('pengaturan')->name('pengaturan.')->group(function () {
    Route::get('pengguna', [PenggunaController::class, 'index'])->name('pengguna');
    Route::post('pengguna', [PenggunaController::class, 'simpan'])->name('pengguna.simpan');

    Route::get('pos-anggaran', [PosAnggaranController::class, 'index'])->name('pos-anggaran');
    Route::post('pos-anggaran', [PosAnggaranController::class, 'simpan'])->name('pos-anggaran.simpan');
    Route::post('pos-anggaran/nonaktifkan', [PosAnggaranController::class, 'nonaktifkan'])->name('pos-anggaran.nonaktifkan');

    Route::get('impor', [ImporController::class, 'index'])->name('impor');
    Route::post('impor', [ImporController::class, 'simpan'])->name('impor.simpan');
    Route::get('impor/{job}', [ImporController::class, 'hasil'])->name('impor.hasil');

    Route::get('audit', [ImporController::class, 'audit'])->name('audit');
});
