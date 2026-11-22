<?php

use App\Actions\Pengguna\SimpanPengguna;
use App\Models\AuditLog;
use App\Models\LoginHistory;
use App\Models\User;
use App\Services\TotpService;
use Database\Seeders\PeranSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(PeranSeeder::class);
});

it('F0-06: pengguna dapat login menggunakan username atau email dan login_history tercatat', function () {
    $user = User::factory()->create([
        'username' => 'amil.pengumpulan',
        'email' => 'amil.pengumpulan@baznas.local',
        'password' => 'rahasia123',
        'status' => 'aktif',
    ]);
    $user->assignRole(['pelayanan_muzakki', 'amil']);

    $this->post('/login', [
        'identitas' => 'AMIL.PENGUMPULAN',
        'password' => 'rahasia123',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();

    $history = LoginHistory::where('user_id', $user->id)->latest('id')->firstOrFail();
    expect($history->hasil)->toBe('sukses')
        ->and($history->identitas)->toBe('AMIL.PENGUMPULAN');
});

it('F0-06: pengguna nonaktif ditolak saat login dan gagal tercatat di login_history', function () {
    $user = User::factory()->create([
        'username' => 'nonaktif.user',
        'password' => 'rahasia123',
        'status' => 'nonaktif',
    ]);

    $this->post('/login', [
        'identitas' => 'nonaktif.user',
        'password' => 'rahasia123',
    ])->assertSessionHasErrors('identitas');

    $this->assertGuest();
    expect(LoginHistory::where('user_id', $user->id)->value('hasil'))->toBe('gagal_nonaktif');
});

it('F0-06: kebijakan sandi menolak kata sandi lemah tanpa angka pada SimpanPengguna', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    expect(fn () => app(SimpanPengguna::class)->jalankan([
        'name' => 'Uji Sandi Lemah',
        'email' => 'lemah@baznas.local',
        'password' => 'sanditanpaangka',
        'password_confirmation' => 'sanditanpaangka',
        'peran' => ['pelayanan_muzakki'],
    ]))->toThrow(ValidationException::class);
});

it('2FA saat login dinonaktifkan secara bawaan untuk admin dan keuangan', function () {
    $totp = app(TotpService::class);
    $secret = $totp->buatSecret();

    $admin = User::factory()->create([
        'username' => 'admin.langsung',
        'password' => 'Aman12345',
        'status' => 'aktif',
    ]);
    $admin->assignRole(['admin', 'amil']);

    $this->post('/login', [
        'identitas' => 'admin.langsung',
        'password' => 'Aman12345',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($admin);
    $this->get('/dashboard')->assertOk();

    $this->post('/logout');

    $keuanganDengan2Fa = User::factory()->create([
        'username' => 'keuangan.langsung',
        'password' => 'Aman12345',
        'status' => 'aktif',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['ABCD-1234'],
    ]);
    $keuanganDengan2Fa->assignRole(['keuangan', 'amil']);

    $this->post('/login', [
        'identitas' => 'keuangan.langsung',
        'password' => 'Aman12345',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($keuanganDengan2Fa);
});

it('F0-07: admin dan keuangan wajib aktivasi 2FA TOTP dan login memakai tantangan 2FA', function () {
    Config::set('sisfozis.aktifkan_2fa_login', true);
    $totp = app(TotpService::class);
    $secret = $totp->buatSecret();

    $keuangan = User::factory()->create([
        'username' => 'staf.keuangan',
        'password' => 'Aman12345',
        'status' => 'aktif',
    ]);
    $keuangan->assignRole(['keuangan', 'amil']);

    // Login pertama tanpa 2FA aktif diarahkan ke pengaturan 2FA wajib.
    $this->post('/login', [
        'identitas' => 'staf.keuangan',
        'password' => 'Aman12345',
    ])->assertRedirect('/settings/two-factor');

    // Ketika 2FA belum selesai diaktifkan, rute lain diblokir oleh middleware wajib.2fa.
    $this->get('/dashboard')->assertRedirect('/settings/two-factor');

    $kodeValid = $totp->kodeSaatIni($secret);
    $this->post('/settings/two-factor', [
        'secret' => $secret,
        'code' => $kodeValid,
    ])->assertRedirect('/settings/two-factor');

    expect($keuangan->fresh()->sudahAktif2Fa())->toBeTrue();
    expect(AuditLog::where('aksi', 'aktifkan_2fa')->where('entitas_id', $keuangan->id)->exists())->toBeTrue();

    // Logout lalu login ulang -> wajib melewati tantangan 2FA.
    $this->post('/logout');

    $this->post('/login', [
        'identitas' => 'staf.keuangan',
        'password' => 'Aman12345',
    ])->assertRedirect('/two-factor-challenge');

    $this->assertGuest();

    // Kode salah ditolak.
    $this->post('/two-factor-challenge', [
        'code' => '000000',
    ])->assertSessionHasErrors('code');
    $this->assertGuest();

    // Kode benar meloloskan ke dashboard.
    $this->post('/two-factor-challenge', [
        'code' => $totp->kodeSaatIni($secret),
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($keuangan);
});

it('F0-07: admin dan keuangan tidak dapat menonaktifkan 2FA secara sepihak', function () {
    $totp = app(TotpService::class);
    $secret = $totp->buatSecret();

    $admin = User::factory()->create([
        'password' => 'Aman12345',
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['ABCD-1234'],
    ]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->delete('/settings/two-factor', ['current_password' => 'Aman12345'])
        ->assertSessionHasErrors('two_factor');

    expect($admin->fresh()->sudahAktif2Fa())->toBeTrue();
    expect((int) Config::get('session.lifetime'))->toBe(120);
});

it('F0-19: halaman login dapat diakses dan merender komponen login', function () {
    $this->get('/login')->assertOk();
});
