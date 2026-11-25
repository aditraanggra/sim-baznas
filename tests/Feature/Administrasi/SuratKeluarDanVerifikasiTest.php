<?php

use App\Actions\Administrasi\AjukanSuratKeluar;
use App\Actions\Administrasi\BatalkanSuratKeluar;
use App\Actions\Administrasi\CetakSuratKeluar;
use App\Actions\Administrasi\SetujuiSuratKeluar;
use App\Actions\Administrasi\SimpanSuratKeluar;
use App\Actions\Administrasi\TolakRevisiSuratKeluar;
use App\Models\JenisSurat;
use App\Models\SuratKeluar;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Services\Pdf\DokumenPdfService;
use Database\Seeders\AdministrasiSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PeranSeeder::class, MasterOperasionalSeeder::class, AdministrasiSeeder::class]);
});

it('ADM-T03: surat keluar menegakkan maker-checker, penomoran global vs SK terpisah, dan penguncian draft', function () {
    $adminArsip = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $kabagSauPembuat = User::factory()->create()->assignRole(['kabag_sau', 'amil']);
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);

    $jenisUnd = JenisSurat::where('kode', 'UND')->firstOrFail();
    $tplUnd = TemplateSurat::where('jenis_surat_id', $jenisUnd->id)->firstOrFail();
    $jenisSk = JenisSurat::where('kode', 'SK')->firstOrFail();

    // Buat draft oleh Kabag SAU (yang juga punya peran penyetuju)
    $this->actingAs($kabagSauPembuat);
    $suratUnd = app(SimpanSuratKeluar::class)->jalankan([
        'jenis_surat_id' => $jenisUnd->id,
        'template_surat_id' => $tplUnd->id,
        'tanggal' => '2026-11-24',
        'sifat' => 'penting',
        'tujuan' => 'Seluruh Kepala Seksi BAZNAS Cianjur',
        'perihal' => 'Undangan Rapat Kerja',
        'isi' => 'Mohon kehadiran pada rapat koordinasi tahunan.',
        'nama_penandatangan' => 'H. Yosef Umar',
        'jabatan_penandatangan' => 'Ketua BAZNAS Kabupaten Cianjur',
    ]);

    expect($suratUnd->nomor)->toBeNull()
        ->and($suratUnd->status_kode)->toBe('draft');

    app(AjukanSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratUnd->id]);
    expect($suratUnd->fresh()->status_kode)->toBe('menunggu_persetujuan');

    // Maker-checker: Kabag SAU pembuat tidak boleh menyetujui suratnya sendiri
    expect(fn () => app(SetujuiSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratUnd->id]))
        ->toThrow(ValidationException::class);

    // Ketua menyetujui -> nomor global ke-1 terbit dan draft terkunci
    $this->actingAs($ketua);
    $disetujui = app(SetujuiSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratUnd->id]);

    expect($disetujui->status_kode)->toBe('disetujui')
        ->and($disetujui->nomor)->toContain('001/UND/');

    // Upaya edit setelah final ditolak di server
    $this->actingAs($adminArsip);
    expect(fn () => app(SimpanSuratKeluar::class)->jalankan([
        'id' => $disetujui->id,
        'jenis_surat_id' => $jenisUnd->id,
        'tanggal' => '2026-11-24',
        'sifat' => 'penting',
        'tujuan' => 'Ubah paksa',
        'perihal' => 'Ubah paksa',
        'isi' => 'Tidak boleh berubah',
        'nama_penandatangan' => 'H. Yosef Umar',
        'jabatan_penandatangan' => 'Ketua',
    ]))->toThrow(ValidationException::class);

    // Surat kedua dengan jenis SK (pisah_urutan = true) harus mulai dari 001 tersendiri
    $suratSk = app(SimpanSuratKeluar::class)->jalankan([
        'jenis_surat_id' => $jenisSk->id,
        'tanggal' => '2026-11-24',
        'sifat' => 'biasa',
        'tujuan' => 'Internal BAZNAS',
        'perihal' => 'Penetapan Tim Audit Internal',
        'isi' => 'Menetapkan tim audit internal tahun 2026.',
        'nama_penandatangan' => 'H. Yosef Umar',
        'jabatan_penandatangan' => 'Ketua BAZNAS Kabupaten Cianjur',
    ]);
    app(AjukanSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratSk->id]);

    $this->actingAs($ketua);
    $skFinal = app(SetujuiSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratSk->id]);
    expect($skFinal->nomor)->toStartWith('SK-001/');
});

