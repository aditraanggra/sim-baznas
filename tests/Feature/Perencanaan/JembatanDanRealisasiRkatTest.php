<?php

use App\Actions\Distribusi\CairkanPenyaluran;
use App\Actions\Distribusi\SetujuiPenyaluran;
use App\Actions\Distribusi\SimpanMustahik;
use App\Actions\Distribusi\SimpanPenyaluran;
use App\Actions\Pengumpulan\FinalkanPenerimaan;
use App\Actions\Pengumpulan\SimpanDraftPenerimaan;
use App\Actions\Pengumpulan\SimpanMuzakki;
use App\Actions\Perencanaan\KunciRealisasiManual;
use App\Actions\Perencanaan\SimpanPemetaanPos;
use App\Actions\Perencanaan\SimpanRealisasiManual;
use App\Actions\Perencanaan\SimpanTahunAnggaran;
use App\Actions\Perencanaan\UbahStatusTahunAnggaran;
use App\Models\Asnaf;
use App\Models\AuditLog;
use App\Models\BentukBantuan;
use App\Models\Bidang;
use App\Models\MetodeBayar;
use App\Models\PosAnggaran;
use App\Models\PosAnggaranPemetaan;
use App\Models\Program;
use App\Models\RealisasiManual;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use App\Models\User;
use App\Services\Perencanaan\RealisasiRkatService;
use Database\Seeders\BidangSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\PerencanaanJembatanSeeder;
use Database\Seeders\PosAnggaranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([
        PeranSeeder::class,
        BidangSeeder::class,
        WilayahSeeder::class,
        MasterDanaSeeder::class,
        MasterOperasionalSeeder::class,
        PosAnggaranSeeder::class,
        PerencanaanJembatanSeeder::class,
    ]);
});

it('PRC-T02: PerencanaanJembatanSeeder memetakan seluruh pos daun, SimpanPemetaanPos menulis audit, dan peran non-PKP ditolak', function () {
    $jumlahDaun = PosAnggaran::where('agregasi', 'input_langsung')->count();
    expect(PosAnggaranPemetaan::count())->toBe($jumlahDaun);

    $stafNonPkp = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $this->actingAs($stafNonPkp);

    $pos = PosAnggaran::where('kode', 'L2/1.2')->firstOrFail();
    $bidang = Bidang::where('kode', 'pengumpulan')->firstOrFail();

    expect(fn () => app(SimpanPemetaanPos::class)->jalankan([
        'pos_anggaran_id' => $pos->id,
        'bidang_id' => $bidang->id,
        'sumber_transaksi' => 'penerimaan',
        'nama_kegiatan' => 'Zakat Mal Perorangan Diubah',
    ]))->toThrow(AuthorizationException::class);

    $perencanaan = User::factory()->create()->assignRole(['perencanaan', 'amil']);
    $this->actingAs($perencanaan);

    $pemetaan = app(SimpanPemetaanPos::class)->jalankan([
        'pos_anggaran_id' => $pos->id,
        'bidang_id' => $bidang->id,
        'sumber_transaksi' => 'penerimaan',
        'nama_kegiatan' => 'Zakat Mal Perorangan Langsung (Diperbarui)',
        'aktif' => true,
    ]);

    expect($pemetaan->nama_kegiatan)->toBe('Zakat Mal Perorangan Langsung (Diperbarui)')
        ->and(AuditLog::where('entitas_tipe', PosAnggaranPemetaan::class)->where('entitas_id', $pemetaan->id)->exists())->toBeTrue();
});

it('PRC-T03: TahunAnggaran menjaga satu tahun berjalan, menautkan RKAT, dan tahun tutup menolak realisasi manual', function () {
    $kabagPkp = User::factory()->create()->assignRole(['kabag_pkp', 'amil']);
    $this->actingAs($kabagPkp);

    $rkat2027 = RkatVersion::create([
        'tahun' => 2027,
        'jenis' => 'awal',
        'nomor_perubahan' => 0,
        'status' => 'draft',
        'sumber' => 'manual',
        'dibuat_oleh' => $kabagPkp->id,
    ]);

    $ta2027 = app(SimpanTahunAnggaran::class)->jalankan([
        'tahun' => 2027,
        'status' => 'berjalan',
        'tanggal_mulai' => '2027-01-01',
        'tanggal_selesai' => '2027-12-31',
    ]);

    expect($ta2027->status)->toBe('berjalan')
        ->and(TahunAnggaran::where('tahun', 2026)->value('status'))->toBe('draft')
        ->and($rkat2027->fresh()->tahun_anggaran_id)->toBe($ta2027->id);

    app(UbahStatusTahunAnggaran::class)->jalankan([
        'id' => $ta2027->id,
        'status' => 'tutup',
        'alasan' => 'Penutupan buku tahun 2027',
    ]);

    expect($ta2027->fresh()->status)->toBe('tutup');

    $posL6 = PosAnggaran::where('kode', 'L6/1.1')->firstOrFail();

    expect(fn () => app(SimpanRealisasiManual::class)->jalankan([
        'tahun' => 2027,
        'bulan' => 1,
        'pos_anggaran_id' => $posL6->id,
        'nilai' => 10_000_000,
        'catatan' => 'Coba simpan saat tutup',
    ]))->toThrow(ValidationException::class);
});

