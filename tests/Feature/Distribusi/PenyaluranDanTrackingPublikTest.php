<?php

use App\Actions\Distribusi\BatalkanBeritaAcaraPenyaluran;
use App\Actions\Distribusi\BatalkanPenyaluran;
use App\Actions\Distribusi\BuatBeritaAcaraPenyaluran;
use App\Actions\Distribusi\BuatPermohonan;
use App\Actions\Distribusi\CairkanPenyaluran;
use App\Actions\Distribusi\FinalkanBeritaAcaraPenyaluran;
use App\Actions\Distribusi\ProsesLangkahPermohonan;
use App\Actions\Distribusi\SetujuiPenyaluran;
use App\Actions\Distribusi\SimpanMustahik;
use App\Actions\Distribusi\SimpanPenyaluran;
use App\Actions\Distribusi\UbahBeritaAcaraPenyaluran;
use App\Actions\Pengumpulan\FinalkanPenerimaan;
use App\Actions\Pengumpulan\SimpanDraftPenerimaan;
use App\Actions\Pengumpulan\SimpanMuzakki;
use App\Models\Asnaf;
use App\Models\BentukBantuan;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\JenisDana;
use App\Models\MetodeBayar;
use App\Models\Program;
use App\Models\SumberDana;
use App\Models\User;
use Database\Seeders\BidangSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([
        PeranSeeder::class,
        BidangSeeder::class,
        WilayahSeeder::class,
        MasterDanaSeeder::class,
        MasterOperasionalSeeder::class,
    ]);
});

it('PDP-T08 & PDP-T09 K10 K11 Q-16: validasi Zakat asnaf, barang bertaksiran rupiah, saldo Kelompok Dana, dan MakerChecker', function () {
    $pengumpul = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $stafDist = User::factory()->create()->assignRole(['staf_pendistribusian', 'amil']);
    $kabagDist = User::factory()->create()->assignRole(['kabag_pendis_pendag', 'amil']);

    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zm = SumberDana::where('kode', 'ZM')->firstOrFail();
    $ift = SumberDana::where('kode', 'IFT')->firstOrFail();
    $fakir = Asnaf::where('kode', 'ilike', 'fakir')->firstOrFail();
    $uang = BentukBantuan::where('kode', 'UANG')->firstOrFail();
    $barang = BentukBantuan::where('kode', 'BARANG')->firstOrFail();
    $program = Program::where('sub_bidang', 'pendistribusian')->firstOrFail();

    // Seed saldo Zakat masuk Rp 1.000.000 agar bisa menguji kecukupan saldo per Kelompok Dana.
    $this->actingAs($pengumpul);
    $muzakki = app(SimpanMuzakki::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Muzakki Uji']);
    $penerimaan = app(SimpanDraftPenerimaan::class)->jalankan([
        'tanggal' => '2026-11-22',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 1_000_000]],
    ]);
    app(FinalkanPenerimaan::class)->jalankan(['id' => $penerimaan->id]);

    $this->actingAs($stafDist);
    $mustahik = app(SimpanMustahik::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Mustahik Uji Penyaluran']);

    // K10: Infak tidak boleh memilih asnaf.
    expect(fn () => app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => '2026-11-22',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [[
            'sumber_dana_id' => $ift->id,
            'asnaf_id' => $fakir->id,
            'bentuk_bantuan_id' => $uang->id,
            'jumlah' => 250_000,
        ]],
    ]))->toThrow(ValidationException::class);

    // Q-16: Bantuan bentuk BARANG wajib mengisi uraian_barang + taksiran rupiah.
    expect(fn () => app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => '2026-11-22',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [[
            'sumber_dana_id' => $zm->id,
            'asnaf_id' => $fakir->id,
            'bentuk_bantuan_id' => $barang->id,
            'jumlah' => 500_000,
        ]],
    ]))->toThrow(ValidationException::class);

    // K11: Penyaluran melebihi saldo Kelompok Dana Zakat (Rp 1.500.000 > Rp 1.000.000) memberi peringatan namun tetap bisa disimpan sebagai draft.
    $draft = app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => '2026-11-22',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [[
            'sumber_dana_id' => $zm->id,
            'asnaf_id' => $fakir->id,
            'bentuk_bantuan_id' => $barang->id,
            'uraian_barang' => 'Gerobak Usaha & Bahan Pokok',
            'kuantitas' => 1,
            'satuan' => 'Paket',
            'jumlah' => 1_500_000,
        ]],
    ]);

    expect($draft->status)->toBe('draft')
        ->and($draft->peringatan_saldo)->toContain('Saldo Kelompok Dana');

    // MakerChecker: pembuat tidak bisa menyetujui penyalurannya sendiri.
    expect(fn () => app(SetujuiPenyaluran::class)->jalankan(['id' => $draft->id]))
        ->toThrow(AuthorizationException::class);

    // Kabag berbeda menyetujui lalu mencairkan.
    $this->actingAs($kabagDist);
    $disetujui = app(SetujuiPenyaluran::class)->jalankan(['id' => $draft->id]);
    expect($disetujui->status)->toBe('disetujui')
        ->and($disetujui->nomor)->toStartWith('PNY/');

    $dicairkan = app(CairkanPenyaluran::class)->jalankan(['id' => $disetujui->id]);
    expect($dicairkan->status)->toBe('dicairkan');
});

