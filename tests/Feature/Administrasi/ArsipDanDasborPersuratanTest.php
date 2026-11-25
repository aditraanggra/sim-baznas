<?php

use App\Actions\Administrasi\AjukanSuratKeluar;
use App\Actions\Administrasi\CatatSuratMasuk;
use App\Actions\Administrasi\SetujuiSuratKeluar;
use App\Actions\Administrasi\SimpanSuratKeluar;
use App\Models\JenisSurat;
use App\Models\User;
use Database\Seeders\AdministrasiSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PeranSeeder::class, MasterOperasionalSeeder::class, AdministrasiSeeder::class]);
});

it('ADM-T04: arsip full-text PostgreSQL, ekspor Buku Agenda Excel & PDF, serta Ctrl+K mengikuti cakupan peran', function () {
    $adminArsip = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);
    $stafLain = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $upz = User::factory()->create()->assignRole('upz');

    $this->actingAs($adminArsip);
    $suratMasuk = app(CatatSuratMasuk::class)->jalankan([
        'nomor_surat' => '900/BAPPEDA/2026',
        'tanggal_surat' => '2026-11-24',
        'tanggal_terima' => '2026-11-24',
        'pengirim' => 'BAPPEDA Kabupaten Cianjur',
        'perihal' => 'Undangan Musrenbang Pengentasan Kemiskinan',
        'ringkasan' => 'Pembahasan sinergi program bantuan rumah tidak layak huni.',
        'sifat' => 'biasa',
        'langsung_teruskan_ketua' => false,
    ]);

    $jenisUnd = JenisSurat::where('kode', 'UND')->firstOrFail();
    $suratKeluar = app(SimpanSuratKeluar::class)->jalankan([
        'jenis_surat_id' => $jenisUnd->id,
        'surat_masuk_id' => $suratMasuk->id,
        'tanggal' => '2026-11-24',
        'sifat' => 'rahasia',
        'tujuan' => 'Kepala BAPPEDA Kab. Cianjur',
        'perihal' => 'Konfirmasi Kehadiran Musrenbang',
        'isi' => 'Menunjuk delegasi BAZNAS pada pembahasan rahasia anggaran.',
        'nama_penandatangan' => 'H. Yosef Umar',
        'jabatan_penandatangan' => 'Ketua BAZNAS Kabupaten Cianjur',
    ]);
    app(AjukanSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratKeluar->id]);

    $this->actingAs($ketua);
    $suratKeluarFinal = app(SetujuiSuratKeluar::class)->jalankan(['surat_keluar_id' => $suratKeluar->id]);

    // Pencarian full-text oleh Admin Arsip menemukan baik surat masuk maupun surat keluar
    $this->actingAs($adminArsip)
        ->get(route('umum.surat.arsip.index', ['q' => 'Musrenbang']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('umum/surat/arsip')
            ->where('ringkasan.total', 2)
            ->where('ringkasan.masuk', 1)
            ->where('ringkasan.keluar', 1));

    // Pencarian dengan potongan nomor agenda juga cocok
    $this->actingAs($adminArsip)
        ->get(route('umum.surat.arsip.index', ['q' => $suratMasuk->nomor_agenda]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('ringkasan.total', 1));

    // Staf bidang lain tanpa disposisi mendapat 0 hasil di arsip maupun Ctrl+K
    $this->actingAs($stafLain)
        ->get(route('umum.surat.arsip.index', ['q' => 'Musrenbang']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('ringkasan.total', 0));

    // Ekspor Buku Agenda Excel & PDF
    $excel = $this->actingAs($adminArsip)->get(route('umum.surat.arsip.excel', ['q' => 'Musrenbang']));
    $excel->assertOk();
    expect($excel->headers->get('content-disposition'))->toContain('buku-agenda-surat.xlsx');

    $pdf = $this->actingAs($adminArsip)->get(route('umum.surat.arsip.pdf', ['q' => 'Musrenbang']));
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');

    // Pencarian global Ctrl+K: Admin Arsip melihat surat masuk & keluar; UPZ tidak menerima hasil surat
    $this->actingAs($adminArsip)
        ->getJson(route('pencarian-global', ['q' => 'Musrenbang']))
        ->assertOk()
        ->assertJsonFragment(['kategori' => 'Surat Masuk'])
        ->assertJsonFragment(['kategori' => 'Surat Keluar', 'judul' => "{$suratKeluarFinal->nomor} — {$suratKeluarFinal->perihal}"]);

    $this->actingAs($upz)
        ->getJson(route('pencarian-global', ['q' => 'Musrenbang']))
        ->assertOk()
        ->assertExactJson(['hasil' => []]);
});

it('ADM-T04: dasbor bidang umum menampilkan ringkasan sesuai peran internal vs UPZ eksternal', function () {
    $adminArsip = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $upz = User::factory()->create()->assignRole('upz');
    $tanpaUmum = User::factory()->create()->assignRole('pelayanan_mustahik');

    $this->actingAs($adminArsip)
        ->get(route('umum.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('umum/dasbor')
            ->where('adalah_upz', false)
            ->where('akses.boleh_kelola_surat', true));

    $this->actingAs($upz)
        ->get(route('umum.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('umum/dasbor')
            ->where('adalah_upz', true));

    $this->actingAs($tanpaUmum)
        ->get(route('umum.index'))
        ->assertForbidden();
});
