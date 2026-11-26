<?php

use App\Http\Controllers\Pengaturan\ImporController;
use App\Http\Controllers\Pengaturan\MasterDataController;
use App\Http\Controllers\Pengaturan\PenggunaController;
use App\Http\Controllers\Pengaturan\PosAnggaranController;
use App\Http\Controllers\Pengaturan\TautanAksesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'peran:admin'])->prefix('pengaturan')->name('pengaturan.')->group(function () {
    Route::get('pengguna', [PenggunaController::class, 'index'])->name('pengguna');
    Route::post('pengguna', [PenggunaController::class, 'simpan'])->name('pengguna.simpan');
    Route::post('pengguna/toggle-status', [PenggunaController::class, 'toggleStatus'])->name('pengguna.toggle-status');
    Route::post('pengguna/reset-sandi', [PenggunaController::class, 'resetSandi'])->name('pengguna.reset-sandi');

    Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data');
    Route::post('master-data/{jenis}', [MasterDataController::class, 'simpan'])->name('master-data.simpan');
    Route::post('master-data/{jenis}/toggle-aktif', [MasterDataController::class, 'toggleAktif'])->name('master-data.toggle-aktif');

    Route::get('pos-anggaran', [PosAnggaranController::class, 'index'])->name('pos-anggaran');
    Route::post('pos-anggaran', [PosAnggaranController::class, 'simpan'])->name('pos-anggaran.simpan');
    Route::post('pos-anggaran/nonaktifkan', [PosAnggaranController::class, 'nonaktifkan'])->name('pos-anggaran.nonaktifkan');

    Route::get('impor', [ImporController::class, 'index'])->name('impor');
    Route::post('impor', [ImporController::class, 'simpan'])->name('impor.simpan');
    Route::get('impor/{job}', [ImporController::class, 'hasil'])->name('impor.hasil');

    Route::get('audit', [ImporController::class, 'audit'])->name('audit');

    Route::get('tautan-akses', [TautanAksesController::class, 'index'])->name('tautan-akses');
    Route::post('tautan-akses', [TautanAksesController::class, 'buat'])->name('tautan-akses.buat');
    Route::post('tautan-akses/{tautan}/cabut', [TautanAksesController::class, 'cabut'])->name('tautan-akses.cabut');
    Route::post('tautan-akses/{tautan}/putar', [TautanAksesController::class, 'putar'])->name('tautan-akses.putar');
});
