<?php

use App\Models\BerkasPrivat;
use App\Models\BerkasUnduhLog;
use App\Models\User;
use App\Services\BerkasPrivatService;
use Database\Seeders\PeranSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(PeranSeeder::class);
    Storage::fake('private');
});

it('F0-23: BerkasPrivatService menyimpan berkas di disk private dengan checksum dan signed URL', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $this->actingAs($staf);

    $file = UploadedFile::fake()->create('sk-upz.pdf', 120, 'application/pdf');
    $service = app(BerkasPrivatService::class);

    $berkas = $service->unggah($file, 'sk_upz', [
        'bidang_kode' => 'pengumpulan',
        'upz_id' => 10,
    ]);

    Storage::disk('private')->assertExists($berkas->path);

    $url = $service->urlBertandaTangan($berkas);

    expect($berkas->uuid)->not->toBeEmpty()
        ->and($berkas->checksum_sha256)->toHaveLength(64)
        ->and($berkas->created_by)->toBe($staf->id)
        ->and($url)->toContain('signature=');
});

it('F0-23: akses tanpa tanda tangan atau tanda tangan kedaluwarsa ditolak 403', function () {
    $staf = User::factory()->create()->assignRole(['admin', 'amil']);
    $this->actingAs($staf);

    $service = app(BerkasPrivatService::class);
    $berkas = $service->unggah(
        UploadedFile::fake()->create('lampiran.pdf', 50, 'application/pdf'),
        'lampiran',
        ['bidang_kode' => 'pkp'],
    );

    $this->get(route('berkas.privat.unduh', ['uuid' => $berkas->uuid]))
        ->assertForbidden();

    $urlKedaluwarsa = $service->urlBertandaTangan($berkas, -5);
    $this->get($urlKedaluwarsa)->assertForbidden();
});

it('F0-23: pengguna berwenang dapat mengunduh lewat signed URL dan tercatat di berkas_unduh_log', function () {
    $pembuat = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $this->actingAs($pembuat);

    $service = app(BerkasPrivatService::class);
    $berkas = $service->unggah(
        UploadedFile::fake()->create('bukti-setoran.pdf', 64, 'application/pdf'),
        'penerimaan',
        ['bidang_kode' => 'pengumpulan', 'upz_id' => 10],
    );

    $url = $service->urlBertandaTangan($berkas);

    $this->actingAs($pembuat)
        ->get($url)
        ->assertOk()
        ->assertHeader('content-disposition');

    $log = BerkasUnduhLog::where('berkas_privat_id', $berkas->id)->firstOrFail();

    expect($log->user_id)->toBe($pembuat->id)
        ->and($log->aksi)->toBe('unduh')
        ->and($log->ip_address)->not->toBeNull();
});

it('F0-23: pembatasan bidang dan upz_id ditegakkan saat mengunduh berkas privat', function () {
    $admin = User::factory()->create()->assignRole(['admin', 'amil']);
    $this->actingAs($admin);

    $service = app(BerkasPrivatService::class);
    $berkasDistribusi = $service->unggah(
        UploadedFile::fake()->create('ktp-mustahik.pdf', 40, 'application/pdf'),
        'mustahik',
        ['bidang_kode' => 'distribusi'],
    );
    $berkasUpz10 = $service->unggah(
        UploadedFile::fake()->create('sk-upz-10.pdf', 40, 'application/pdf'),
        'sk_upz',
        ['bidang_kode' => 'umum', 'upz_id' => 10],
    );

    $stafPengumpulan = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $pengurusUpz20 = User::factory()->create(['upz_id' => 20])->assignRole('upz');
    $pengurusUpz10 = User::factory()->create(['upz_id' => 10])->assignRole('upz');

    $this->actingAs($stafPengumpulan)
        ->get($service->urlBertandaTangan($berkasDistribusi))
        ->assertForbidden();

    $this->actingAs($pengurusUpz20)
        ->get($service->urlBertandaTangan($berkasUpz10))
        ->assertForbidden();

    $this->actingAs($pengurusUpz10)
        ->get($service->urlBertandaTangan($berkasUpz10))
        ->assertOk();
});

it('F0-23: berkas dengan izinkan_publik_bertanda_tangan dapat diunduh tamu jika signed URL valid dan tetap tercatat di log', function () {
    $admin = User::factory()->create()->assignRole(['admin', 'amil']);
    $this->actingAs($admin);

    $service = app(BerkasPrivatService::class);
    $berkasPublik = $service->unggah(
        UploadedFile::fake()->create('sk-publik.pdf', 32, 'application/pdf'),
        'sk_upz',
        ['izinkan_publik_bertanda_tangan' => true],
    );

    auth()->logout();

    $this->get($service->urlBertandaTangan($berkasPublik, 10, 'lihat'))
        ->assertOk();

    $log = BerkasUnduhLog::where('berkas_privat_id', $berkasPublik->id)->firstOrFail();

    expect(BerkasPrivat::count())->toBe(1)
        ->and($log->user_id)->toBeNull()
        ->and($log->aksi)->toBe('lihat');
});
