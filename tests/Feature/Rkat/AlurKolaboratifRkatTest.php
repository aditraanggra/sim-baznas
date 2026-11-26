<?php

use App\Actions\Rkat\AjukanRkat;
use App\Actions\Rkat\AjukanTahapBidangRkat;
use App\Actions\Rkat\BuatVersiRkat;
use App\Actions\Rkat\KembalikanTahapBidangRkat;
use App\Actions\Rkat\SahkanRkat;
use App\Actions\Rkat\SimpanAturanAmilRkat;
use App\Actions\Rkat\SimpanCatatanRkat;
use App\Actions\Rkat\SimpanJenisKegiatanRkat;
use App\Actions\Rkat\SimpanKegiatanRkat;
use App\Actions\Rkat\SimpanNilaiRkat;
use App\Actions\Rkat\SimpanPelengkapBidangRkat;
use App\Actions\Rkat\VerifikasiTahapBidangRkat;
use App\Models\AuditLog;
use App\Models\Notifikasi;
use App\Models\PosAnggaran;
use App\Models\RkatAturanAmil;
use App\Models\RkatJenisKegiatan;
use App\Models\RkatKegiatanRencana;
use App\Models\RkatValue;
use App\Models\User;
use App\Services\Rkat\HitungRkat;
use App\Services\Rkat\ValidasiRkat;
use Database\Seeders\BidangSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\PosAnggaranAlurBaruSeeder;
use Database\Seeders\PosAnggaranSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;

beforeEach(function (): void {
    $this->seed([
        PeranSeeder::class,
        BidangSeeder::class,
        PosAnggaranSeeder::class,
        PosAnggaranAlurBaruSeeder::class,
    ]);
});

function buatUser(string|array $peran): User
{
    $user = User::factory()->create(['status' => 'aktif']);
    $user->assignRole($peran);

    return $user;
}

function posByKode(string $kode): PosAnggaran
{
    return PosAnggaran::where('kode', $kode)->firstOrFail();
}

