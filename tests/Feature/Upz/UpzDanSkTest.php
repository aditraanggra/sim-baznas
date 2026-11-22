<?php

use App\Actions\Upz\SimpanSkUpz;
use App\Actions\Upz\SimpanUpz;
use App\Models\AuditLog;
use App\Models\Desa;
use App\Models\JenisUpz;
use App\Models\Notifikasi;
use App\Models\SkUpz;
use App\Models\Upz;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\BidangSeeder;
use Database\Seeders\JenisUpzSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([PeranSeeder::class, BidangSeeder::class, WilayahSeeder::class, JenisUpzSeeder::class]);
});

it('UPZ-T01 & UPZ-T02: membuat UPZ, mengunggah SK UPZ privat, dan menghitung status aktif atau habis di view', function () {
    Storage::fake('private');

    $staf = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $this->actingAs($staf);

    $jenis = JenisUpz::where('kode', 'DKM')->firstOrFail();
    $desa = Desa::firstOrFail();

    $upz = app(SimpanUpz::class)->jalankan([
        'nama' => 'UPZ Masjid Agung Cianjur',
        'nomor_sk' => 'SK-UPZ/000/2026',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'H. Ahmad Subhan',
        'alamat' => 'Jl. Siti Jenab No. 1, Cianjur',
        'pic_nama' => 'Ustadz Hasan',
        'pic_telepon' => '081234567890',
    ]);

    expect($upz->kode)->toStartWith('UPZ-')
        ->and($upz->nomor_sk)->toBe('SK-UPZ/000/2026')
        ->and($upz->created_by)->toBe($staf->id);

    $this->post(route('umum.upz.store'), [
        'nama' => 'UPZ Masjid At-Taqwa',
        'nomor_sk' => 'SK-UPZ/999/2026',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'H. Zainal',
        'alamat' => 'Jl. Arif Rahman Hakim, Cianjur',
        'pic_nama' => 'Budi',
        'pic_telepon' => '081299988877',
    ])->assertSessionHasNoErrors();

    $upzHttp = Upz::where('nama', 'UPZ Masjid At-Taqwa')->firstOrFail();
    expect($upzHttp->nomor_sk)->toBe('SK-UPZ/999/2026');

    $this->get(route('umum.upz.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('umum/upz/index')
            ->where('upz_list.0.nomor_sk', fn ($nilai) => is_string($nilai) || $nilai === null));

    $this->get(route('umum.upz.show', $upzHttp))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('umum/upz/show')
            ->where('upz.nomor_sk', 'SK-UPZ/999/2026'));

    $skAktif = app(SimpanSkUpz::class)->jalankan([
        'upz_id' => $upz->id,
        'nomor' => 'SK-UPZ/001/2026',
        'tanggal_sk' => Carbon::today()->toDateString(),
        'berlaku_mulai' => Carbon::today()->toDateString(),
        'berlaku_sampai' => Carbon::today()->addDays(200)->toDateString(),
        'berkas' => UploadedFile::fake()->create('sk-upz.pdf', 120, 'application/pdf'),
    ]);

    $skHabis = app(SimpanSkUpz::class)->jalankan([
        'upz_id' => $upz->id,
        'nomor' => 'SK-UPZ/001/2024',
        'tanggal_sk' => Carbon::today()->subYears(2)->toDateString(),
        'berlaku_mulai' => Carbon::today()->subYears(2)->toDateString(),
        'berlaku_sampai' => Carbon::today()->subDays(5)->toDateString(),
    ]);

    expect($skAktif->berkasPrivat)->not->toBeNull();
    Storage::disk('private')->assertExists($skAktif->berkasPrivat->path);

    $statusView = DB::table('v_sk_upz_status')->whereIn('id', [$skAktif->id, $skHabis->id])->get()->keyBy('id');
    expect($statusView[$skAktif->id]->status_berlaku)->toBe('aktif')
        ->and($statusView[$skHabis->id]->status_berlaku)->toBe('habis');

    expect(AuditLog::where('entitas_tipe', Upz::class)->where('entitas_id', $upz->id)->exists())->toBeTrue();
});

