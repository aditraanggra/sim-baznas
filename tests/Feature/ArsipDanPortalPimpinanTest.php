<?php

use App\Actions\Arsip\CabutDokumenArsip;
use App\Actions\Arsip\SahkanDokumenArsip;
use App\Actions\Arsip\SimpanDokumenArsip;
use App\Actions\Distribusi\CairkanPenyaluran;
use App\Actions\Distribusi\SetujuiPenyaluran;
use App\Actions\Distribusi\SimpanMustahik;
use App\Actions\Distribusi\SimpanPenyaluran;
use App\Actions\Pengaturan\BuatTautanAkses;
use App\Actions\Pengaturan\CabutTautanAkses;
use App\Actions\Pengaturan\PutarTautanAkses;
use App\Actions\Pengumpulan\FinalkanPenerimaan;
use App\Actions\Pengumpulan\SimpanDraftPenerimaan;
use App\Actions\Pengumpulan\SimpanMuzakki;
use App\Models\AksesDokumenLog;
use App\Models\Asnaf;
use App\Models\AuditLog;
use App\Models\BentukBantuan;
use App\Models\DokumenArsip;
use App\Models\KategoriDokumen;
use App\Models\MetodeBayar;
use App\Models\Program;
use App\Models\SumberDana;
use App\Models\TautanAkses;
use App\Models\User;
use Database\Seeders\BidangSeeder;
use Database\Seeders\KategoriDokumenSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\PerencanaanJembatanSeeder;
use Database\Seeders\PosAnggaranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        KategoriDokumenSeeder::class,
    ]);
});

function buatPdfPalsu(string $nama = 'dokumen-uji.pdf'): UploadedFile
{
    $kontenPdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";

    return UploadedFile::fake()->createWithContent($nama, $kontenPdf);
}

it('Beranda layanan publik /publik terbuka untuk tamu, terpisah dari portal pimpinan, dan rute / tetap redirect ke login', function () {
    $this->get(route('home'))->assertRedirect(route('login'));

    $this->get(route('publik.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/index')
            ->has('lembaga'));
});