it('K17/VAL-1..6: alur penuh 3 bidang -> verifikasi Kabag PKP -> konsolidasi L2-L7 -> ajukan & sahkan', function (): void {
    $perencanaan = buatUser(['perencanaan', 'amil']);
    $kabagPkp = buatUser(['kabag_pkp', 'amil']);
    $kabagPengumpulan = buatUser(['kabag_pengumpulan', 'amil']);
    $kabagPendis = buatUser(['kabag_pendis_pendag', 'amil']);
    $kabagSau = buatUser(['kabag_sau', 'amil']);

    // 1. PKP membuat RKAT 2027 Kolaboratif dengan target Rp 1.000.000.000
    $this->actingAs($perencanaan);
    $versi = app(BuatVersiRkat::class)->jalankan([
        'tahun' => 2027,
        'jenis' => 'awal',
        'mode_penyusunan' => 'kolaboratif',
        'target_pengumpulan' => 1_000_000_000,
    ]);

    expect($versi->isKolaboratif())->toBeTrue()
        ->and($versi->aturanAmil()->count())->toBe(24)
        ->and($versi->tahapBidang()->where('bidang_kode', 'pengumpulan')->value('status'))->toBe('draft')
        ->and($versi->tahapBidang()->where('bidang_kode', 'distribusi')->value('status'))->toBe('menunggu')
        ->and($versi->tahapBidang()->where('bidang_kode', 'umum')->value('status'))->toBe('menunggu')
        ->and(Notifikasi::where('user_id', $kabagPengumpulan->id)->where('jenis', 'rkat_target')->exists())->toBeTrue();

    // 2. Pengumpulan merinci kegiatan hingga persis Rp 1.000.000.000 + L2 Luar Neraca
    $this->actingAs($kabagPengumpulan);
    $jkFitrah = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Pengumpulan Zakat Fitrah',
        'pos_anggaran_id' => posByKode('L2/1.4')->id,
        'satuan_default' => 'muzakki',
    ]);
    $jkPalestina = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Donasi Kemanusiaan Palestina',
        'pos_anggaran_id' => posByKode('L2/2.4')->id,
        'satuan_default' => 'orang',
    ]);
    $jkNatura = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Infak Terikat Natura Sembako',
        'pos_anggaran_id' => posByKode('L2/2.8')->id,
        'satuan_default' => 'paket',
    ]);
    $jkPendidikan = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Infak Terikat Program Pendidikan',
        'pos_anggaran_id' => posByKode('L2/2.11')->id,
        'satuan_default' => 'orang',
    ]);
    $jkFidyah = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Pengumpulan DSKL Fidyah',
        'pos_anggaran_id' => posByKode('L2/5.3')->id,
        'satuan_default' => 'orang',
    ]);
    $jkDam = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Pengumpulan DSKL Dam',
        'pos_anggaran_id' => posByKode('L2/5.4')->id,
        'satuan_default' => 'orang',
    ]);

    // 400 jt Fitrah (12.5% amil = 50 jt; penyaluran zakat = 350 jt)
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jkFitrah->id,
        'jumlah' => 8000,
        'frekuensi' => 1,
        'besaran' => 50_000,
    ]);
    // 200 jt Palestina (5% amil = 10 jt; penyaluran infak terikat = 190 jt)
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jkPalestina->id,
        'jumlah' => 1000,
        'frekuensi' => 2,
        'besaran' => 100_000,
    ]);
    // 100 jt Natura (0% amil = 0; penyaluran infak terikat = 100 jt)
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jkNatura->id,
        'jumlah' => 500,
        'frekuensi' => 1,
        'besaran' => 200_000,
    ]);
    // 150 jt Infak Pendidikan (20% amil = 30 jt; penyaluran infak terikat = 120 jt)
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jkPendidikan->id,
        'jumlah' => 1500,
        'frekuensi' => 1,
        'besaran' => 100_000,
    ]);
    // 100 jt Fidyah (20% amil = 20 jt; penyaluran fidyah = 80 jt)
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jkFidyah->id,
        'jumlah' => 1000,
        'frekuensi' => 1,
        'besaran' => 100_000,
    ]);
    // Belum genap 1 M (baru 950 jt): ajukan harus ditolak
    expect(fn () => app(AjukanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
    ]))->toThrow(ValidationException::class);

    // Tambah 50 jt Dam (0% amil = 0; penyaluran dskl = 50 jt) -> genap 1 M
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jkDam->id,
        'jumlah' => 500,
        'frekuensi' => 1,
        'besaran' => 100_000,
    ]);
    // Luar Neraca 200 jt
    app(SimpanPelengkapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'nilai' => [posByKode('L2/LN/1.1')->id => 200_000_000],
    ]);

    app(AjukanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
    ]);

    // 3. Verifikasi Tahap Pengumpulan oleh Kabag PKP
    $this->actingAs($kabagPkp);
    app(VerifikasiTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
    ]);

    // Total Hak Amil: 50jt (Zakat) + 40jt (Infak Terikat) + 20jt (Fidyah) = 110jt; Penyaluran = 890jt
    $versi->refresh();
    expect($versi->tahapBidang()->where('bidang_kode', 'distribusi')->value('status'))->toBe('draft')
        ->and($versi->tahapBidang()->where('bidang_kode', 'umum')->value('status'))->toBe('draft')
        ->and(Notifikasi::where('user_id', $kabagPendis->id)->where('jenis', 'rkat_pagu_terbuka')->exists())->toBeTrue()
        ->and(Notifikasi::where('user_id', $kabagSau->id)->where('jenis', 'rkat_pagu_terbuka')->exists())->toBeTrue();

    // 4. Bidang Pendistribusian & Pendayagunaan merinci kegiatan sesuai 7 ember pagu:
    //    Zakat = 350 jt, Infak Terikat = 410 jt, Fidyah = 80 jt, DSKL = 50 jt
    $this->actingAs($kabagPendis);
    $jkSalurZakat = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'distribusi',
        'nama' => 'Bantuan Biaya Hidup Fakir Miskin',
        'pos_anggaran_id' => posByKode('L3/1.2')->id,
        'pos_program_id' => posByKode('L4/1.1')->id,
        'satuan_default' => 'mustahik',
    ]);
    $jkSalurInfak = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'distribusi',
        'nama' => 'Beasiswa Pendidikan & Palestina',
        'pos_anggaran_id' => posByKode('L3/3.1')->id,
        'pos_program_id' => posByKode('L4/2.3')->id,
        'satuan_default' => 'mustahik',
    ]);
    $jkSalurFidyah = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'distribusi',
        'nama' => 'Paket Makan Fidyah Dhuafa',
        'pos_anggaran_id' => posByKode('L3/7.1')->id,
        'pos_program_id' => posByKode('L4/1.1')->id,
        'satuan_default' => 'mustahik',
    ]);
    $jkSalurDskl = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'distribusi',
        'nama' => 'Santunan Program Dakwah DSKL',
        'pos_anggaran_id' => posByKode('L3/8.1')->id,
        'pos_program_id' => posByKode('L4/1.4')->id,
        'satuan_default' => 'mustahik',
    ]);

    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
        'rkat_jenis_kegiatan_id' => $jkSalurZakat->id,
        'jumlah' => 700,
        'frekuensi' => 1,
        'besaran' => 500_000, // 350 jt
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
        'rkat_jenis_kegiatan_id' => $jkSalurInfak->id,
        'jumlah' => 410,
        'frekuensi' => 1,
        'besaran' => 1_000_000, // 410 jt
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
        'rkat_jenis_kegiatan_id' => $jkSalurFidyah->id,
        'jumlah' => 800,
        'frekuensi' => 1,
        'besaran' => 100_000, // 80 jt
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
        'rkat_jenis_kegiatan_id' => $jkSalurDskl->id,
        'jumlah' => 100,
        'frekuensi' => 1,
        'besaran' => 500_000, // 50 jt
    ]);
    app(SimpanPelengkapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
        'nilai' => [
            posByKode('L5/3.1')->id => 120,
            posByKode('L5/3.2')->id => 250,
        ],
    ]);

    app(AjukanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
    ]);

    // 5. Bidang SAU merinci Belanja Operasional = Hak Amil (110 jt) + Hibah APBD (40 jt) = 150 jt
    $this->actingAs($kabagSau);
    $jkGaji = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'umum',
        'nama' => 'Belanja Pegawai Amil',
        'pos_anggaran_id' => posByKode('L7/1.1.1')->id,
        'satuan_default' => 'bulan',
    ]);

    app(SimpanPelengkapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'umum',
        'nilai' => [posByKode('L6/1.8')->id => 40_000_000],
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'umum',
        'rkat_jenis_kegiatan_id' => $jkGaji->id,
        'jumlah' => 12,
        'frekuensi' => 1,
        'besaran' => 12_500_000, // 150 jt
    ]);

    app(AjukanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'umum',
    ]);

    // Sebelum SAU & Pendis diverifikasi, AjukanRkat harus ditolak
    $this->actingAs($perencanaan);
    expect(fn () => app(AjukanRkat::class)->jalankan(['rkat_version_id' => $versi->id]))
        ->toThrow(ValidationException::class);

    // 6. Kabag PKP verifikasi Pendis & SAU
    $this->actingAs($kabagPkp);
    app(VerifikasiTahapBidangRkat::class)->jalankan(['rkat_version_id' => $versi->id, 'bidang_kode' => 'distribusi']);
    app(VerifikasiTahapBidangRkat::class)->jalankan(['rkat_version_id' => $versi->id, 'bidang_kode' => 'umum']);

    // 7. Verifikasi angka L2-L7 & VAL-1..VAL-6 lulus 6/6, ajukan & sahkan
    $hasil = HitungRkat::untukVersi($versi->fresh());
    $perKode = $hasil['per_kode'];
    expect($perKode['L2/1'])->toBe(400_000_000)
        ->and($perKode['L2/2'])->toBe(450_000_000)
        ->and($perKode['L2/5'])->toBe(150_000_000)
        ->and($perKode['L2/LN/1.1'])->toBe(200_000_000)
        ->and($perKode['L3/1.3'])->toBe(50_000_000)
        ->and($perKode['L3/3.2'])->toBe(40_000_000)
        ->and($perKode['L3/7.2'])->toBe(20_000_000)
        ->and($perKode['L4/1'])->toBe(480_000_000)
        ->and($perKode['L4/2'])->toBe(410_000_000)
        ->and($perKode['L5/1'])->toBe(12_500)
        ->and($perKode['L5/2'])->toBe(2_010)
        ->and($perKode['L6/1'])->toBe(150_000_000)
        ->and($perKode['L7/1'])->toBe(150_000_000);

    $validasi = AjukanRkat::evaluasiVersi($versi->fresh());
    expect(ValidasiRkat::semuaLulus($validasi))->toBeTrue();

    $this->actingAs($perencanaan);
    // SimpanCatatanRkat pada kolaboratif hanya memperbarui teks catatan tanpa menimpa batas otomatis
    app(SimpanCatatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'batas_amil_zakat_bp' => 9999,
        'catatan' => 'Disetujui rapat pleno RKAT 2027',
    ]);
    expect($versi->fresh()->catatan->batas_amil_zakat_bp)->toBe(1250)
        ->and($versi->fresh()->catatan->catatan)->toBe('Disetujui rapat pleno RKAT 2027');

    $ajukan = app(AjukanRkat::class)->jalankan(['rkat_version_id' => $versi->id]);
    expect($ajukan['versi']->status)->toBe('diajukan');

    app(SahkanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'nomor_surat' => 'SK-RKAT/001/2027',
        'tanggal_surat' => '2026-12-20',
    ]);

    expect($versi->fresh()->status)->toBe('disahkan');

    // Pastikan halaman PKP dan halaman bidang merender props kolaboratif dengan benar
    $this->actingAs($kabagPkp)
        ->get(route('pkp.rencana.show', $versi))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pkp/rencana/show')
            ->where('versi.mode_penyusunan', 'kolaboratif')
            ->where('dapat_verifikasi', true)
            ->has('kolaboratif.tahap_bidang', 3)
            ->where('pos', fn ($pos) => collect($pos)->every(fn ($p) => $p['editable'] === false)));

    $this->actingAs($kabagPengumpulan)
        ->get(route('pengumpulan.rkat.index', ['versi' => $versi->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('rkat/bidang/index')
            ->where('bidang.kode', 'pengumpulan')
            ->where('dapat_kelola', true)
            ->where('ringkasan.pengumpulan.terpenuhi', true));
});

