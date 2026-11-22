<?php

use App\Models\User;
use Database\Seeders\PeranSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(PeranSeeder::class);
});

it('F0-17: halaman launcher menampilkan kartu bidang dan peran_label sesuai peran pengguna', function () {
    $muzakkiDanAmil = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);

    $this->actingAs($muzakkiDanAmil)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('auth.user.kartu_launcher', ['pengumpulan', 'umum'])
            ->where('auth.user.peran_label', ['Pelayanan Muzakki', 'Amil'])
            ->where('auth.user.admin_sistem', false));

    $pelayananMustahik = User::factory()->create()->assignRole('pelayanan_mustahik');

    $this->actingAs($pelayananMustahik)
        ->get('/launcher')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.kartu_launcher', ['distribusi']));

    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);
    $admin = User::factory()->create()->assignRole(['admin', 'amil']);

    $this->actingAs($ketua)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.kartu_launcher', ['pengumpulan', 'distribusi', 'umum', 'pkp'])
            ->where('auth.user.admin_sistem', false));

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.kartu_launcher', ['pengumpulan', 'distribusi', 'umum', 'pkp'])
            ->where('auth.user.admin_sistem', true));
});

it('F0-18: rute modul bidang menegakkan middleware bidang di server sesuai matriks peran', function () {
    $pelayananMuzakki = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $pelayananMustahik = User::factory()->create()->assignRole('pelayanan_mustahik');
    $kabagPengumpulan = User::factory()->create()->assignRole(['kabag_pengumpulan', 'amil']);
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);

    $this->actingAs($pelayananMuzakki)->get('/pengumpulan')->assertOk();
    $this->actingAs($pelayananMuzakki)->get('/distribusi')->assertForbidden();
    $this->actingAs($pelayananMuzakki)->get('/pkp')->assertForbidden();

    $this->actingAs($pelayananMustahik)->get('/distribusi')->assertOk();
    $this->actingAs($pelayananMustahik)->get('/pengumpulan')->assertForbidden();
    $this->actingAs($pelayananMustahik)->get('/umum')->assertForbidden();

    foreach (['/pengumpulan', '/distribusi', '/umum', '/pkp'] as $url) {
        $this->actingAs($kabagPengumpulan)->get($url)->assertOk();
        $this->actingAs($ketua)->get($url)->assertOk();
    }
});