it('ARS-T01: RBAC arsip PKP, penguncian setelah disahkan, revisi versi baru, pencabutan wajib alasan, dan validasi PDF', function () {
    Storage::fake('private');

    $nonPkp = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $perencanaan = User::factory()->create()->assignRole(['perencanaan', 'amil']);
    $kabagPkp = User::factory()->create()->assignRole(['kabag_pkp', 'amil']);
    $kategori = KategoriDokumen::where('kode', 'LAP_KINERJA')->firstOrFail();

    // Non-PKP ditolak di rute & Action.
    $this->actingAs($nonPkp)->get(route('pkp.arsip.index'))->assertForbidden();
    expect(fn () => app(SimpanDokumenArsip::class)->jalankan([
        'kategori_dokumen_id' => $kategori->id,
        'tahun' => 2026,
        'judul' => 'Tidak Boleh',
        'berkas' => buatPdfPalsu(),
    ]))->toThrow(AuthorizationException::class);

    // File non-PDF ditolak.
    $this->actingAs($perencanaan);
    expect(fn () => app(SimpanDokumenArsip::class)->jalankan([
        'kategori_dokumen_id' => $kategori->id,
        'tahun' => 2026,
        'judul' => 'Berkas Gambar',
        'berkas' => UploadedFile::fake()->image('bukan-pdf.jpg'),
    ]))->toThrow(ValidationException::class);

    // Staf perencanaan mengunggah draft v1 dan dapat mengubahnya selama draft.
    $dokV1 = app(SimpanDokumenArsip::class)->jalankan([
        'kategori_dokumen_id' => $kategori->id,
        'tahun' => 2026,
        'nomor' => '001/LAP/BAZNAS-CJR/2026',
        'judul' => 'Laporan Kinerja Pengelolaan ZIS 2026',
        'ringkasan' => 'Ringkasan draft awal',
        'berkas' => buatPdfPalsu('lkp-2026-v1.pdf'),
    ]);

    expect($dokV1->status)->toBe(DokumenArsip::STATUS_DRAFT)
        ->and($dokV1->versi)->toBe(1);

    app(SimpanDokumenArsip::class)->jalankan([
        'id' => $dokV1->id,
        'kategori_dokumen_id' => $kategori->id,
        'tahun' => 2026,
        'nomor' => '001/LAP/BAZNAS-CJR/2026',
        'judul' => 'Laporan Kinerja Pengelolaan ZIS 2026 (Final)',
        'ringkasan' => 'Ringkasan diperbarui',
    ]);

    expect($dokV1->fresh()->judul)->toBe('Laporan Kinerja Pengelolaan ZIS 2026 (Final)');

    // Staf perencanaan tidak boleh mengesahkan atau mencabut dokumen.
    expect(fn () => app(SahkanDokumenArsip::class)->jalankan(['id' => $dokV1->id]))
        ->toThrow(AuthorizationException::class);
    expect(fn () => app(CabutDokumenArsip::class)->jalankan(['id' => $dokV1->id, 'alasan_cabut' => 'Alasan palsu']))
        ->toThrow(AuthorizationException::class);

    // Kabag PKP mengesahkan v1.
    $this->actingAs($kabagPkp);
    $dokV1 = app(SahkanDokumenArsip::class)->jalankan(['id' => $dokV1->id]);

    expect($dokV1->status)->toBe(DokumenArsip::STATUS_DISAHKAN)
        ->and($dokV1->tanggal_pengesahan)->not->toBeNull()
        ->and(AuditLog::where('entitas_tipe', DokumenArsip::class)->where('entitas_id', $dokV1->id)->where('aksi', 'sahkan')->exists())->toBeTrue();

    // Setelah disahkan, edit metadata langsung ditolak (terkunci).
    $this->actingAs($perencanaan);
    expect(fn () => app(SimpanDokumenArsip::class)->jalankan([
        'id' => $dokV1->id,
        'kategori_dokumen_id' => $kategori->id,
        'tahun' => 2026,
        'judul' => 'Coba Edit Setelah Sah',
    ]))->toThrow(ValidationException::class);

    // Buat versi revisi (v2) dari dokumen v1.
    $dokV2 = app(SimpanDokumenArsip::class)->jalankan([
        'induk_versi_id' => $dokV1->id,
        'kategori_dokumen_id' => $kategori->id,
        'tahun' => 2026,
        'nomor' => '001-REV/LAP/BAZNAS-CJR/2026',
        'judul' => 'Laporan Kinerja Pengelolaan ZIS 2026',
        'berkas' => buatPdfPalsu('lkp-2026-v2.pdf'),
    ]);

    expect($dokV2->versi)->toBe(2)
        ->and($dokV2->induk_versi_id)->toBe($dokV1->id)
        ->and($dokV2->status)->toBe(DokumenArsip::STATUS_DRAFT);

    // Saat v2 disahkan oleh Kabag PKP, v1 otomatis berstatus dicabut (tanpa hard delete).
    $this->actingAs($kabagPkp);
    $dokV2 = app(SahkanDokumenArsip::class)->jalankan(['id' => $dokV2->id]);

    expect($dokV2->status)->toBe(DokumenArsip::STATUS_DISAHKAN)
        ->and($dokV1->fresh()->status)->toBe(DokumenArsip::STATUS_DICABUT)
        ->and($dokV1->fresh()->alasan_cabut)->toContain('v2');

    // Pencabutan manual wajib alasan >= 5 karakter.
    expect(fn () => app(CabutDokumenArsip::class)->jalankan(['id' => $dokV2->id, 'alasan_cabut' => 'abc']))
        ->toThrow(ValidationException::class);

    $dokV2 = app(CabutDokumenArsip::class)->jalankan([
        'id' => $dokV2->id,
        'alasan_cabut' => 'Koreksi angka audit akhir tahun',
    ]);
    expect($dokV2->status)->toBe(DokumenArsip::STATUS_DICABUT)
        ->and(DokumenArsip::count())->toBe(2);
});

