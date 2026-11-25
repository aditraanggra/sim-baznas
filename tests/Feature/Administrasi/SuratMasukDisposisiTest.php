<?php

use App\Actions\Administrasi\CatatSuratMasuk;
use App\Actions\Administrasi\DisposisiSuratMasuk;
use App\Actions\Administrasi\SelesaikanDisposisiSurat;
use App\Actions\Administrasi\TeruskanSuratMasukKeKetua;
use App\Actions\Administrasi\TutupSuratMasuk;
use App\Models\AuditLog;
use App\Models\DisposisiSurat;
use App\Models\Notifikasi;
use App\Models\SuratMasuk;
use App\Models\User;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    $this->seed([PeranSeeder::class, MasterOperasionalSeeder::class]);
});

it('ADM-T02: alur surat masuk K9 multi-Waka otomatis selesai hanya setelah semua cabang disposisi selesai', function () {
    $adminArsip = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);
    $waka1 = User::factory()->create()->assignRole(['waka_1', 'amil']);
    $waka2 = User::factory()->create()->assignRole(['waka_2', 'amil']);
    $stafPengumpulan = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $stafDistribusi = User::factory()->create()->assignRole(['staf_pendistribusian', 'amil']);

    $this->actingAs($adminArsip);
    $surat = app(CatatSuratMasuk::class)->jalankan([
        'nomor_surat' => '451/012/PEMDA/2026',
        'tanggal_surat' => '2026-11-24',
        'tanggal_terima' => '2026-11-24',
        'pengirim' => 'Sekretariat Daerah Kab. Cianjur',
        'perihal' => 'Koordinasi Penyaluran Bantuan Bencana',
        'sifat' => 'penting',
        'langsung_teruskan_ketua' => false,
        'berkas' => UploadedFile::fake()->create('surat-masuk.pdf', 120, 'application/pdf'),
    ]);

    expect($surat->nomor_agenda)->toStartWith('AGD/')
        ->and($surat->status_kode)->toBe('diterima_arsip')
        ->and($surat->berkas_privat_id)->not->toBeNull();

    app(TeruskanSuratMasukKeKetua::class)->jalankan([
        'surat_masuk_id' => $surat->id,
        'catatan' => 'Mohon arahan Ketua.',
    ]);
    expect($surat->fresh()->status_kode)->toBe('diteruskan_ketua');
    expect(Notifikasi::where('user_id', $ketua->id)->where('ref_id', $surat->id)->exists())->toBeTrue();

    // Ketua mendisposisikan ke 2 Wakil Ketua sekaligus
    $this->actingAs($ketua);
    app(DisposisiSuratMasuk::class)->jalankan([
        'surat_masuk_id' => $surat->id,
        'kepada_user_ids' => [$waka1->id, $waka2->id],
        'instruksi' => 'Waka I koordinasi donasi, Waka II siapkan tim distribusi.',
        'tenggat' => '2026-11-27',
    ]);

    $surat->refresh();
    expect($surat->status_kode)->toBe('disposisi_waka')
        ->and($surat->disposisi()->count())->toBe(2)
        ->and(Notifikasi::where('user_id', $waka1->id)->where('ref_id', $surat->id)->exists())->toBeTrue()
        ->and(Notifikasi::where('user_id', $waka2->id)->where('ref_id', $surat->id)->exists())->toBeTrue();

    $dispWaka1 = DisposisiSurat::where('surat_masuk_id', $surat->id)->where('kepada_user_id', $waka1->id)->firstOrFail();
    $dispWaka2 = DisposisiSurat::where('surat_masuk_id', $surat->id)->where('kepada_user_id', $waka2->id)->firstOrFail();

    // Waka 1 meneruskan ke Staf Pengumpulan
    $this->actingAs($waka1);
    app(DisposisiSuratMasuk::class)->jalankan([
        'surat_masuk_id' => $surat->id,
        'parent_disposisi_id' => $dispWaka1->id,
        'kepada_user_ids' => [$stafPengumpulan->id],
        'instruksi' => 'Buka posko penerimaan donasi bencana.',
    ]);
    expect($dispWaka1->fresh()->status)->toBe('diteruskan')
        ->and($surat->fresh()->status_kode)->toBe('tindak_lanjut');

    // Waka 2 meneruskan ke Staf Distribusi
    $this->actingAs($waka2);
    app(DisposisiSuratMasuk::class)->jalankan([
        'surat_masuk_id' => $surat->id,
        'parent_disposisi_id' => $dispWaka2->id,
        'kepada_user_ids' => [$stafDistribusi->id],
        'instruksi' => 'Siapkan paket logistik darurat.',
    ]);

    $dispStaf1 = DisposisiSurat::where('surat_masuk_id', $surat->id)->where('kepada_user_id', $stafPengumpulan->id)->firstOrFail();
    $dispStaf2 = DisposisiSurat::where('surat_masuk_id', $surat->id)->where('kepada_user_id', $stafDistribusi->id)->firstOrFail();

    // Staf Pengumpulan selesai terlebih dahulu -> surat masih tindak_lanjut karena cabang Staf Distribusi masih menunggu
    $this->actingAs($stafPengumpulan);
    app(SelesaikanDisposisiSurat::class)->jalankan([
        'disposisi_id' => $dispStaf1->id,
        'catatan_tindak_lanjut' => 'Posko penerimaan donasi telah dibuka.',
    ]);
    expect($surat->fresh()->status_kode)->toBe('tindak_lanjut');

    // Staf Distribusi selesai -> seluruh cabang daun selesai -> surat otomatis menjadi selesai
    $this->actingAs($stafDistribusi);
    app(SelesaikanDisposisiSurat::class)->jalankan([
        'disposisi_id' => $dispStaf2->id,
        'catatan_tindak_lanjut' => 'Paket logistik telah disiapkan.',
        'berkas_bukti' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
    ]);

    expect($surat->fresh()->status_kode)->toBe('selesai')
        ->and($surat->fresh()->selesai_at)->not->toBeNull()
        ->and(AuditLog::where('entitas_tipe', SuratMasuk::class)->where('entitas_id', $surat->id)->count())->toBeGreaterThanOrEqual(4);

    // Lembar disposisi PDF dapat dirender
    $pdfResp = $this->actingAs($adminArsip)->get(route('umum.surat.masuk.lembar-disposisi-pdf', $surat->id));
    $pdfResp->assertOk();
    expect($pdfResp->headers->get('content-type'))->toContain('application/pdf');
});