it('PDP-T10 & PDP-T11 Q-06: Berita Acara penyaluran final mengunci tanggal dan pembatalan menerbitkan BA Revisi serta ekspor', function () {
    $stafDist = User::factory()->create()->assignRole(['staf_pendistribusian', 'amil']);
    $kabagDist = User::factory()->create()->assignRole(['kabag_pendis_pendag', 'amil']);

    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $ift = SumberDana::where('kode', 'IFT')->firstOrFail();
    $uang = BentukBantuan::where('kode', 'UANG')->firstOrFail();
    $program = Program::where('sub_bidang', 'pendayagunaan')->firstOrFail();

    $this->actingAs($stafDist);
    $mustahik = app(SimpanMustahik::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Mustahik BA']);

    $buatDanCairkan = function (int $nominal) use ($stafDist, $kabagDist, $mustahik, $program, $tunai, $ift, $uang) {
        $this->actingAs($stafDist);
        $d = app(SimpanPenyaluran::class)->jalankan([
            'tanggal' => '2026-11-22',
            'mustahik_id' => $mustahik->id,
            'program_id' => $program->id,
            'metode_bayar_id' => $tunai->id,
            'items' => [['sumber_dana_id' => $ift->id, 'bentuk_bantuan_id' => $uang->id, 'jumlah' => $nominal]],
        ]);
        $this->actingAs($kabagDist);
        app(SetujuiPenyaluran::class)->jalankan(['id' => $d->id]);

        return app(CairkanPenyaluran::class)->jalankan(['id' => $d->id]);
    };

    $py1 = $buatDanCairkan(400_000);
    $py2 = $buatDanCairkan(600_000);

    $this->actingAs($kabagDist);
    $baDraft = app(BuatBeritaAcaraPenyaluran::class)->jalankan(['tanggal' => '2026-11-22']);
    $baFinal = app(FinalkanBeritaAcaraPenyaluran::class)->jalankan(['id' => $baDraft->id]);

    expect($baFinal->status)->toBe('final')
        ->and($baFinal->total_rupiah)->toBe(1_000_000);

    // Tanggal terkunci setelah BA final.
    $this->actingAs($stafDist);
    expect(fn () => app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => '2026-11-22',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $ift->id, 'bentuk_bantuan_id' => $uang->id, 'jumlah' => 100_000]],
    ]))->toThrow(ValidationException::class);

    // MakerChecker pembatalan $py1 menerbitkan BA Revisi.
    $this->actingAs($kabagDist);
    app(BatalkanPenyaluran::class)->jalankan([
        'id' => $py1->id,
        'alasan' => 'Penerima pindah alamat luar kabupaten.',
    ]);

    expect($baFinal->fresh()->status)->toBe('digantikan');
    $baRevisi = BeritaAcaraPenyaluran::where('status', 'final')->where('revisi_ke', 1)->first();
    expect($baRevisi)->not->toBeNull()
        ->and($baRevisi->total_rupiah)->toBe(600_000)
        ->and($py2->fresh()->berita_acara_id)->toBe($baRevisi->id);

    $this->get(route('distribusi.laporan.index', ['jenis' => 'bulanan', 'tahun' => 2026, 'bulan' => 11]))->assertOk();
    $this->get(route('distribusi.laporan.pdf', ['jenis' => 'bulanan', 'tahun' => 2026, 'bulan' => 11]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    $this->get(route('distribusi.laporan.excel', ['jenis' => 'bulanan', 'tahun' => 2026, 'bulan' => 11]))
        ->assertOk();
});

