<?php

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/two-factor', [TwoFactorController::class, 'tampilSetup'])->name('two-factor.setup');
    Route::post('settings/two-factor', [TwoFactorController::class, 'aktifkan'])->name('two-factor.aktifkan');
    Route::delete('settings/two-factor', [TwoFactorController::class, 'nonaktifkan'])->name('two-factor.nonaktifkan');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