it('ARS-T02: Tautan khusus bertoken hash SHA-256, hanya Admin, header noindex, cabut, kedaluwarsa, putar token, dan batas cakupan', function () {
    Storage::fake('private');

    $admin = User::factory()->create()->assignRole(['admin', 'amil']);
    $kabag = User::factory()->create()->assignRole(['kabag_pkp', 'amil']);
    $kategori = KategoriDokumen::where('kode', 'RKAT')->firstOrFail();

    // Non-admin ditolak akses pengaturan tautan akses.
    $this->actingAs($kabag)->get(route('pengaturan.tautan-akses'))->assertForbidden();
    expect(fn () => app(BuatTautanAkses::class)->jalankan([
        'label' => 'Ilegal',
        'jenis' => TautanAkses::JENIS_PORTAL_PIMPINAN,
    ]))->toThrow(AuthorizationException::class);

    // Siapkan 1 dokumen arsip disahkan.
    $this->actingAs($kabag);
    $dokSah = app(SimpanDokumenArsip::class)->jalankan([
        'kategori_dokumen_id' => $kategori->id,
        'tahun' => 2026,
        'judul' => 'RKAT 2026 Resmi',
        'berkas' => buatPdfPalsu('rkat-2026.pdf'),
        'sahkan_langsung' => true,
    ]);

    // Admin menerbitkan tautan jenis portal_pimpinan.
    $this->actingAs($admin);
    $hasil = app(BuatTautanAkses::class)->jalankan([
        'label' => 'Portal Pimpinan 2026',
        'jenis' => TautanAkses::JENIS_PORTAL_PIMPINAN,
    ]);
    $tautan = $hasil['tautan'];
    $tokenMentah = $hasil['token_mentah'];

    // Token mentah tidak disimpan di DB maupun di audit_logs.
    expect($tautan->token_hash)->toBe(hash('sha256', $tokenMentah))
        ->and($tautan->token_hash)->not->toBe($tokenMentah)
        ->and(json_encode(AuditLog::latest('id')->first()->nilai_baru))->not->toContain($tokenMentah);

    // Tamu membuka portal dengan token valid (tanpa login) -> 200 + header noindex.
    auth()->logout();
    $resp = $this->get(route('akses.portal', ['token' => $tokenMentah]));
    $resp->assertOk();
    expect($resp->headers->get('X-Robots-Tag'))->toContain('noindex');
    expect($tautan->fresh()->jumlah_akses)->toBe(1);

    // Token palsu -> 404.
    $this->get(route('akses.portal', ['token' => 'token-palsu-tidak-terdaftar']))->assertNotFound();

    // Uji pembatasan jenis: tautan 'arsip' tidak boleh akses kinerja PDF; tautan 'laporan_kinerja' tidak boleh akses arsip berkas.
    $this->actingAs($admin);
    $hanyaArsip = app(BuatTautanAkses::class)->jalankan([
        'label' => 'Khusus Arsip',
        'jenis' => TautanAkses::JENIS_ARSIP,
    ]);
    $hanyaKinerja = app(BuatTautanAkses::class)->jalankan([
        'label' => 'Khusus Kinerja',
        'jenis' => TautanAkses::JENIS_LAPORAN_KINERJA,
    ]);

    auth()->logout();
    $this->get(route('akses.kinerja.pdf', ['token' => $hanyaArsip['token_mentah']]))->assertForbidden();
    $this->get(route('akses.arsip.berkas', ['token' => $hanyaKinerja['token_mentah'], 'uuid' => $dokSah->uuid]))->assertForbidden();

    // Putar token: token lama mati (410), token baru hidup (200).
    $this->actingAs($admin);
    $hasilPutar = app(PutarTautanAkses::class)->jalankan(['id' => $tautan->id]);

    auth()->logout();
    $this->get(route('akses.portal', ['token' => $tokenMentah]))->assertStatus(410);
    $this->get(route('akses.portal', ['token' => $hasilPutar['token_mentah']]))->assertOk();

    // Cabut token: langsung 410.
    $this->actingAs($admin);
    app(CabutTautanAkses::class)->jalankan(['id' => $hasilPutar['tautan']->id]);
    auth()->logout();
    $this->get(route('akses.portal', ['token' => $hasilPutar['token_mentah']]))->assertStatus(410);

    // Token kedaluwarsa: 410.
    $hasilPutar['tautan']->update([
        'dicabut_at' => null,
        'kadaluarsa_at' => now()->subMinute(),
    ]);
    $this->get(route('akses.portal', ['token' => $hasilPutar['token_mentah']]))->assertStatus(410);
});