it('K17/BR-12/VAL-6: pembulatan floor BIGINT Hak Amil menjamin amil + penyaluran = total dan penyesuaian aturan <= maks', function (): void {
    $perencanaan = buatUser(['perencanaan', 'amil']);
    $kabagPengumpulan = buatUser(['kabag_pengumpulan', 'amil']);

    $this->actingAs($perencanaan);
    $versi = app(BuatVersiRkat::class)->jalankan([
        'tahun' => 2027,
        'jenis' => 'awal',
        'mode_penyusunan' => 'kolaboratif',
        'target_pengumpulan' => 100_005,
    ]);

    $this->actingAs($kabagPengumpulan);
    $jk = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Zakat Fitrah Nominal Ganjil',
        'pos_anggaran_id' => posByKode('L2/1.4')->id,
        'satuan_default' => 'paket',
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jk->id,
        'jumlah' => 1,
        'frekuensi' => 1,
        'besaran' => 100_005,
    ]);

    $hasil = HitungRkat::untukVersi($versi->fresh())['per_kode'];
    // intdiv(100005 * 1250, 10000) = 12500 -> <= 12.5%
    expect($hasil['L3/1.3'])->toBe(12_500);
    $val6 = collect(AjukanRkat::evaluasiVersi($versi->fresh()))->firstWhere('id', 'VAL-6');
    expect($val6['lulus'])->toBeTrue();

    // PKP menyesuaikan Infak Natura dari 0% ke 10% (<= maks 20%) berhasil; melebihi maks Palestina (>5%) ditolak
    $this->actingAs($perencanaan);
    $aturanNatura = RkatAturanAmil::where('rkat_version_id', $versi->id)
        ->where('pos_anggaran_id', posByKode('L2/2.8')->id)
        ->firstOrFail();
    $aturanPalestina = RkatAturanAmil::where('rkat_version_id', $versi->id)
        ->where('pos_anggaran_id', posByKode('L2/2.4')->id)
        ->firstOrFail();

    app(SimpanAturanAmilRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'persen' => [$aturanNatura->id => 1000],
    ]);
    expect($aturanNatura->fresh()->persen_bp)->toBe(1000);

    expect(fn () => app(SimpanAturanAmilRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'persen' => [$aturanPalestina->id => 501],
    ]))->toThrow(ValidationException::class);
});