it('UPZ-T03: scheduler mengirim notifikasi in-app SK UPZ H-90/H-60/H-30 tanpa duplikat', function () {
    $arsip = User::factory()->create(['status' => 'aktif'])->assignRole(['admin_arsip', 'amil']);
    $kabag = User::factory()->create(['status' => 'aktif'])->assignRole(['kabag_sau', 'amil']);
    $this->actingAs($arsip);

    $jenis = JenisUpz::where('kode', 'DIBALEKA')->firstOrFail();
    $desa = Desa::firstOrFail();
    $upz = app(SimpanUpz::class)->jalankan([
        'nama' => 'UPZ DIBALEKA Cipanas',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'H. Ridwan',
        'alamat' => 'Cipanas',
        'pic_nama' => 'Siti',
        'pic_telepon' => '081200011122',
    ]);

    $pengurusUpz = User::factory()->create(['status' => 'aktif', 'upz_id' => $upz->id])->assignRole('upz');

    $sk = app(SimpanSkUpz::class)->jalankan([
        'upz_id' => $upz->id,
        'nomor' => 'SK-UPZ/DIBALEKA/2026',
        'tanggal_sk' => Carbon::today()->subMonths(11)->toDateString(),
        'berlaku_mulai' => Carbon::today()->subMonths(11)->toDateString(),
        'berlaku_sampai' => Carbon::today()->addDays(25)->toDateString(),
    ]);

    $this->artisan('upz:peringatan-sk')->assertSuccessful();
    $this->artisan('upz:peringatan-sk')->assertSuccessful();

    foreach ([$arsip, $kabag, $pengurusUpz] as $penerima) {
        $jumlah = Notifikasi::where('user_id', $penerima->id)
            ->where('jenis', 'sk_upz_h_30')
            ->where('ref_tabel', SkUpz::class)
            ->where('ref_id', $sk->id)
            ->count();

        expect($jumlah)->toBe(1);
    }
});

it('UPZ-T04 & UPZ-T01 K12: halaman publik verifikasi SK UPZ menampilkan status non-sensitif dan akun upz eksternal terisolasi', function () {
    $arsip = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $this->actingAs($arsip);

    $jenis = JenisUpz::where('kode', 'DESA')->firstOrFail();
    $desa = Desa::firstOrFail();

    $upz1 = app(SimpanUpz::class)->jalankan([
        'nama' => 'UPZ Desa Maju Jaya',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'Kades Maju',
        'alamat' => 'Desa Maju',
        'pic_nama' => 'Sekdes',
        'pic_telepon' => '081111111111',
    ]);

    $upz2 = app(SimpanUpz::class)->jalankan([
        'nama' => 'UPZ Desa Sejahtera',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'Kades Sejahtera',
        'alamat' => 'Desa Sejahtera',
        'pic_nama' => 'Bendahara',
        'pic_telepon' => '082222222222',
    ]);

    app(SimpanSkUpz::class)->jalankan([
        'upz_id' => $upz1->id,
        'nomor' => 'SK-UPZ/MAJU/2026',
        'tanggal_sk' => Carbon::today()->toDateString(),
        'berlaku_mulai' => Carbon::today()->toDateString(),
        'berlaku_sampai' => Carbon::today()->addYear()->toDateString(),
    ]);

    $this->get('/publik/verifikasi-sk-upz?q=Maju')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/verifikasi-sk-upz')
            ->where('hasil.0.nama', 'UPZ Desa Maju Jaya')
            ->where('hasil.0.nomor_sk', 'SK-UPZ/MAJU/2026'));

    $pengurusUpz1 = User::factory()->create(['upz_id' => $upz1->id])->assignRole('upz');
    $this->actingAs($pengurusUpz1)->get(route('umum.upz.show', $upz1))->assertOk();
    $this->actingAs($pengurusUpz1)->get(route('umum.upz.show', $upz2))->assertForbidden();
});