it('ARS-T03: Portal Arsip PPID hanya menampilkan dokumen disahkan, mendukung filter, preview/cetak/unduh PDF, dan mencatat akses_dokumen_log', function () {
    Storage::fake('private');

    $admin = User::factory()->create()->assignRole(['admin', 'amil']);
    $kabag = User::factory()->create()->assignRole(['kabag_pkp', 'amil']);
    $katRenstra = KategoriDokumen::where('kode', 'RENSTRA')->firstOrFail();
    $katRkat = KategoriDokumen::where('kode', 'RKAT')->firstOrFail();

    $this->actingAs($kabag);
    $dokDraft = app(SimpanDokumenArsip::class)->jalankan([
        'kategori_dokumen_id' => $katRenstra->id,
        'tahun' => 2026,
        'judul' => 'RENSTRA Masih Draft',
        'berkas' => buatPdfPalsu('renstra-draft.pdf'),
        'sahkan_langsung' => false,
    ]);
    $dokSah2025 = app(SimpanDokumenArsip::class)->jalankan([
        'kategori_dokumen_id' => $katRkat->id,
        'tahun' => 2025,
        'judul' => 'RKAT Perubahan 2025',
        'berkas' => buatPdfPalsu('rkat-2025.pdf'),
        'sahkan_langsung' => true,
    ]);
    $dokSah2026 = app(SimpanDokumenArsip::class)->jalankan([
        'kategori_dokumen_id' => $katRenstra->id,
        'tahun' => 2026,
        'judul' => 'RENSTRA BAZNAS Cianjur 2026-2030',
        'berkas' => buatPdfPalsu('renstra-2026.pdf'),
        'sahkan_langsung' => true,
    ]);

    $this->actingAs($admin);
    $tautan = app(BuatTautanAkses::class)->jalankan([
        'label' => 'Arsip Pimpinan',
        'jenis' => TautanAkses::JENIS_ARSIP,
    ]);
    $token = $tautan['token_mentah'];

    auth()->logout();

    // Tanpa filter: hanya 2 dokumen disahkan yang muncul (draft tidak muncul).
    $this->get(route('akses.portal', ['token' => $token]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/portal-pimpinan')
            ->has('arsip.dokumen', 2));

    // Filter kategori & tahun menyaring ke 1 dokumen.
    $this->get(route('akses.portal', [
        'token' => $token,
        'kategori_id' => $katRenstra->id,
        'tahun_arsip' => 2026,
        'q' => 'Cianjur',
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('arsip.dokumen', 1)
            ->where('arsip.dokumen.0.judul', 'RENSTRA BAZNAS Cianjur 2026-2030'));

    // Dokumen draft tidak dapat diunduh lewat UUID di portal khusus.
    $this->get(route('akses.arsip.berkas', ['token' => $token, 'uuid' => $dokDraft->uuid, 'aksi' => 'lihat']))
        ->assertNotFound();

    // Akses lihat (inline) dan unduh mencatat akses_dokumen_log.
    $respLihat = $this->get(route('akses.arsip.berkas', ['token' => $token, 'uuid' => $dokSah2026->uuid, 'aksi' => 'lihat']));
    $respLihat->assertOk();
    expect($respLihat->headers->get('content-type'))->toContain('application/pdf')
        ->and($respLihat->headers->get('content-disposition'))->toContain('inline');

    $respUnduh = $this->get(route('akses.arsip.berkas', ['token' => $token, 'uuid' => $dokSah2025->uuid, 'aksi' => 'unduh']));
    $respUnduh->assertOk();

    expect(AksesDokumenLog::where('dokumen_arsip_id', $dokSah2026->id)->where('aksi', 'lihat')->where('tautan_akses_id', $tautan['tautan']->id)->exists())->toBeTrue()
        ->and(AksesDokumenLog::where('dokumen_arsip_id', $dokSah2025->id)->where('aksi', 'unduh')->where('tautan_akses_id', $tautan['tautan']->id)->exists())->toBeTrue();
});

it('LKP-T01: Portal Laporan Kinerja Pimpinan menyajikan agregat ZIS akurat per tahun, tanpa data pribadi, dan menghasilkan PDF', function () {
    $admin = User::factory()->create()->assignRole(['admin', 'amil']);
    $pengumpul = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $stafDist = User::factory()->create()->assignRole(['staf_pendistribusian', 'amil']);
    $kabagDist = User::factory()->create()->assignRole(['kabag_pendis_pendag', 'amil']);

    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zm = SumberDana::where('kode', 'ZM')->firstOrFail();
    $ift = SumberDana::where('kode', 'IFT')->firstOrFail();
    $fakir = Asnaf::where('kode', 'FAKIR')->firstOrFail();
    $uang = BentukBantuan::where('kode', 'UANG')->firstOrFail();
    $program = Program::where('sub_bidang', 'pendistribusian')->firstOrFail();

    // Buat 1 penerimaan final (Rp 2.500.000) dan 1 draft (Rp 999.000 - tidak boleh terhitung).
    $this->actingAs($pengumpul);
    $muzakki = app(SimpanMuzakki::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'H. Rahasia Muzakki',
        'telepon' => '081200001111',
    ]);
    $penerimaanFinal = app(SimpanDraftPenerimaan::class)->jalankan([
        'tanggal' => '2026-03-15',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [
            ['sumber_dana_id' => $zm->id, 'jumlah' => 2_000_000],
            ['sumber_dana_id' => $ift->id, 'jumlah' => 500_000],
        ],
    ]);
    app(FinalkanPenerimaan::class)->jalankan(['id' => $penerimaanFinal->id]);

    app(SimpanDraftPenerimaan::class)->jalankan([
        'tanggal' => '2026-03-20',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 999_000]],
    ]);

    // Buat 1 penyaluran dicairkan (Rp 1.000.000).
    $this->actingAs($stafDist);
    $mustahik = app(SimpanMustahik::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Siti Rahasia Mustahik',
        'telepon' => '081300002222',
    ]);
    $penyaluran = app(SimpanPenyaluran::class)->jalankan([
        'tanggal' => '2026-04-10',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [[
            'sumber_dana_id' => $zm->id,
            'asnaf_id' => $fakir->id,
            'bentuk_bantuan_id' => $uang->id,
            'jumlah' => 1_000_000,
        ]],
    ]);

    $this->actingAs($kabagDist);
    $penyaluran = app(SetujuiPenyaluran::class)->jalankan(['id' => $penyaluran->id]);
    app(CairkanPenyaluran::class)->jalankan(['id' => $penyaluran->id]);

    // Terbitkan tautan khusus laporan kinerja.
    $this->actingAs($admin);
    $tautan = app(BuatTautanAkses::class)->jalankan([
        'label' => 'Laporan Kinerja 2026',
        'jenis' => TautanAkses::JENIS_LAPORAN_KINERJA,
    ]);
    $token = $tautan['token_mentah'];

    auth()->logout();
    $resp = $this->get(route('akses.portal', ['token' => $token, 'tahun_kinerja' => 2026]));
    $resp->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/portal-pimpinan')
            ->where('kinerja.tahun_terpilih', 2026)
            ->where('kinerja.kinerja_pengumpulan.total_penerimaan', 2_500_000)
            ->where('kinerja.kinerja_distribusi.total_penyaluran', 1_000_000)
            ->where('kinerja.kinerja_distribusi.rasio_penyaluran_label', '40,0%')
            ->has('kinerja.rekap_bulanan', 12)
            ->where('kinerja.rekap_bulanan.2.penerimaan', 2_500_000)
            ->where('kinerja.rekap_bulanan.3.penyaluran', 1_000_000));

    // Pastikan nama pribadi muzakki & mustahik tidak pernah bocor ke respons portal kinerja.
    $body = $resp->getContent();
    expect($body)->not->toContain('H. Rahasia Muzakki')
        ->and($body)->not->toContain('Siti Rahasia Mustahik')
        ->and($body)->not->toContain('081200001111')
        ->and($body)->not->toContain('081300002222');

    // Unduh PDF ringkasan kinerja via tautan khusus dan via rute internal PKP.
    $pdfTamu = $this->get(route('akses.kinerja.pdf', ['token' => $token, 'tahun' => 2026]));
    $pdfTamu->assertOk();
    expect($pdfTamu->headers->get('content-type'))->toContain('application/pdf')
        ->and($pdfTamu->headers->get('X-Robots-Tag'))->toContain('noindex');

    expect(AksesDokumenLog::where('tautan_akses_id', $tautan['tautan']->id)->where('konteks', 'kinerja_pdf')->exists())->toBeTrue();

    $this->actingAs($kabagDist);
    $this->get(route('pkp.kinerja.pdf', ['tahun' => 2026]))->assertForbidden();

    $perencanaan = User::factory()->create()->assignRole(['perencanaan', 'amil']);
    $this->actingAs($perencanaan);
    $pdfInternal = $this->get(route('pkp.kinerja.pdf', ['tahun' => 2026]));
    $pdfInternal->assertOk();
    expect($pdfInternal->headers->get('content-type'))->toContain('application/pdf');
});