it('K17: revisi cascade mengembalikan Pengumpulan me-reset Pendis & SAU ke menunggu tanpa menghapus kegiatan + notifikasi', function (): void {
    $perencanaan = buatUser(['perencanaan', 'amil']);
    $kabagPkp = buatUser(['kabag_pkp', 'amil']);
    $kabagPengumpulan = buatUser(['kabag_pengumpulan', 'amil']);
    $kabagPendis = buatUser(['kabag_pendis_pendag', 'amil']);
    $kabagSau = buatUser(['kabag_sau', 'amil']);

    $this->actingAs($perencanaan);
    $versi = app(BuatVersiRkat::class)->jalankan([
        'tahun' => 2027,
        'jenis' => 'awal',
        'mode_penyusunan' => 'kolaboratif',
        'target_pengumpulan' => 100_000_000,
    ]);

    $this->actingAs($kabagPengumpulan);
    $jk = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Zakat Mal Perorangan',
        'pos_anggaran_id' => posByKode('L2/1.2')->id,
        'satuan_default' => 'muzakki',
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jk->id,
        'jumlah' => 100,
        'frekuensi' => 1,
        'besaran' => 1_000_000,
    ]);
    app(AjukanTahapBidangRkat::class)->jalankan(['rkat_version_id' => $versi->id, 'bidang_kode' => 'pengumpulan']);

    $this->actingAs($kabagPkp);
    app(VerifikasiTahapBidangRkat::class)->jalankan(['rkat_version_id' => $versi->id, 'bidang_kode' => 'pengumpulan']);

    // Pendis & SAU sudah mengisi kegiatan
    $this->actingAs($kabagPendis);
    $jkDist = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'distribusi',
        'nama' => 'Bantuan Pendidikan Miskin',
        'pos_anggaran_id' => posByKode('L3/1.2')->id,
        'pos_program_id' => posByKode('L4/1.3')->id,
        'satuan_default' => 'mustahik',
    ]);
    $barisDist = app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
        'rkat_jenis_kegiatan_id' => $jkDist->id,
        'jumlah' => 50,
        'frekuensi' => 1,
        'besaran' => 1_750_000,
    ]);

    $this->actingAs($kabagSau);
    $jkSau = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'umum',
        'nama' => 'Belanja Pegawai',
        'pos_anggaran_id' => posByKode('L7/1.1.1')->id,
        'satuan_default' => 'bulan',
    ]);
    $barisSau = app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'umum',
        'rkat_jenis_kegiatan_id' => $jkSau->id,
        'jumlah' => 10,
        'frekuensi' => 1,
        'besaran' => 1_250_000,
    ]);

    // Kabag PKP mengembalikan tahap Pengumpulan
    $this->actingAs($kabagPkp);
    app(KembalikanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'alasan' => 'Perlu pecah komponen UPZ',
    ]);

    expect($versi->tahapBidang()->where('bidang_kode', 'pengumpulan')->value('status'))->toBe('draft')
        ->and($versi->tahapBidang()->where('bidang_kode', 'distribusi')->value('status'))->toBe('menunggu')
        ->and($versi->tahapBidang()->where('bidang_kode', 'umum')->value('status'))->toBe('menunggu')
        ->and(RkatKegiatanRencana::where('id', $barisDist->id)->whereNull('diarsipkan_pada')->exists())->toBeTrue()
        ->and(RkatKegiatanRencana::where('id', $barisSau->id)->whereNull('diarsipkan_pada')->exists())->toBeTrue()
        ->and(Notifikasi::where('user_id', $kabagPengumpulan->id)->where('jenis', 'rkat_bidang_dikembalikan')->exists())->toBeTrue()
        ->and(Notifikasi::where('user_id', $kabagPendis->id)->where('jenis', 'rkat_tahap_direset')->exists())->toBeTrue()
        ->and(Notifikasi::where('user_id', $kabagSau->id)->where('jenis', 'rkat_tahap_direset')->exists())->toBeTrue();
});

