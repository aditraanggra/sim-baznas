<?php

use App\Models\AuditLog;
use App\Models\ImportJob;
use App\Models\PosAlias;
use App\Models\PosAnggaran;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard', [
            'ringkasan' => [
                'total_pos' => PosAnggaran::where('aktif', true)->count(),
                'total_alias' => PosAlias::count(),
                'impor_berjalan' => ImportJob::whereIn('status', ['menunggu', 'proses'])->count(),
                'audit_terakhir' => AuditLog::latest('id')->value('created_at')?->toIso8601String(),
            ],
        ]);
    })->name('dashboard');

    foreach ([
        'realisasi' => ['Realisasi', 'Modul PK2. Input bulanan, penguncian periode, dan capaian dikerjakan di modul ini.'],
        'kinerja' => ['Kinerja', 'Modul PK3. Perbandingan antar tahun dan capaian terhadap RKAT dikerjakan di modul ini.'],
        'keuangan' => ['Keuangan', 'Modul PK4. Laporan keuangan sesuai format menunggu jawaban Q1.'],
    ] as $slug => [$judul, $keterangan]) {
        Route::get("pkp/{$slug}", fn () => Inertia::render('pkp/placeholder', ['judul' => $judul, 'keterangan' => $keterangan]))->name("pkp.{$slug}");
    }
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/pengaturan.php';
require __DIR__.'/rencana.php';