it('PRC-T04: view v_realisasi_rkat dan rollup RealisasiRkatService cocok dengan penjumlahan sumber serta memisahkan L2/LN', function () {
    $pengumpul = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $stafDist = User::factory()->create()->assignRole(['staf_pendistribusian', 'amil']);
    $kabagDist = User::factory()->create()->assignRole(['kabag_pendis_pendag', 'amil']);
    $perencanaan = User::factory()->create()->assignRole(['perencanaan', 'amil']);

    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zm = SumberDana::where('kode', 'ZM')->firstOrFail();
    $fakir = Asnaf::where('kode', 'FAKIR')->firstOrFail();
    $uang = BentukBantuan::where('kode', 'UANG')->firstOrFail();
    $program = Program::where('kode', 'PDS-KEMANUSIAAN')->firstOrFail();

    // 1. Penerimaan: Rp 10.000.000 final + Rp 5.000.000 draft (draft tidak boleh masuk)
    $this->actingAs($pengumpul);
    $muzakki = app(SimpanMuzakki::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Muzakki Perorangan Uji']);
    $pFinal = app(SimpanDraftPenerimaan::class)->jalankan([
        'tanggal' => '2026-03-10',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 10_000_000]],
    ]);
    app(FinalkanPenerimaan::class)->jalankan(['id' => $pFinal->id]);

    app(SimpanDraftPenerimaan::class)->jalankan([
        'tanggal' => '2026-03-11',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 5_000_000]],
    ]);

    // 2. Penyaluran: Rp 3.000.000 dicairkan + Rp 2.000.000 draft (draft tidak boleh masuk)
    $this->actingAs($stafDist);
    $mustahik = app(SimpanMustahik::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Mustahik Fakir Uji']);
    $pyCair = app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => '2026-03-15',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'asnaf_id' => $fakir->id, 'bentuk_bantuan_id' => $uang->id, 'jumlah' => 3_000_000]],
    ]);
    app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => '2026-03-16',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'asnaf_id' => $fakir->id, 'bentuk_bantuan_id' => $uang->id, 'jumlah' => 2_000_000]],
    ]);

    $this->actingAs($kabagDist);
    app(SetujuiPenyaluran::class)->jalankan(['id' => $pyCair->id]);
    app(CairkanPenyaluran::class)->jalankan(['id' => $pyCair->id]);

    // 3. Realisasi manual untuk L2/LN/1.1 (Luar Neraca) dan target RKAT pada L2/1.2 + L2/LN/1.1
    $this->actingAs($perencanaan);
    $posL2ZmPerorangan = PosAnggaran::where('kode', 'L2/1.2')->firstOrFail();
    $posL2Ln = PosAnggaran::where('kode', 'L2/LN/1.1')->firstOrFail();

    app(SimpanRealisasiManual::class)->jalankan([
        'tahun' => 2026,
        'bulan' => 3,
        'pos_anggaran_id' => $posL2Ln->id,
        'nilai' => 50_000_000,
        'catatan' => 'Zakat fitrah luar neraca',
    ]);

    $rkat = RkatVersion::create([
        'tahun' => 2026,
        'tahun_anggaran_id' => TahunAnggaran::where('tahun', 2026)->value('id'),
        'jenis' => 'awal',
        'nomor_perubahan' => 0,
        'status' => 'disahkan',
        'sumber' => 'manual',
        'dibuat_oleh' => $perencanaan->id,
    ]);
    RkatValue::create([
        'rkat_version_id' => $rkat->id,
        'pos_anggaran_id' => $posL2ZmPerorangan->id,
        'kode_asal' => $posL2ZmPerorangan->kode,
        'tipe_nilai' => $posL2ZmPerorangan->tipe_nilai,
        'nilai' => 20_000_000,
    ]);
    RkatValue::create([
        'rkat_version_id' => $rkat->id,
        'pos_anggaran_id' => $posL2Ln->id,
        'kode_asal' => $posL2Ln->kode,
        'tipe_nilai' => $posL2Ln->tipe_nilai,
        'nilai' => 100_000_000,
    ]);

    // Verifikasi langsung query view SQL v_realisasi_rkat
    $viewRow = DB::table('v_realisasi_rkat')
        ->where('tahun', 2026)
        ->where('bulan', 3)
        ->where('pos_anggaran_id', $posL2ZmPerorangan->id)
        ->first();

    expect($viewRow)->not->toBeNull()
        ->and((int) $viewRow->nilai_otomatis)->toBe(10_000_000);

    // Verifikasi hasil service & rollup induk
    $hasil = app(RealisasiRkatService::class)->hitung(2026, 3);
    $petaBaris = collect($hasil['baris'])->keyBy('kode');

    expect($hasil['total_rkat'])->toBe(20_000_000)
        ->and($hasil['total_realisasi'])->toBe(10_000_000)
        ->and($hasil['ringkasan_lampiran']['L2/LN']['target_rkat'])->toBe(100_000_000)
        ->and($hasil['ringkasan_lampiran']['L2/LN']['realisasi'])->toBe(50_000_000)
        ->and($petaBaris['L2/1']['target_rkat'])->toBe(20_000_000)
        ->and($petaBaris['L2/1']['realisasi'])->toBe(10_000_000)
        ->and($petaBaris['L3/1']['realisasi'])->toBe(3_000_000)
        ->and($petaBaris['L4/1']['realisasi'])->toBe(3_000_000);
});