it('K17/BR-01/BR-02: RKAT Perubahan kolaboratif menyalin target, aturan amil, kegiatan 3 bidang, dan pos pelengkap', function (): void {
    $perencanaan = buatUser(['perencanaan', 'amil']);
    $kabagPkp = buatUser(['kabag_pkp', 'amil']);
    $kabagPengumpulan = buatUser(['kabag_pengumpulan', 'amil']);
    $kabagPendis = buatUser(['kabag_pendis_pendag', 'amil']);
    $kabagSau = buatUser(['kabag_sau', 'amil']);

    $this->actingAs($perencanaan);
    $awal = app(BuatVersiRkat::class)->jalankan([
        'tahun' => 2027,
        'jenis' => 'awal',
        'mode_penyusunan' => 'kolaboratif',
        'target_pengumpulan' => 100_000_000,
    ]);

    $this->actingAs($kabagPengumpulan);
    $jk = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Zakat Mal',
        'pos_anggaran_id' => posByKode('L2/1.2')->id,
        'satuan_default' => 'muzakki',
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $awal->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jk->id,
        'jumlah' => 100,
        'frekuensi' => 1,
        'besaran' => 1_000_000,
    ]);
    app(AjukanTahapBidangRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'bidang_kode' => 'pengumpulan']);
    $this->actingAs($kabagPkp);
    app(VerifikasiTahapBidangRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'bidang_kode' => 'pengumpulan']);

    $this->actingAs($kabagPendis);
    $jkDist = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'distribusi',
        'nama' => 'Santunan Fakir',
        'pos_anggaran_id' => posByKode('L3/1.1')->id,
        'pos_program_id' => posByKode('L4/1.1')->id,
        'satuan_default' => 'mustahik',
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $awal->id,
        'bidang_kode' => 'distribusi',
        'rkat_jenis_kegiatan_id' => $jkDist->id,
        'jumlah' => 100,
        'frekuensi' => 1,
        'besaran' => 875_000,
    ]);
    app(AjukanTahapBidangRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'bidang_kode' => 'distribusi']);

    $this->actingAs($kabagSau);
    $jkSau = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'umum',
        'nama' => 'ATK Operasional',
        'pos_anggaran_id' => posByKode('L7/1.1.4')->id,
        'satuan_default' => 'paket',
    ]);
    app(SimpanPelengkapBidangRkat::class)->jalankan([
        'rkat_version_id' => $awal->id,
        'bidang_kode' => 'umum',
        'nilai' => [posByKode('L6/1.8')->id => 10_000_000],
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $awal->id,
        'bidang_kode' => 'umum',
        'rkat_jenis_kegiatan_id' => $jkSau->id,
        'jumlah' => 1,
        'frekuensi' => 1,
        'besaran' => 22_500_000,
    ]);
    app(AjukanTahapBidangRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'bidang_kode' => 'umum']);

    $this->actingAs($kabagPkp);
    app(VerifikasiTahapBidangRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'bidang_kode' => 'distribusi']);
    app(VerifikasiTahapBidangRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'bidang_kode' => 'umum']);
    app(AjukanRkat::class)->jalankan(['rkat_version_id' => $awal->id]);
    app(SahkanRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'nomor_surat' => 'B-1/2027', 'tanggal_surat' => '2026-12-10']);

    // Buat versi Perubahan
    $perubahan = app(BuatVersiRkat::class)->jalankan(['tahun' => 2027, 'jenis' => 'perubahan']);

    expect($perubahan->isKolaboratif())->toBeTrue()
        ->and($perubahan->target_pengumpulan)->toBe(100_000_000)
        ->and($perubahan->kegiatanRencana()->aktif()->count())->toBe(3)
        ->and($perubahan->pelengkapBidang()->count())->toBe(1)
        ->and($perubahan->aturanAmil()->count())->toBe(24)
        ->and($perubahan->tahapBidang()->where('bidang_kode', 'pengumpulan')->value('status'))->toBe('draft')
        ->and($perubahan->tahapBidang()->where('bidang_kode', 'distribusi')->value('status'))->toBe('menunggu');
});

