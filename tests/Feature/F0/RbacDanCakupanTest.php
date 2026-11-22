<?php

use App\Actions\Pengguna\SimpanPengguna;
use App\Models\Concerns\CakupanUpz;
use App\Models\User;
use Database\Seeders\PeranSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(PeranSeeder::class);
});

it('F0-03: PeranSeeder menanam seluruh 18 peran SISFOZIS beserta admin dan alias legacy', function () {
    foreach (PeranSeeder::PERAN_SISFOZIS as $kode) {
        expect(Role::where('name', $kode)->exists())->toBeTrue();
    }

    expect(Role::where('name', 'pelaporan')->exists())->toBeTrue()
        ->and(Role::where('name', 'pimpinan')->exists())->toBeTrue();
});

it('F0-03 K7: SimpanPengguna otomatis memberi peran amil untuk pengguna internal dan tidak untuk upz murni', function () {
    $admin = User::factory()->create();
    $admin->assignRole(['admin', 'amil']);
    $this->actingAs($admin);

    $staf = app(SimpanPengguna::class)->jalankan([
        'name' => 'Pelayanan Muzakki',
        'email' => 'muzakki.staf@baznas.local',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'peran' => ['pelayanan_muzakki'],
    ]);

    expect($staf->hasRole('pelayanan_muzakki'))->toBeTrue()
        ->and($staf->hasRole('amil'))->toBeTrue()
        ->and($staf->adalahInternal())->toBeTrue()
        ->and($staf->adalahUpzEksternal())->toBeFalse();

    $pengurusUpz = app(SimpanPengguna::class)->jalankan([
        'name' => 'Pengurus UPZ DKM',
        'email' => 'dkm.agung@baznas.local',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
        'peran' => ['upz'],
    ]);

    expect($pengurusUpz->hasRole('upz'))->toBeTrue()
        ->and($pengurusUpz->hasRole('amil'))->toBeFalse()
        ->and($pengurusUpz->adalahInternal())->toBeFalse()
        ->and($pengurusUpz->adalahUpzEksternal())->toBeTrue();
});

it('F0-03: kartuLauncher dan matriks peran x bidang x aksi sesuai K7', function () {
    $pelayananMuzakki = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    expect($pelayananMuzakki->kartuLauncher())->toBe(['pengumpulan', 'umum'])
        ->and($pelayananMuzakki->bolehAksiBidang('pengumpulan', 'create'))->toBeTrue()
        ->and($pelayananMuzakki->bolehAksiBidang('distribusi', 'view'))->toBeFalse();

    $pelayananMuzakkiTanpaAmil = User::factory()->create()->assignRole(['pelayanan_muzakki']);
    expect($pelayananMuzakkiTanpaAmil->kartuLauncher())->toBe(['pengumpulan']);

    foreach (['pelayanan_mustahik', 'staf_pendistribusian', 'staf_pendayagunaan'] as $peranDistribusi) {
        $staf = User::factory()->create()->assignRole($peranDistribusi);
        expect($staf->kartuLauncher())->toBe(['distribusi'])
            ->and($staf->bolehAksiBidang('distribusi', 'create'))->toBeTrue()
            ->and($staf->bolehAksiBidang('distribusi', 'approve'))->toBeFalse();
    }

    $adminArsip = User::factory()->create()->assignRole('admin_arsip');
    expect($adminArsip->kartuLauncher())->toBe(['umum'])
        ->and($adminArsip->bolehAksiBidang('umum', 'create'))->toBeTrue()
        ->and($adminArsip->bolehAksiBidang('umum', 'approve'))->toBeFalse();

    $kabag = User::factory()->create()->assignRole(['kabag_pendis_pendag', 'amil']);
    expect($kabag->kartuLauncher())->toBe(['pengumpulan', 'distribusi', 'umum', 'pkp'])
        ->and($kabag->bolehAksiBidang('distribusi', 'create'))->toBeTrue()
        ->and($kabag->bolehAksiBidang('distribusi', 'approve'))->toBeTrue()
        ->and($kabag->bolehAksiBidang('pengumpulan', 'view'))->toBeTrue()
        ->and($kabag->bolehAksiBidang('pengumpulan', 'create'))->toBeFalse();

    $waka1 = User::factory()->create()->assignRole('waka_1');
    expect($waka1->kartuLauncher())->toBe(['pengumpulan', 'distribusi', 'umum', 'pkp'])
        ->and($waka1->bolehAksiBidang('pengumpulan', 'approve'))->toBeTrue()
        ->and($waka1->bolehAksiBidang('distribusi', 'approve'))->toBeFalse()
        ->and($waka1->bolehAksiBidang('distribusi', 'view'))->toBeTrue();

    $ketua = User::factory()->create()->assignRole('ketua');
    expect($ketua->kartuLauncher())->toBe(['pengumpulan', 'distribusi', 'umum', 'pkp']);
    foreach (array_keys(Config::get('sisfozis.bidang')) as $kodeBidang) {
        expect($ketua->bolehAksiBidang($kodeBidang, 'approve'))->toBeTrue();
    }

    $admin = User::factory()->create()->assignRole('admin');
    expect($admin->kartuLauncher())->toBe(['pengumpulan', 'distribusi', 'umum', 'pkp']);
    foreach (array_keys(Config::get('sisfozis.bidang')) as $kodeBidang) {
        expect($admin->bolehAksiBidang($kodeBidang, 'delete'))->toBeTrue()
            ->and($admin->bolehAksiBidang($kodeBidang, 'approve'))->toBeTrue();
    }
});

it('F0-03 K12: CakupanUpz membatasi kueri hanya pada upz_id milik pengguna eksternal', function () {
    $modelDenganCakupan = new class extends Model
    {
        use CakupanUpz;

        protected $table = 'users';
    };

    $targetUpz10 = User::factory()->create(['upz_id' => 10]);
    $targetUpz20 = User::factory()->create(['upz_id' => 20]);

    $penggunaUpz10 = User::factory()->create(['upz_id' => 10])->assignRole('upz');
    $penggunaUpzTanpaId = User::factory()->create(['upz_id' => null])->assignRole('upz');
    $stafInternal = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);

    $hasilUpz10 = $modelDenganCakupan::query()
        ->whereIn('id', [$targetUpz10->id, $targetUpz20->id])
        ->untukPengguna($penggunaUpz10)
        ->pluck('id')
        ->all();

    expect($hasilUpz10)->toBe([$targetUpz10->id]);

    $hasilUpzNull = $modelDenganCakupan::query()
        ->whereIn('id', [$targetUpz10->id, $targetUpz20->id])
        ->untukPengguna($penggunaUpzTanpaId)
        ->count();

    expect($hasilUpzNull)->toBe(0);

    $hasilInternal = $modelDenganCakupan::query()
        ->whereIn('id', [$targetUpz10->id, $targetUpz20->id])
        ->untukPengguna($stafInternal)
        ->count();

    expect($hasilInternal)->toBe(2);
});

it('F0-03: kabag_pkp berhak mengakses rute tulis RKAT', function () {
    $kabagPkp = User::factory()->create()->assignRole(['kabag_pkp', 'amil']);

    $this->actingAs($kabagPkp)
        ->get('/pkp/rencana/impor')
        ->assertOk();
});