it('ADM-T02: Ketua dapat menutup langsung surat masuk dan visibilitas ketat berlaku untuk rahasia, staf lain, serta UPZ', function () {
    $adminArsip = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);
    $waka1 = User::factory()->create()->assignRole(['waka_1', 'amil']);
    $waka3 = User::factory()->create()->assignRole(['waka_3', 'amil']);
    $stafLain = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $upz = User::factory()->create()->assignRole('upz');

    $this->actingAs($adminArsip);
    $suratBiasa = app(CatatSuratMasuk::class)->jalankan([
        'nomor_surat' => '001/UND/2026',
        'tanggal_surat' => '2026-11-24',
        'tanggal_terima' => '2026-11-24',
        'pengirim' => 'Kemenag Cianjur',
        'perihal' => 'Undangan Rakor Biasa',
        'sifat' => 'biasa',
        'langsung_teruskan_ketua' => true,
    ]);

    $suratRahasia = app(CatatSuratMasuk::class)->jalankan([
        'nomor_surat' => '002/RHS/2026',
        'tanggal_surat' => '2026-11-24',
        'tanggal_terima' => '2026-11-24',
        'pengirim' => 'Inspektorat',
        'perihal' => 'Klarifikasi Audit Khusus',
        'sifat' => 'rahasia',
        'langsung_teruskan_ketua' => true,
    ]);

    // Ketua disposisi surat rahasia hanya ke Waka 1
    $this->actingAs($ketua);
    app(DisposisiSuratMasuk::class)->jalankan([
        'surat_masuk_id' => $suratRahasia->id,
        'kepada_user_ids' => [$waka1->id],
        'instruksi' => 'Tindak lanjuti secara rahasia.',
    ]);

    // Ketua tutup langsung surat biasa ("Arsipkan / Cukup diketahui")
    app(TutupSuratMasuk::class)->jalankan([
        'surat_masuk_id' => $suratBiasa->id,
        'catatan_penutupan' => 'Cukup diketahui dan diarsipkan.',
    ]);
    expect($suratBiasa->fresh()->status_kode)->toBe('selesai');

    // UPZ diblokir 403 dari modul surat masuk
    $this->actingAs($upz)->get(route('umum.surat.masuk.index'))->assertForbidden();
    $this->actingAs($upz)->get(route('umum.surat.masuk.show', $suratBiasa->id))->assertForbidden();

    // Staf bidang lain tanpa disposisi tidak boleh melihat detail surat biasa maupun rahasia
    $this->actingAs($stafLain)->get(route('umum.surat.masuk.show', $suratBiasa->id))->assertForbidden();
    $this->actingAs($stafLain)->get(route('umum.surat.masuk.show', $suratRahasia->id))->assertForbidden();

    // Waka 3 bisa melihat surat biasa, tapi TIDAK bisa melihat surat rahasia yang tidak didisposisikan kepadanya
    $this->actingAs($waka3)->get(route('umum.surat.masuk.show', $suratBiasa->id))->assertOk();
    $this->actingAs($waka3)->get(route('umum.surat.masuk.show', $suratRahasia->id))->assertForbidden();

    // Waka 1 penerima disposisi bisa melihat surat rahasia
    $this->actingAs($waka1)->get(route('umum.surat.masuk.show', $suratRahasia->id))->assertOk();
});