it('PDP-T12 & PDP-T13 Q-09 Q-14: portal lacak publik memvalidasi 4 digit telepon, menyamarkan nama staf, dan menyederhanakan status', function () {
    $pelayanan = User::factory()->create(['name' => 'Petugas Internal Rahasia'])->assignRole(['pelayanan_mustahik', 'amil']);
    $ketua = User::factory()->create(['name' => 'Ketua Rahasia'])->assignRole(['ketua', 'amil']);
    $kabag = User::factory()->create(['name' => 'Kabag Rahasia'])->assignRole(['kabag_pendis_pendag', 'amil']);
    $waka2 = User::factory()->create(['name' => 'Waka Dua Rahasia'])->assignRole(['waka_2', 'amil']);

    $this->actingAs($pelayanan);
    $mustahik = app(SimpanMustahik::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Kartika Sari',
        'telepon' => '081399885678',
    ]);
    $program = Program::firstOrFail();
    $zakat = JenisDana::where('kode', 'ZAKAT')->firstOrFail();
    $fakir = Asnaf::where('kode', 'ilike', 'fakir')->firstOrFail();

    $permohonan = app(BuatPermohonan::class)->jalankan([
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'jenis_dana_id' => $zakat->id,
        'asnaf_id' => $fakir->id,
        'nominal_diajukan' => 1_500_000,
    ]);

    app(ProsesLangkahPermohonan::class)->jalankan(['permohonan_id' => $permohonan->id]);
    $this->actingAs($ketua);
    app(ProsesLangkahPermohonan::class)->jalankan(['permohonan_id' => $permohonan->id]);
    $this->actingAs($kabag);
    app(ProsesLangkahPermohonan::class)->jalankan(['permohonan_id' => $permohonan->id, 'catatan_asesmen' => 'Layak']);
    $this->actingAs($waka2);
    app(ProsesLangkahPermohonan::class)->jalankan(['permohonan_id' => $permohonan->id, 'nominal_usulan' => 1_500_000]);
    $this->actingAs($ketua);
    $permohonan = app(ProsesLangkahPermohonan::class)->jalankan(['permohonan_id' => $permohonan->id, 'nominal_disetujui' => 1_500_000]);

    // Cek rate limiter throttle:10,1 aktif pada rute tracking publik.
    $routeObj = Route::getRoutes()->getByName('publik.lacak-permohonan');
    expect($routeObj->gatherMiddleware())->toContain('throttle:10,1');

    // Kombinasi 4 digit telepon salah ditolak.
    auth()->logout();
    $this->get(route('publik.lacak-permohonan', ['kode_tracking' => $permohonan->kode_tracking, 'telepon_4' => '0000']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/lacak-permohonan')
            ->where('hasil', null)
            ->where('pesan_error', fn ($v) => ! empty($v)));

    // Kombinasi benar menampilkan status publik 'disetujui' (bukan internal 'minta_keuangan'), menyamarkan pemohon, dan tidak membocorkan nama petugas internal.
    $this->get(route('publik.lacak-permohonan', ['kode_tracking' => $permohonan->kode_tracking, 'telepon_4' => '5678']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/lacak-permohonan')
            ->where('hasil.kode_tracking', $permohonan->kode_tracking)
            ->where('hasil.status_publik', 'disetujui')
            ->where('hasil.pemohon_samaran', fn ($v) => str_contains((string) $v, '*')))
        ->assertDontSee('Petugas Internal Rahasia')
        ->assertDontSee('Ketua Rahasia')
        ->assertDontSee('minta_keuangan');
});

it('PDP-T10 Q-06: draft dan final BA penyaluran dapat diubah serta dibatalkan untuk membuka kunci tanggal dan revisi', function () {
    $stafDist = User::factory()->create()->assignRole(['staf_pendistribusian', 'amil']);
    $kabagDist = User::factory()->create()->assignRole(['kabag_pendis_pendag', 'amil']);

    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $ift = SumberDana::where('kode', 'IFT')->firstOrFail();
    $uang = BentukBantuan::where('kode', 'UANG')->firstOrFail();
    $program = Program::where('sub_bidang', 'pendistribusian')->firstOrFail();

    $this->actingAs($stafDist);
    $mustahik = app(SimpanMustahik::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Mustahik BA Batal']);
    $draftPy = app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => '2026-11-23',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $ift->id, 'bentuk_bantuan_id' => $uang->id, 'jumlah' => 500_000]],
    ]);

    $this->actingAs($kabagDist);
    app(SetujuiPenyaluran::class)->jalankan(['id' => $draftPy->id]);
    $py = app(CairkanPenyaluran::class)->jalankan(['id' => $draftPy->id]);

    $baDraft = app(BuatBeritaAcaraPenyaluran::class)->jalankan(['tanggal' => '2026-11-23']);
    $baDiubah = app(UbahBeritaAcaraPenyaluran::class)->jalankan(['id' => $baDraft->id, 'catatan' => 'Koreksi catatan penyaluran']);
    expect($baDiubah->catatan)->toBe('Koreksi catatan penyaluran');

    $baBatalDraft = app(BatalkanBeritaAcaraPenyaluran::class)->jalankan([
        'id' => $baDraft->id,
        'alasan' => 'Salah memilih tanggal berita acara penyaluran.',
    ]);
    expect($baBatalDraft->status)->toBe('batal')
        ->and($py->fresh()->berita_acara_id)->toBeNull();

    $baUlang = app(BuatBeritaAcaraPenyaluran::class)->jalankan(['tanggal' => '2026-11-23']);
    expect($baUlang->revisi_ke)->toBe(0);

    $baFinal = app(FinalkanBeritaAcaraPenyaluran::class)->jalankan(['id' => $baUlang->id]);
    $this->actingAs($stafDist);
    expect(fn () => app(BatalkanBeritaAcaraPenyaluran::class)->jalankan([
        'id' => $baFinal->id,
        'alasan' => 'Staf biasa tidak boleh membatalkan BA final.',
    ]))->toThrow(AuthorizationException::class);

    $this->actingAs($kabagDist);
    $baBatalFinal = app(BatalkanBeritaAcaraPenyaluran::class)->jalankan([
        'id' => $baFinal->id,
        'alasan' => 'Perlu pencairan susulan pada tanggal yang sama.',
    ]);
    expect($baBatalFinal->status)->toBe('batal');

    $baRevisi = app(BuatBeritaAcaraPenyaluran::class)->jalankan(['tanggal' => '2026-11-23']);
    expect($baRevisi->revisi_ke)->toBe(1);
});

