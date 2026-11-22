<?php

use App\Models\NomorSequence;
use App\Services\PenomoranService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('F0-13 K15: PenomoranService menghasilkan format berurut sesuai template dan reset per tahun', function () {
    $service = app(PenomoranService::class);
    $november2026 = Carbon::create(2026, 11, 22);
    $januari2027 = Carbon::create(2027, 1, 5);

    $kw1 = $service->berikutnya('kwitansi', $november2026);
    $kw2 = $service->berikutnya('kwitansi', $november2026);
    $kw2027 = $service->berikutnya('kwitansi', $januari2027);

    expect($kw1)->toBe('KW/BAZNAS-CJR/2026/XI/00001')
        ->and($kw2)->toBe('KW/BAZNAS-CJR/2026/XI/00002')
        ->and($kw2027)->toBe('KW/BAZNAS-CJR/2027/I/00001');

    $sk1 = $service->berikutnya('surat_keluar', $november2026, ['kode' => 'SK']);
    $aset1 = $service->berikutnya('aset', $november2026, ['kode' => 'KEND']);

    expect($sk1)->toBe('001/SK/BAZNAS-CJR/XI/2026')
        ->and($aset1)->toBe('AST/KEND/2026/0001');
});

it('F0-13 K15: format penomoran dapat diganti dari konfigurasi tanpa mengubah kode atau struktur tabel', function () {
    Config::set('penomoran.format.kwitansi', '{tahun}-{bulan}-{urut:3}/KW/{lembaga}');

    $service = app(PenomoranService::class);
    $nomor = $service->berikutnya('kwitansi', Carbon::create(2026, 5, 10));

    expect($nomor)->toBe('2026-05-001/KW/BAZNAS-CJR');
});

it('F0-13: penomoran paralel/beruntun dalam transaksi menghasilkan seluruh nomor unik tanpa celah', function () {
    $service = app(PenomoranService::class);
    $tanggal = Carbon::create(2026, 8, 17);
    $hasil = [];

    for ($i = 0; $i < 50; $i++) {
        $hasil[] = DB::transaction(fn () => $service->berikutnya('permohonan', $tanggal));
    }

    expect(count(array_unique($hasil)))->toBe(50)
        ->and($hasil[0])->toBe('PRM/BAZNAS-CJR/2026/VIII/0001')
        ->and($hasil[49])->toBe('PRM/BAZNAS-CJR/2026/VIII/0050')
        ->and(NomorSequence::where('jenis_nomor', 'permohonan')->where('tahun', 2026)->value('nilai_terakhir'))->toBe(50);
});
