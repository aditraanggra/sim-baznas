<?php

use App\Actions\Pengumpulan\AjukanPembatalanPenerimaan;
use App\Actions\Pengumpulan\BatalkanBeritaAcaraPenerimaan;
use App\Actions\Pengumpulan\BuatBeritaAcaraPenerimaan;
use App\Actions\Pengumpulan\FinalkanBeritaAcaraPenerimaan;
use App\Actions\Pengumpulan\FinalkanPenerimaan;
use App\Actions\Pengumpulan\SetujuiPembatalanPenerimaan;
use App\Actions\Pengumpulan\SimpanMuzakki;
use App\Actions\Pengumpulan\UbahBeritaAcaraPenerimaan;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\MetodeBayar;
use App\Models\SumberDana;
use App\Models\User;
use App\Services\LaporanPenerimaanService;
use Database\Seeders\BidangSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([
        PeranSeeder::class,
        BidangSeeder::class,
        WilayahSeeder::class,
        MasterDanaSeeder::class,
        MasterOperasionalSeeder::class,
    ]);
});

it('PGM-T07 & PGM-T08 Q-06: BA harian final mengunci tanggal dan pembatalan maker-checker menerbitkan BA revisi bernomor baru', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $kabag = User::factory()->create()->assignRole(['kabag_pengumpulan', 'amil']);

    $this->actingAs($staf);

    $muzakki = app(SimpanMuzakki::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'H. Yusuf',
    ]);
    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zf = SumberDana::where('kode', 'ZF')->firstOrFail();

    $trx1 = app(FinalkanPenerimaan::class)->jalankan([
        'tanggal' => '2026-11-20',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zf->id, 'jumlah' => 2_000_000]],
    ]);

    $trx2 = app(FinalkanPenerimaan::class)->jalankan([
        'tanggal' => '2026-11-20',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zf->id, 'jumlah' => 1_500_000]],
    ]);

    $baDraft = app(BuatBeritaAcaraPenerimaan::class)->jalankan(['tanggal' => '2026-11-20']);
    expect($baDraft->status)->toBe('draft')
        ->and($baDraft->total_rupiah)->toBe(3_500_000)
        ->and($baDraft->jumlah_transaksi)->toBe(2);

    $baFinal = app(FinalkanBeritaAcaraPenerimaan::class)->jalankan(['id' => $baDraft->id]);
    expect($baFinal->status)->toBe('final');

    // Q-06: transaksi susulan pada tanggal BA final ditolak.
    expect(fn () => app(FinalkanPenerimaan::class)->jalankan([
        'tanggal' => '2026-11-20',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zf->id, 'jumlah' => 100_000]],
    ]))->toThrow(ValidationException::class);

    // Ajukan pembatalan untuk trx1 oleh staf.
    $pembatalan = app(AjukanPembatalanPenerimaan::class)->jalankan([
        'penerimaan_id' => $trx1->id,
        'alasan' => 'Salah nominal dan metode transfer yang diinput petugas.',
    ]);

    // Staf (pembuat & peminta) tidak boleh menyetujui sendiri (maker-checker).
    expect(fn () => app(SetujuiPembatalanPenerimaan::class)->jalankan([
        'pembatalan_id' => $pembatalan->id,
        'keputusan' => 'disetujui',
    ]))->toThrow(AuthorizationException::class);

    // Kabag Pengumpulan menyetujui -> trx1 batal, BA lama digantikan, terbit BA Revisi ke-1 bernomor baru.
    $this->actingAs($kabag);
    $hasilBatal = app(SetujuiPembatalanPenerimaan::class)->jalankan([
        'pembatalan_id' => $pembatalan->id,
        'keputusan' => 'disetujui',
        'catatan_penyetuju' => 'Disetujui untuk koreksi harian.',
    ]);

    expect($hasilBatal->status)->toBe('disetujui')
        ->and($trx1->fresh()->status)->toBe('batal')
        ->and($baFinal->fresh()->status)->toBe('digantikan');

    $baRevisi = BeritaAcaraPenerimaan::where('status', 'final')->whereDate('tanggal', '2026-11-20')->firstOrFail();
    expect($baRevisi->revisi_ke)->toBe(1)
        ->and($baRevisi->nomor)->not->toBe($baFinal->nomor)
        ->and($baRevisi->total_rupiah)->toBe(1_500_000)
        ->and($baRevisi->jumlah_transaksi)->toBe(1)
        ->and($trx2->fresh()->berita_acara_id)->toBe($baRevisi->id);
});

