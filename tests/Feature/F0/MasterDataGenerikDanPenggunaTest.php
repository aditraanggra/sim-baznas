<?php

use App\Models\Asnaf;
use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\PeranSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed([PeranSeeder::class, MasterDanaSeeder::class]);
});

it('F0-12: admin dapat melihat, menambah, mengubah, dan menonaktifkan master data generik tanpa hard delete', function () {
    $admin = User::factory()->create()->assignRole(['admin', 'amil']);
    $this->actingAs($admin);

    $this->get('/pengaturan/master-data?jenis=asnaf')->assertOk();

    $this->post('/pengaturan/master-data/asnaf', [
        'kode' => 'ASNAF_UJI',
        'nama' => 'Asnaf Uji',
        'urutan' => 99,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $asnaf = Asnaf::where('kode', 'ASNAF_UJI')->firstOrFail();
    expect($asnaf->nama)->toBe('Asnaf Uji')
        ->and($asnaf->aktif)->toBeTrue()
        ->and($asnaf->created_by)->toBe($admin->id);

    $this->post('/pengaturan/master-data/asnaf', [
        'id' => $asnaf->id,
        'kode' => 'ASNAF_UJI',
        'nama' => 'Asnaf Uji Diubah',
        'urutan' => 98,
    ])->assertSessionHasNoErrors();

    expect($asnaf->fresh()->nama)->toBe('Asnaf Uji Diubah');

    $this->post('/pengaturan/master-data/asnaf/toggle-aktif', [
        'id' => $asnaf->id,
        'alasan' => 'Tidak dipakai sementara',
    ])->assertSessionHasNoErrors();

    expect($asnaf->fresh()->aktif)->toBeFalse()
        ->and($asnaf->fresh()->deleted_at)->toBeNull();

    expect(AuditLog::where('entitas_tipe', Asnaf::class)->where('entitas_id', $asnaf->id)->count())->toBe(3);
});

it('F0-12 & F0-20: peran non-admin ditolak mengakses master data dan pengelolaan pengguna', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);

    $this->actingAs($staf)->get('/pengaturan/master-data')->assertForbidden();
    $this->actingAs($staf)->post('/pengaturan/master-data/asnaf', ['kode' => 'X', 'nama' => 'X', 'urutan' => 1])->assertForbidden();
    $this->actingAs($staf)->post('/pengaturan/pengguna/reset-sandi', ['id' => $staf->id, 'password' => 'Aman12345', 'password_confirmation' => 'Aman12345'])->assertForbidden();
});

it('F0-21: admin dapat mereset sandi dan menonaktifkan pengguna dengan jejak audit lengkap', function () {
    $admin = User::factory()->create()->assignRole(['admin', 'amil']);
    $target = User::factory()->create(['status' => 'aktif'])->assignRole(['pelayanan_mustahik', 'amil']);
    $this->actingAs($admin);

    $this->post('/pengaturan/pengguna/reset-sandi', [
        'id' => $target->id,
        'password' => 'SandiBaru99',
        'password_confirmation' => 'SandiBaru99',
    ])->assertSessionHasNoErrors();

    expect(Hash::check('SandiBaru99', $target->fresh()->password))->toBeTrue();
    expect(AuditLog::where('aksi', 'reset_sandi')->where('entitas_id', $target->id)->exists())->toBeTrue();

    $this->post('/pengaturan/pengguna/toggle-status', [
        'id' => $target->id,
        'alasan' => 'Cuti panjang',
    ])->assertSessionHasNoErrors();

    expect($target->fresh()->status)->toBe('nonaktif');
    expect(AuditLog::where('aksi', 'nonaktifkan')->where('entitas_id', $target->id)->exists())->toBeTrue();
});
