<?php

use App\Actions\Upz\SimpanUpz;
use App\Models\Desa;
use App\Models\JenisUpz;
use App\Models\User;
use Database\Seeders\BidangSeeder;
use Database\Seeders\JenisUpzSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\WilayahSeeder;

beforeEach(function () {
    $this->seed([PeranSeeder::class, BidangSeeder::class, WilayahSeeder::class, JenisUpzSeeder::class]);
});

it('X1-01 K3 K7 K12: matriks 18 peran tetap dan admin ditegakkan di seluruh modul Fase 1A', function () {
    $matriks = config('sisfozis.matriks_peran', []);
    $semuaPeran = array_keys(config('sisfozis.peran', []));

    expect($semuaPeran)->toHaveCount(19);

    foreach ($semuaPeran as $kodePeran) {
        $pengguna = User::factory()->create([
            'two_factor_secret' => in_array($kodePeran, ['admin', 'keuangan'], true) ? 'JBSWY3DPEHPK3PXP' : null,
            'two_factor_confirmed_at' => in_array($kodePeran, ['admin', 'keuangan'], true) ? now() : null,
        ])->assignRole($kodePeran);

        $bolehLihatPengumpulan = in_array('view', $matriks[$kodePeran]['pengumpulan'] ?? [], true);
        $bolehBuatPengumpulan = in_array('create', $matriks[$kodePeran]['pengumpulan'] ?? [], true);
        $bolehLihatDistribusi = in_array('view', $matriks[$kodePeran]['distribusi'] ?? [], true);
        $bolehBuatDistribusi = in_array('create', $matriks[$kodePeran]['distribusi'] ?? [], true);
        $bolehLihatUmum = in_array('view', $matriks[$kodePeran]['umum'] ?? [], true);

        $this->actingAs($pengguna)
            ->get(route('pengumpulan.index'))
            ->assertStatus($bolehLihatPengumpulan ? 200 : 403);

        $this->actingAs($pengguna)
            ->get(route('pengumpulan.penerimaan.create'))
            ->assertStatus($bolehBuatPengumpulan ? 200 : 403);

        $this->actingAs($pengguna)
            ->get(route('distribusi.index'))
            ->assertStatus($bolehLihatDistribusi ? 200 : 403);

        $this->actingAs($pengguna)
            ->get(route('distribusi.penyaluran.create'))
            ->assertStatus($bolehBuatDistribusi ? 200 : 403);

        $this->actingAs($pengguna)
            ->get(route('umum.upz.index'))
            ->assertStatus($bolehLihatUmum ? 200 : 403);

        $this->actingAs($pengguna)
            ->get(route('pengaturan.pengguna'))
            ->assertStatus($kodePeran === 'admin' ? 200 : 403);
    }
});

it('X1-01 K7 K12: kabag melihat bidang lain view-only dan akun upz hanya melihat UPZ miliknya', function () {
    $kabagPkp = User::factory()->create()->assignRole(['kabag_pkp', 'amil']);

    $this->actingAs($kabagPkp)->get(route('pengumpulan.index'))->assertOk();
    $this->actingAs($kabagPkp)->get(route('pengumpulan.penerimaan.create'))->assertForbidden();
    $this->actingAs($kabagPkp)->get(route('distribusi.index'))->assertOk();
    $this->actingAs($kabagPkp)->get(route('distribusi.penyaluran.create'))->assertForbidden();

    $jenis = JenisUpz::firstOrFail();
    $desa = Desa::firstOrFail();

    $this->actingAs($kabagPkp);
    $stafUmum = User::factory()->create()->assignRole(['admin_arsip', 'amil']);
    $this->actingAs($stafUmum);

    $upz1 = app(SimpanUpz::class)->jalankan([
        'nama' => 'UPZ Satu Matriks',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'Ketua UPZ 1',
        'alamat' => 'Desa 1',
        'pic_nama' => 'PIC UPZ 1',
        'pic_telepon' => '081100000001',
    ]);
    $upz2 = app(SimpanUpz::class)->jalankan([
        'nama' => 'UPZ Dua Matriks',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'Ketua UPZ 2',
        'alamat' => 'Desa 2',
        'pic_nama' => 'PIC UPZ 2',
        'pic_telepon' => '081100000002',
    ]);

    $akunUpz = User::factory()->create(['upz_id' => $upz1->id])->assignRole('upz');
    $this->actingAs($akunUpz)->get(route('umum.upz.show', $upz1))->assertOk();
    $this->actingAs($akunUpz)->get(route('umum.upz.show', $upz2))->assertForbidden();
});