it('BR-05: realisasi manual bernilai negatif wajib catatan dan tersimpan jika catatan diisi', function () {
    $perencanaan = User::factory()->create()->assignRole(['perencanaan', 'amil']);
    $this->actingAs($perencanaan);

    $pos = PosAnggaran::where('kode', 'L6/1.1')->firstOrFail();

    expect(fn () => app(SimpanRealisasiManual::class)->jalankan([
        'tahun' => 2026,
        'bulan' => 2,
        'pos_anggaran_id' => $pos->id,
        'nilai' => -500_000,
    ]))->toThrow(ValidationException::class);

    $tersimpan = app(SimpanRealisasiManual::class)->jalankan([
        'tahun' => 2026,
        'bulan' => 2,
        'pos_anggaran_id' => $pos->id,
        'nilai' => -500_000,
        'catatan' => 'Koreksi kelebihan pencatatan bulan sebelumnya',
    ]);

    expect($tersimpan->nilai)->toBe(-500_000)
        ->and(AuditLog::where('entitas_tipe', RealisasiManual::class)->where('entitas_id', $tersimpan->id)->exists())->toBeTrue();
});

it('BR-07 & BR-12: target nol menghasilkan n/a, kumulatif sama dengan jumlah bulan 1..n, dan persen dibulatkan hanya saat tampil', function () {
    $perencanaan = User::factory()->create()->assignRole(['perencanaan', 'amil']);
    $this->actingAs($perencanaan);

    $posTarget = PosAnggaran::where('kode', 'L6/1.1')->firstOrFail();
    $posNol = PosAnggaran::where('kode', 'L6/1.2')->firstOrFail();

    $rkat = RkatVersion::create([
        'tahun' => 2026,
        'tahun_anggaran_id' => TahunAnggaran::where('tahun', 2026)->value('id'),
        'jenis' => 'awal',
        'nomor_perubahan' => 0,
        'status' => 'disahkan',
        'sumber' => 'manual',
        'dibuat_oleh' => $perencanaan->id,
    ]);
    RkatValue::create([
        'rkat_version_id' => $rkat->id,
        'pos_anggaran_id' => $posTarget->id,
        'kode_asal' => $posTarget->kode,
        'tipe_nilai' => $posTarget->tipe_nilai,
        'nilai' => 300_000,
    ]);

    app(SimpanRealisasiManual::class)->jalankan(['tahun' => 2026, 'bulan' => 1, 'pos_anggaran_id' => $posTarget->id, 'nilai' => 50_000]);
    app(SimpanRealisasiManual::class)->jalankan(['tahun' => 2026, 'bulan' => 2, 'pos_anggaran_id' => $posTarget->id, 'nilai' => 50_000]);
    app(SimpanRealisasiManual::class)->jalankan(['tahun' => 2026, 'bulan' => 2, 'pos_anggaran_id' => $posNol->id, 'nilai' => 25_000]);

    $hasil = app(RealisasiRkatService::class)->hitung(2026, 2, 'L6');
    $peta = collect($hasil['baris'])->keyBy('kode');

    // BR-07: kumulatif s.d. bulan 2 = 50.000 + 50.000 = 100.000; rencana nol = n/a
    expect($peta['L6/1.1']['realisasi'])->toBe(100_000)
        ->and($peta['L6/1.2']['persen'])->toBeNull()
        ->and($peta['L6/1.2']['persen_label'])->toBe('n/a');

    // BR-12: 100.000 / 300.000 = 33.3333...% -> persen mentah tidak dibulatkan ke integer, label tampil 33,3%
    expect($peta['L6/1.1']['persen'])->toBeGreaterThan(33.3)->toBeLessThan(33.4)
        ->and($peta['L6/1.1']['persen_label'])->toBe('33,3%');
});