it('PDP-T09 & PDP-T13: dasbor pendistribusian tanpa saldo kelompok dana, tabel penyaluran memuat asnaf & program, dan QR lacak terisi rapi', function () {
    $kabagDist = User::factory()->create()->assignRole(['kabag_pendis_pendag', 'amil']);
    $stafDist = User::factory()->create()->assignRole(['staf_pendistribusian', 'amil']);

    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zm = SumberDana::where('kode', 'ZM')->firstOrFail();
    $fakir = Asnaf::where('kode', 'ilike', 'fakir')->firstOrFail();
    $uang = BentukBantuan::where('kode', 'UANG')->firstOrFail();
    $program = Program::where('sub_bidang', 'pendistribusian')->firstOrFail();

    $this->actingAs($stafDist);
    $mustahik = app(SimpanMustahik::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Pak Rahman', 'telepon' => '081200001234']);
    app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => now()->toDateString(),
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'asnaf_id' => $fakir->id, 'bentuk_bantuan_id' => $uang->id, 'jumlah' => 450_000]],
    ]);

    $permohonan = app(BuatPermohonan::class)->jalankan([
        'mustahik_id' => $mustahik->id,
        'pemohon_sama_mustahik' => false,
        'pemohon_nama' => 'Ibu Aminah',
        'pemohon_telepon' => '081377774321',
        'pemohon_hubungan' => 'Keluarga',
        'program_id' => $program->id,
        'jenis_dana_id' => JenisDana::where('kode', 'ZAKAT')->firstOrFail()->id,
        'asnaf_id' => $fakir->id,
        'nominal_diajukan' => 450_000,
    ]);

    $this->actingAs($kabagDist)
        ->get(route('distribusi.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('distribusi/dasbor')
            ->missing('saldo_kelompok')
            ->has('grafik_program')
            ->has('grafik_asnaf'));

    $this->get(route('distribusi.penyaluran.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('distribusi/penyaluran/index')
            ->where('penyaluran.data.0.program', $program->nama)
            ->where('penyaluran.data.0.asnaf', $fakir->nama));

    // Buka portal lacak langsung dari QR (?kode_tracking=...) tanpa memunculkan pesan error lebih dulu.
    auth()->logout();
    $this->get(route('publik.lacak-permohonan', ['kode_tracking' => $permohonan->kode_tracking]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/lacak-permohonan')
            ->where('sudah_dicari', false)
            ->where('pesan_error', null)
            ->where('kode_tracking', $permohonan->kode_tracking));

    // Lacak menggunakan 4 digit akhir telepon pemohon terpisah.
    $this->get(route('publik.lacak-permohonan', ['kode_tracking' => $permohonan->kode_tracking, 'telepon_4' => '4321', 'cari' => 1]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/lacak-permohonan')
            ->where('hasil.pemohon_sama_mustahik', false)
            ->where('hasil.kode_tracking', $permohonan->kode_tracking));
});
