<?php

use App\Actions\Administrasi\SimpanJenisSurat;
use App\Actions\Administrasi\SimpanTemplateSurat;
use App\Models\AuditLog;
use App\Models\JenisSurat;
use App\Models\TemplateSurat;
use App\Models\User;
use App\Services\Administrasi\TemplateSuratService;
use Database\Seeders\AdministrasiSeeder;
use Database\Seeders\PeranSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PeranSeeder::class, AdministrasiSeeder::class]);
});

it('ADM-T01: TemplateSuratService mengekstrak dan merender placeholder variabel dengan benar', function () {
    $service = app(TemplateSuratService::class);
    $isi = 'Undangan kepada {{tujuan}} untuk menghadiri {{acara}} di {{tempat}} pada {{waktu}}.';

    expect($service->ekstrakPlaceholder($isi))->toBe(['tujuan', 'acara', 'tempat', 'waktu'])
        ->and($service->placeholderKustom($isi))->toBe(['acara', 'tempat', 'waktu'])
        ->and($service->render($isi, ['tujuan' => 'Camat Cianjur', 'acara' => 'Rakor UPZ', 'tempat' => 'Aula BAZNAS']))
        ->toBe('Undangan kepada Camat Cianjur untuk menghadiri Rakor UPZ di Aula BAZNAS pada {{waktu}}.')
        ->and($service->render($isi, ['tujuan' => 'Camat Cianjur', 'acara' => 'Rakor UPZ'], true))
        ->toBe('Undangan kepada Camat Cianjur untuk menghadiri Rakor UPZ di - pada -.');
});

it('ADM-T01: admin arsip dapat mengelola jenis surat dan template surat beserta audit log', function () {
    $adminArsip = User::factory()->create()->assignRole(['admin_arsip', 'amil']);

    $this->actingAs($adminArsip)
        ->get(route('umum.surat.template.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('umum/surat/template')
            ->has('jenis_list', 7)
            ->has('template_list', 7)
            ->where('bisa_kelola', true));

    $jenis = app(SimpanJenisSurat::class)->jalankan([
        'kode' => 'RKM',
        'nama' => 'Surat Rekomendasi',
        'format_nomor' => 'RKM-{urut:3}/{lembaga}/{tahun}',
        'pisah_urutan' => true,
    ]);

    $template = app(SimpanTemplateSurat::class)->jalankan([
        'jenis_surat_id' => $jenis->id,
        'nama' => 'Rekomendasi Bantuan Lembaga',
        'isi' => 'Merekomendasikan {{nama_lembaga}} untuk keperluan {{keperluan}}.',
    ]);

    expect($jenis->kode)->toBe('RKM')
        ->and($jenis->pisah_urutan)->toBeTrue()
        ->and($template->nama)->toBe('Rekomendasi Bantuan Lembaga')
        ->and(AuditLog::where('entitas_tipe', JenisSurat::class)->where('entitas_id', $jenis->id)->exists())->toBeTrue()
        ->and(AuditLog::where('entitas_tipe', TemplateSurat::class)->where('entitas_id', $template->id)->exists())->toBeTrue();

    $this->actingAs($adminArsip)
        ->post(route('umum.surat.template.jenis.toggle'), ['id' => $jenis->id])
        ->assertRedirect();

    expect($jenis->fresh()->aktif)->toBeFalse();
});

it('ADM-T01: pengguna non-SAU dan akun UPZ eksternal ditolak saat mengakses atau mengubah template surat', function () {
    $upz = User::factory()->create()->assignRole('upz');
    $stafPengumpulan = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $waka = User::factory()->create()->assignRole(['waka_4', 'amil']);

    $this->actingAs($upz)->get(route('umum.surat.template.index'))->assertForbidden();
    $this->actingAs($stafPengumpulan)->get(route('umum.surat.template.index'))->assertForbidden();

    $this->actingAs($waka)
        ->post(route('umum.surat.template.jenis.simpan'), [
            'kode' => 'TEST',
            'nama' => 'Tidak Boleh',
        ])
        ->assertForbidden();
});
