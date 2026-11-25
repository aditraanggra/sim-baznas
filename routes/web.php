<?php

use App\Http\Controllers\BerkasPrivatController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PencarianGlobalController;
use App\Http\Controllers\Umum\DasborUmumController;
use App\Models\AuditLog;
use App\Models\ImportJob;
use App\Models\PosAlias;
use App\Models\PosAnggaran;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    $renderLauncher = function () {
        return Inertia::render('dashboard', [
            'ringkasan' => [
                'total_pos' => PosAnggaran::where('aktif', true)->count(),
                'total_alias' => PosAlias::count(),
                'impor_berjalan' => ImportJob::whereIn('status', ['menunggu', 'proses'])->count(),
                'audit_terakhir' => AuditLog::latest('id')->value('created_at')?->toIso8601String(),
            ],
        ]);
    };

    Route::get('dashboard', $renderLauncher)->name('dashboard');
    Route::get('launcher', $renderLauncher)->name('launcher');

    Route::get('pencarian-global', [PencarianGlobalController::class, 'index'])->name('pencarian-global');
    Route::post('notifikasi/{notifikasi}/baca', [NotifikasiController::class, 'tandaiDibaca'])->name('notifikasi.baca');
    Route::post('notifikasi/baca-semua', [NotifikasiController::class, 'tandaiSemuaDibaca'])->name('notifikasi.baca-semua');

    Route::get('umum', [DasborUmumController::class, 'index'])
        ->middleware('bidang:umum,view')
        ->name('umum.index');

    foreach ([
        'kinerja' => ['Kinerja', 'Modul RENSTRA, RENJA & Laporan Kinerja — dijadwalkan pada Gelombang 2 Fase 1B (PRC-T05 s.d. PRC-T07).'],
        'keuangan' => ['Keuangan', 'Modul Keuangan & PSAK 109 — dijadwalkan pada Fase 2 (KEU-T01 s.d. KEU-T06).'],
    ] as $slug => [$judul, $keterangan]) {
        Route::get("pkp/{$slug}", fn () => Inertia::render('pkp/placeholder', ['judul' => $judul, 'keterangan' => $keterangan]))->name("pkp.{$slug}");
    }
});

Route::get('berkas-privat/{uuid}', [BerkasPrivatController::class, 'unduh'])
    ->middleware('signed')
    ->name('berkas.privat.unduh');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/pengaturan.php';
require __DIR__.'/rencana.php';
require __DIR__.'/perencanaan.php';
require __DIR__.'/pengumpulan.php';
require __DIR__.'/distribusi.php';
require __DIR__.'/umum.php';
require __DIR__.'/publik.php';