it('K17/RBAC: hanya Kabag bidang yang menulis kegiatannya; hanya Kabag PKP memverifikasi; grid PKP read-only; UPZ ditolak', function (): void {
    $perencanaan = buatUser(['perencanaan', 'amil']);
    $kabagPengumpulan = buatUser(['kabag_pengumpulan', 'amil']);
    $stafMuzakki = buatUser(['pelayanan_muzakki', 'amil']);
    $stafDist = buatUser(['staf_pendistribusian', 'amil']);
    $waka1 = buatUser(['waka_1', 'amil']);
    $upz = buatUser('upz');

    $this->actingAs($perencanaan);
    $versi = app(BuatVersiRkat::class)->jalankan([
        'tahun' => 2027,
        'jenis' => 'awal',
        'mode_penyusunan' => 'kolaboratif',
        'target_pengumpulan' => 50_000_000,
    ]);

    // Grid PKP 100% read-only untuk kolaboratif
    expect(fn () => app(SimpanNilaiRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'nilai' => [posByKode('L2/1.1')->id => 1_000_000],
    ]))->toThrow(ValidationException::class);

    // Staf, Waka, dan Perencanaan ditolak menulis kegiatan bidang
    foreach ([$stafMuzakki, $waka1, $perencanaan] as $user) {
        $this->actingAs($user)
            ->post(route('pengumpulan.rkat.jenis-kegiatan'), [])
            ->assertForbidden();
    }
    $this->actingAs($stafDist)
        ->post(route('distribusi.rkat.kegiatan'), [])
        ->assertForbidden();

    // UPZ eksternal ditolak mengakses RKAT SAU
    $this->actingAs($upz)
        ->get(route('umum.rkat.index'))
        ->assertForbidden();

    // Perencanaan tidak boleh memverifikasi/mengembalikan tahap bidang (khusus Kabag PKP & Admin)
    $this->actingAs($perencanaan)
        ->post(route('pkp.rencana.tahap.verifikasi', ['versi' => $versi->id, 'bidang' => 'pengumpulan']))
        ->assertForbidden();
    $this->actingAs($perencanaan)
        ->post(route('pkp.rencana.tahap.kembalikan', ['versi' => $versi->id, 'bidang' => 'pengumpulan']), ['alasan' => 'coba'])
        ->assertForbidden();

    expect(fn () => app(VerifikasiTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
    ]))->toThrow(AuthorizationException::class);

    // Waka 1 dapat melihat RKAT Pengumpulan (view-only)
    $this->actingAs($waka1)
        ->get(route('pengumpulan.rkat.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('dapat_kelola', false));

    // Kabag Pengumpulan tidak dapat menulis di bidang distribusi
    $this->actingAs($kabagPengumpulan)
        ->post(route('distribusi.rkat.jenis-kegiatan'), [])
        ->assertForbidden();
});

