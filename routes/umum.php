<?php

use App\Http\Controllers\RkatBidangController;
use App\Http\Controllers\Umum\ArsipSuratController;
use App\Http\Controllers\Umum\SuratKeluarController;
use App\Http\Controllers\Umum\SuratMasukController;
use App\Http\Controllers\Umum\TemplateSuratController;
use App\Http\Controllers\Umum\UpzController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'bidang:umum,view'])
    ->prefix('umum')
    ->name('umum.')
    ->group(function () {
        Route::prefix('rkat')->name('rkat.')->group(function () {
            Route::get('/', [RkatBidangController::class, 'index'])->name('index')->defaults('bidang', 'umum');
            Route::middleware('peran:kabag_sau,admin')->group(function () {
                Route::post('jenis-kegiatan', [RkatBidangController::class, 'simpanJenisKegiatan'])->name('jenis-kegiatan')->defaults('bidang', 'umum');
                Route::post('kegiatan', [RkatBidangController::class, 'simpanKegiatan'])->name('kegiatan')->defaults('bidang', 'umum');
                Route::post('pelengkap', [RkatBidangController::class, 'simpanPelengkap'])->name('pelengkap')->defaults('bidang', 'umum');
                Route::post('{versi}/ajukan', [RkatBidangController::class, 'ajukan'])->name('ajukan')->defaults('bidang', 'umum');
            });
        });

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

        Route::prefix('surat')->name('surat.')->group(function () {
            Route::get('template', [TemplateSuratController::class, 'index'])->name('template.index');
            Route::post('template/jenis', [TemplateSuratController::class, 'simpanJenis'])
                ->middleware('bidang:umum,create')
                ->name('template.jenis.simpan');
            Route::post('template/jenis/toggle-aktif', [TemplateSuratController::class, 'toggleJenis'])
                ->middleware('bidang:umum,update')
                ->name('template.jenis.toggle');
            Route::post('template/isi', [TemplateSuratController::class, 'simpanTemplate'])
                ->middleware('bidang:umum,create')
                ->name('template.isi.simpan');
            Route::post('template/isi/toggle-aktif', [TemplateSuratController::class, 'toggleTemplate'])
                ->middleware('bidang:umum,update')
                ->name('template.isi.toggle');

            Route::get('masuk', [SuratMasukController::class, 'index'])->name('masuk.index');
            Route::post('masuk', [SuratMasukController::class, 'store'])
                ->middleware('bidang:umum,create')
                ->name('masuk.store');
            Route::get('masuk/{suratMasuk}', [SuratMasukController::class, 'show'])->name('masuk.show');
            Route::post('masuk/{suratMasuk}/teruskan-ketua', [SuratMasukController::class, 'teruskanKetua'])
                ->name('masuk.teruskan-ketua');
            Route::post('masuk/{suratMasuk}/disposisi', [SuratMasukController::class, 'disposisi'])
                ->name('masuk.disposisi');
            Route::post('masuk/{suratMasuk}/disposisi/{disposisiSurat}/selesai', [SuratMasukController::class, 'selesaikanDisposisi'])
                ->name('masuk.disposisi.selesai');
            Route::post('masuk/{suratMasuk}/tutup', [SuratMasukController::class, 'tutup'])
                ->name('masuk.tutup');
            Route::get('masuk/{suratMasuk}/lembar-disposisi-pdf', [SuratMasukController::class, 'lembarDisposisiPdf'])
                ->name('masuk.lembar-disposisi-pdf');

            Route::get('keluar', [SuratKeluarController::class, 'index'])->name('keluar.index');
            Route::get('keluar/buat', [SuratKeluarController::class, 'create'])
                ->middleware('bidang:umum,create')
                ->name('keluar.create');
            Route::post('keluar', [SuratKeluarController::class, 'store'])
                ->middleware('bidang:umum,create')
                ->name('keluar.store');
            Route::get('keluar/{suratKeluar}', [SuratKeluarController::class, 'show'])->name('keluar.show');
            Route::get('keluar/{suratKeluar}/edit', [SuratKeluarController::class, 'edit'])
                ->middleware('bidang:umum,update')
                ->name('keluar.edit');
            Route::put('keluar/{suratKeluar}', [SuratKeluarController::class, 'update'])
                ->middleware('bidang:umum,update')
                ->name('keluar.update');
            Route::post('keluar/{suratKeluar}/ajukan', [SuratKeluarController::class, 'ajukan'])
                ->middleware('bidang:umum,update')
                ->name('keluar.ajukan');
            Route::post('keluar/{suratKeluar}/setujui', [SuratKeluarController::class, 'setujui'])
                ->middleware('bidang:umum,approve')
                ->name('keluar.setujui');
            Route::post('keluar/{suratKeluar}/revisi', [SuratKeluarController::class, 'revisi'])
                ->middleware('bidang:umum,approve')
                ->name('keluar.revisi');
            Route::post('keluar/{suratKeluar}/batalkan', [SuratKeluarController::class, 'batalkan'])
                ->name('keluar.batalkan');
            Route::get('keluar/{suratKeluar}/pdf', [SuratKeluarController::class, 'pdf'])
                ->name('keluar.pdf');

            Route::get('arsip', [ArsipSuratController::class, 'index'])->name('arsip.index');
            Route::get('arsip/excel', [ArsipSuratController::class, 'exportExcel'])->name('arsip.excel');
            Route::get('arsip/pdf', [ArsipSuratController::class, 'exportPdf'])->name('arsip.pdf');
        });
    });
