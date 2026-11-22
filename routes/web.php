<?php

use App\Http\Controllers\BerkasPrivatController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PencarianGlobalController;
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

    foreach ([
        'umum' => 'SDM, Administrasi dan Umum',
    ] as $kodeBidang => $namaBidang) {
        Route::get($kodeBidang, fn () => Inertia::render('modul/bidang-dashboard', [
            'bidang' => config("sisfozis.bidang.{$kodeBidang}"),
            'ringkasan' => null,
        ]))->middleware("bidang:{$kodeBidang},view")->name("{$kodeBidang}.index");
    }

    Route::get('pkp', fn () => Inertia::render('modul/bidang-dashboard', [
        'bidang' => config('sisfozis.bidang.pkp'),
        'ringkasan' => [
            'total_pos' => PosAnggaran::where('aktif', true)->count(),
            'total_alias' => PosAlias::count(),
            'impor_berjalan' => ImportJob::whereIn('status', ['menunggu', 'proses'])->count(),
            'audit_terakhir' => AuditLog::latest('id')->value('created_at')?->toIso8601String(),
        ],
    ]))->middleware('bidang:pkp,view')->name('pkp.index');

    foreach ([
        'realisasi' => ['Realisasi', 'Modul PK2. Input bulanan, penguncian periode, dan capaian dikerjakan di modul ini.'],
        'kinerja' => ['Kinerja', 'Modul PK3. Perbandingan antar tahun dan capaian terhadap RKAT dikerjakan di modul ini.'],
        'keuangan' => ['Keuangan', 'Modul PK4. Laporan keuangan sesuai format menunggu jawaban Q1.'],
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
require __DIR__.'/pengumpulan.php';
require __DIR__.'/distribusi.php';
require __DIR__.'/umum.php';
require __DIR__.'/publik.php';