it('Penguncian server-side: KunciRealisasiManual mengunci input dan buka kunci mewajibkan alasan', function () {
    $perencanaan = User::factory()->create()->assignRole(['perencanaan', 'amil']);
    $kabagPkp = User::factory()->create()->assignRole(['kabag_pkp', 'amil']);
    $pos = PosAnggaran::where('kode', 'L6/1.1')->firstOrFail();

    $this->actingAs($perencanaan);
    app(SimpanRealisasiManual::class)->jalankan([
        'tahun' => 2026,
        'bulan' => 4,
        'pos_anggaran_id' => $pos->id,
        'nilai' => 1_000_000,
    ]);

    // Staf perencanaan tidak punya hak approve untuk mengunci
    expect(fn () => app(KunciRealisasiManual::class)->jalankan([
        'tahun' => 2026,
        'bulan' => 4,
        'terkunci' => true,
    ]))->toThrow(AuthorizationException::class);

    // Kabag PKP mengunci bulan 4
    $this->actingAs($kabagPkp);
    app(KunciRealisasiManual::class)->jalankan([
        'tahun' => 2026,
        'bulan' => 4,
        'terkunci' => true,
    ]);

    // Request HTTP langsung ke route storeManual ditolak
    $this->actingAs($perencanaan)
        ->post(route('pkp.realisasi.manual'), [
            'tahun' => 2026,
            'bulan' => 4,
            'pos_anggaran_id' => $pos->id,
            'nilai' => 2_000_000,
        ])
        ->assertSessionHasErrors('nilai');

    // Buka kunci tanpa alasan ditolak
    $this->actingAs($kabagPkp);
    expect(fn () => app(KunciRealisasiManual::class)->jalankan([
        'tahun' => 2026,
        'bulan' => 4,
        'terkunci' => false,
    ]))->toThrow(ValidationException::class);

    // Buka kunci dengan alasan berhasil
    app(KunciRealisasiManual::class)->jalankan([
        'tahun' => 2026,
        'bulan' => 4,
        'terkunci' => false,
        'alasan' => 'Revisi bukti operasional April',
    ]);

    expect(RealisasiManual::where('tahun', 2026)->where('bulan', 4)->value('terkunci'))->toBeFalse();
});

it('Halaman PKP, ekspor PDF/Excel, dan penegakan RBAC berjalan sesuai matriks peran', function () {
    $perencanaan = User::factory()->create()->assignRole(['perencanaan', 'amil']);
    $upz = User::factory()->create()->assignRole(['upz']);
    $muzakki = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);

    $this->actingAs($perencanaan)
        ->get(route('pkp.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pkp/dasbor')
            ->has('ringkasan_lampiran')
            ->has('cuplikan_pos_l3')
            ->has('kinerja_pengumpulan.tren_bulanan', 12)
            ->has('kinerja_pengumpulan.komposisi_dana', 4)
            ->has('kinerja_distribusi.rasio_penyaluran_label'));

    $this->actingAs($perencanaan)
        ->get(route('pkp.laporan-setoran-upz'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('umum/upz/laporan-setoran')
            ->where('konteks_modul', 'pkp'));

    $this->actingAs($upz)
        ->get(route('umum.upz.laporan-setoran'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('umum/upz/laporan-setoran')
            ->where('konteks_modul', 'umum'));

    $this->actingAs($perencanaan)
        ->get(route('pkp.kegiatan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('pkp/kegiatan/index'));

    $this->actingAs($perencanaan)
        ->get(route('pkp.realisasi'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('pkp/realisasi/index'));

    $pdfResp = $this->actingAs($perencanaan)->get(route('pkp.realisasi.pdf', ['tahun' => 2026]));
    $pdfResp->assertOk();
    expect($pdfResp->headers->get('content-type'))->toContain('application/pdf');

    $excelResp = $this->actingAs($perencanaan)->get(route('pkp.realisasi.excel', ['tahun' => 2026]));
    $excelResp->assertOk();

    // Peran tanpa akses PKP ditolak 403 di server
    $this->actingAs($upz)->get(route('pkp.realisasi'))->assertForbidden();
    $this->actingAs($upz)->get(route('pkp.laporan-setoran-upz'))->assertForbidden();
    $this->actingAs($muzakki)->get(route('pkp.kegiatan.index'))->assertForbidden();
    $this->actingAs($muzakki)->get(route('pkp.laporan-setoran-upz'))->assertForbidden();
    $this->actingAs($muzakki)->post(route('pkp.realisasi.manual'), [])->assertForbidden();
});