it('K17/BR-11: tanpa hard delete pada jenis kegiatan dan rincian kegiatan; seluruh aksi tercatat di audit_logs', function (): void {
    $perencanaan = buatUser(['perencanaan', 'amil']);
    $kabagPengumpulan = buatUser(['kabag_pengumpulan', 'amil']);

    $this->actingAs($perencanaan);
    $versi = app(BuatVersiRkat::class)->jalankan([
        'tahun' => 2027,
        'jenis' => 'awal',
        'mode_penyusunan' => 'kolaboratif',
        'target_pengumpulan' => 50_000_000,
    ]);

    $this->actingAs($kabagPengumpulan);
    $jk = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Zakat Mal Lembaga',
        'pos_anggaran_id' => posByKode('L2/1.1')->id,
        'satuan_default' => 'badan',
    ]);
    $baris = app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jk->id,
        'jumlah' => 5,
        'frekuensi' => 1,
        'besaran' => 10_000_000,
    ]);

    expect(fn () => $jk->delete())->toThrow(LogicException::class)
        ->and(fn () => $baris->delete())->toThrow(LogicException::class);

    // Arsip baris kegiatan menolkan nilai rkat_values tanpa menghapus baris RkatValue
    app(SimpanKegiatanRkat::class)->jalankan([
        'aksi' => 'arsip',
        'id' => $baris->id,
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'alasan' => 'Dibatalkan dalam rapat bidang',
    ]);

    expect($baris->fresh()->diarsipkan_pada)->not->toBeNull()
        ->and(RkatValue::where('rkat_version_id', $versi->id)->where('kode_asal', 'L2/1.1')->value('nilai'))->toBe(0)
        ->and(AuditLog::where('entitas_tipe', RkatJenisKegiatan::class)->where('entitas_id', $jk->id)->exists())->toBeTrue()
        ->and(AuditLog::where('entitas_tipe', RkatKegiatanRencana::class)->where('entitas_id', $baris->id)->pluck('aksi')->all())
        ->toBe(['tambah_kegiatan_rkat', 'arsip_kegiatan_rkat']);
});