it('ADM-T03: revisi kembali ke draft, pembatalan tanpa hard delete, PDF kop serif watermark/cetak ulang, dan verifikasi QR publik', function () {
    $adminArsip = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);
    $jenisBiasa = JenisSurat::where('kode', 'BIASA')->firstOrFail();

    $this->actingAs($adminArsip);
    $surat = app(SimpanSuratKeluar::class)->jalankan([
        'jenis_surat_id' => $jenisBiasa->id,
        'tanggal' => '2026-11-24',
        'sifat' => 'rahasia',
        'tujuan' => 'Kepala Bagian Keuangan',
        'perihal' => 'Evaluasi Kepatuhan Internal',
        'isi' => 'Isi rahasia evaluasi kepatuhan.',
        'nama_penandatangan' => 'H. Yosef Umar',
        'jabatan_penandatangan' => 'Ketua BAZNAS Kabupaten Cianjur',
    ]);

    // PDF saat draft memuat watermark DRAFT dan QR verifikasi belum aktif di halaman publik
    $htmlDraft = app(DokumenPdfService::class)->html('pdf.surat-keluar', [
        'judul' => 'Surat Dinas Umum',
        'nomor' => $surat->nomor,
        'surat' => $surat->load('jenisSurat'),
    ], [
        'kertas' => 'a4',
        'tipografi' => 'serif',
        'draft' => true,
    ]);
    expect($htmlDraft)->toContain('DRAFT')
        ->and($htmlDraft)->toContain('KABUPATEN CIANJUR');

    $this->get(route('publik.verifikasi-surat', $surat->uuid))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/verifikasi-surat')
            ->where('ditemukan', false));

    // Ajukan lalu kembalikan revisi oleh Ketua
    app(AjukanSuratKeluar::class)->jalankan(['surat_keluar_id' => $surat->id]);
    $this->actingAs($ketua);
    app(TolakRevisiSuratKeluar::class)->jalankan([
        'surat_keluar_id' => $surat->id,
        'catatan_revisi' => 'Perbaiki redaksi paragraf pertama.',
    ]);
    expect($surat->fresh()->status_kode)->toBe('draft')
        ->and($surat->fresh()->catatan_revisi)->toBe('Perbaiki redaksi paragraf pertama.');

    // Ajukan ulang & setujui
    $this->actingAs($adminArsip);
    app(AjukanSuratKeluar::class)->jalankan(['surat_keluar_id' => $surat->id]);
    $this->actingAs($ketua);
    $suratFinal = app(SetujuiSuratKeluar::class)->jalankan(['surat_keluar_id' => $surat->id]);

    // Cetakan ke-1 tanpa badge CETAK ULANG, cetakan ke-2 mendapat cetak_ulang_ke = 1
    $cetak1 = app(CetakSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratFinal->id]);
    $cetak2 = app(CetakSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratFinal->id]);
    expect($cetak1['cetak_ulang_ke'])->toBeNull()
        ->and($cetak2['cetak_ulang_ke'])->toBe(1);

    // Halaman verifikasi publik menyamarkan perihal & tujuan surat rahasia dan tidak pernah menampilkan isi surat
    $this->get(route('publik.verifikasi-surat', $suratFinal->uuid))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/verifikasi-surat')
            ->where('ditemukan', true)
            ->where('data.nomor', $suratFinal->nomor)
            ->where('data.status', 'disetujui')
            ->where('data.perihal', 'Dokumen Rahasia (Perihal Disamarkan)'));

    // Batalkan surat tanpa hard delete
    app(BatalkanSuratKeluar::class)->jalankan([
        'surat_keluar_id' => $suratFinal->id,
        'alasan_batal' => 'Terdapat perubahan jadwal kegiatan.',
    ]);
    expect(SuratKeluar::find($suratFinal->id))->not->toBeNull()
        ->and($suratFinal->fresh()->status_kode)->toBe('dibatalkan');
});