it('PGM-T09 & PGM-T10: laporan penerimaan dan ekspor PDF/Excel menghitung transaksi final saja', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $this->actingAs($staf);

    $muzakki = app(SimpanMuzakki::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Siti Aminah',
    ]);
    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zm = SumberDana::where('kode', 'ZM')->firstOrFail();

    app(FinalkanPenerimaan::class)->jalankan([
        'tanggal' => '2026-11-15',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 4_000_000]],
    ]);

    $ringkasan = app(LaporanPenerimaanService::class)->ringkas([
        'jenis' => 'bulanan',
        'tahun' => 2026,
        'bulan' => 11,
    ]);

    expect($ringkasan['total_nominal'])->toBe(4_000_000)
        ->and($ringkasan['jumlah_transaksi'])->toBe(1);

    $this->get('/pengumpulan')->assertOk();
    $this->get('/pengumpulan/laporan?jenis=bulanan&tahun=2026&bulan=11')->assertOk();
    $this->get('/pengumpulan/laporan/pdf?jenis=bulanan&tahun=2026&bulan=11')->assertOk();
    $this->get('/pengumpulan/laporan/excel?jenis=bulanan&tahun=2026&bulan=11')->assertOk();
});

it('PGM-T08: draft BA dapat diubah dan dibatalkan sehingga tanggal dapat dibuatkan BA baru', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $this->actingAs($staf);

    $muzakki = app(SimpanMuzakki::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'H. Dadang']);
    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zf = SumberDana::where('kode', 'ZF')->firstOrFail();

    $trx = app(FinalkanPenerimaan::class)->jalankan([
        'tanggal' => '2026-11-18',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zf->id, 'jumlah' => 900_000]],
    ]);

    $ba = app(BuatBeritaAcaraPenerimaan::class)->jalankan(['tanggal' => '2026-11-18']);

    $diubah = app(UbahBeritaAcaraPenerimaan::class)->jalankan(['id' => $ba->id, 'catatan' => 'Catatan koreksi kasir']);
    expect($diubah->catatan)->toBe('Catatan koreksi kasir')->and($diubah->total_rupiah)->toBe(900_000);

    expect(fn () => app(BatalkanBeritaAcaraPenerimaan::class)->jalankan(['id' => $ba->id, 'alasan' => 'singkat']))
        ->toThrow(ValidationException::class);

    $batal = app(BatalkanBeritaAcaraPenerimaan::class)->jalankan(['id' => $ba->id, 'alasan' => 'Salah memilih tanggal berita acara.']);
    expect($batal->status)->toBe('batal')
        ->and($batal->alasan_batal)->toContain('Salah memilih')
        ->and($trx->fresh()->berita_acara_id)->toBeNull();

    $baru = app(BuatBeritaAcaraPenerimaan::class)->jalankan(['tanggal' => '2026-11-18']);
    expect($baru->status)->toBe('draft')->and($baru->id)->not->toBe($ba->id);
});

it('PGM-T08 Q-06: pembatalan BA final hanya oleh Admin atau Kabag Pengumpulan dan membuka kunci tanggal', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $kabag = User::factory()->create()->assignRole(['kabag_pengumpulan', 'amil']);
    $this->actingAs($staf);

    $muzakki = app(SimpanMuzakki::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Hj. Entin']);
    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zf = SumberDana::where('kode', 'ZF')->firstOrFail();

    $payload = fn (int $jumlah) => [
        'tanggal' => '2026-11-19',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zf->id, 'jumlah' => $jumlah]],
    ];

    app(FinalkanPenerimaan::class)->jalankan($payload(700_000));
    $ba = app(BuatBeritaAcaraPenerimaan::class)->jalankan(['tanggal' => '2026-11-19']);
    app(FinalkanBeritaAcaraPenerimaan::class)->jalankan(['id' => $ba->id]);

    expect(fn () => app(UbahBeritaAcaraPenerimaan::class)->jalankan(['id' => $ba->id, 'catatan' => 'x']))
        ->toThrow(ValidationException::class);

    expect(fn () => app(BatalkanBeritaAcaraPenerimaan::class)->jalankan(['id' => $ba->id, 'alasan' => 'Koreksi nominal transaksi harian.']))
        ->toThrow(AuthorizationException::class);

    expect(fn () => app(FinalkanPenerimaan::class)->jalankan($payload(100_000)))->toThrow(ValidationException::class);

    $this->actingAs($kabag);
    $batal = app(BatalkanBeritaAcaraPenerimaan::class)->jalankan(['id' => $ba->id, 'alasan' => 'Koreksi nominal transaksi harian.']);
    expect($batal->status)->toBe('batal')->and($batal->dibatalkan_oleh)->toBe($kabag->id);

    $this->actingAs($staf);
    $susulan = app(FinalkanPenerimaan::class)->jalankan($payload(100_000));
    expect($susulan->status)->toBe('final');

    $baBaru = app(BuatBeritaAcaraPenerimaan::class)->jalankan(['tanggal' => '2026-11-19']);
    expect($baBaru->revisi_ke)->toBe(1);
});