it('K17: ekspor Excel dan PDF versi kolaboratif memuat konsolidasi rincian kegiatan 3 bidang', function (): void {
    $perencanaan = buatUser(['perencanaan', 'amil']);
    $kabagPengumpulan = buatUser(['kabag_pengumpulan', 'amil']);

    $this->actingAs($perencanaan);
    $versi = app(BuatVersiRkat::class)->jalankan([
        'tahun' => 2027,
        'jenis' => 'awal',
        'mode_penyusunan' => 'kolaboratif',
        'target_pengumpulan' => 25_000_000,
    ]);

    $this->actingAs($kabagPengumpulan);
    $jk = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Infak Terikat Dakwah',
        'pos_anggaran_id' => posByKode('L2/2.9')->id,
        'satuan_default' => 'orang',
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jk->id,
        'jumlah' => 250,
        'frekuensi' => 1,
        'besaran' => 100_000,
    ]);

    $this->actingAs($perencanaan);
    $excelResp = $this->get(route('pkp.rencana.ekspor.excel', $versi))->assertOk();
    $spreadsheet = IOFactory::load($excelResp->baseResponse->getFile()->getPathname());

    expect($spreadsheet->getSheetNames())->toContain('Kegiatan 3 Bidang');
    $rows = $spreadsheet->getSheetByName('Kegiatan 3 Bidang')->toArray(null, false, false, false);
    $teks = collect($rows)->flatten()->filter()->implode(' | ');
    expect($teks)->toContain('Infak Terikat Dakwah');

    $pdfResp = $this->get(route('pkp.rencana.ekspor.pdf', $versi))->assertOk();
    expect(substr($pdfResp->getContent(), 0, 4))->toBe('%PDF');
});

it('K17: pengajuan tahap bidang ditolak bila total belum persis sama dengan target atau pagu kelompok dana', function (): void {
    $perencanaan = buatUser(['perencanaan', 'amil']);
    $kabagPkp = buatUser(['kabag_pkp', 'amil']);
    $kabagPengumpulan = buatUser(['kabag_pengumpulan', 'amil']);
    $kabagPendis = buatUser(['kabag_pendis_pendag', 'amil']);
    $kabagSau = buatUser(['kabag_sau', 'amil']);

    $this->actingAs($perencanaan);
    $versi = app(BuatVersiRkat::class)->jalankan([
        'tahun' => 2027,
        'jenis' => 'awal',
        'mode_penyusunan' => 'kolaboratif',
        'target_pengumpulan' => 100_000_000,
    ]);

    $this->actingAs($kabagPengumpulan);
    $jk = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'pengumpulan',
        'nama' => 'Zakat Mal',
        'pos_anggaran_id' => posByKode('L2/1.2')->id,
        'satuan_default' => 'muzakki',
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jk->id,
        'jumlah' => 90,
        'frekuensi' => 1,
        'besaran' => 1_000_000,
    ]);

    expect(fn () => app(AjukanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
    ]))->toThrow(ValidationException::class);

    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
        'rkat_jenis_kegiatan_id' => $jk->id,
        'jumlah' => 10,
        'frekuensi' => 1,
        'besaran' => 1_000_000,
    ]);
    app(AjukanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
    ]);

    $this->actingAs($kabagPkp);
    app(VerifikasiTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'pengumpulan',
    ]);

    // Pendis kurang 1 rupiah dari pagu 87.500.000 -> ditolak
    $this->actingAs($kabagPendis);
    $jkDist = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'distribusi',
        'nama' => 'Santunan Fakir',
        'pos_anggaran_id' => posByKode('L3/1.1')->id,
        'pos_program_id' => posByKode('L4/1.1')->id,
        'satuan_default' => 'mustahik',
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
        'rkat_jenis_kegiatan_id' => $jkDist->id,
        'jumlah' => 1,
        'frekuensi' => 1,
        'besaran' => 87_499_999,
    ]);

    expect(fn () => app(AjukanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'distribusi',
    ]))->toThrow(ValidationException::class);

    // SAU melebihi pagu 12.500.000 -> ditolak
    $this->actingAs($kabagSau);
    $jkSau = app(SimpanJenisKegiatanRkat::class)->jalankan([
        'bidang_kode' => 'umum',
        'nama' => 'ATK Operasional',
        'pos_anggaran_id' => posByKode('L7/1.1.4')->id,
        'satuan_default' => 'paket',
    ]);
    app(SimpanKegiatanRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'umum',
        'rkat_jenis_kegiatan_id' => $jkSau->id,
        'jumlah' => 1,
        'frekuensi' => 1,
        'besaran' => 12_500_001,
    ]);

    expect(fn () => app(AjukanTahapBidangRkat::class)->jalankan([
        'rkat_version_id' => $versi->id,
        'bidang_kode' => 'umum',
    ]))->toThrow(ValidationException::class);
});
