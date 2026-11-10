<?php

namespace Tests\Feature\Pk0;

use App\Actions\Pengguna\SimpanPengguna;
use App\Models\User;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PeranSeeder::class);
    }

    public function test_admin_membuat_pengguna_dengan_peran(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $user = app(SimpanPengguna::class)->jalankan([
            'name' => 'Staf Baru',
            'email' => 'staf@pkp.local',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'peran' => ['perencanaan'],
        ]);

        $this->assertTrue($user->hasRole('perencanaan'));
        $this->actingAs($user)->get('/pkp/rencana')->assertOk();
        $this->actingAs($user)->get('/pengaturan/pengguna')->assertForbidden();
    }

    public function test_sandi_wajib_untuk_pengguna_baru(): void
    {
        $this->expectException(ValidationException::class);

        app(SimpanPengguna::class)->jalankan([
            'name' => 'Tanpa Sandi',
            'email' => 'tanpa@pkp.local',
            'peran' => ['pimpinan'],
        ]);
    }
}
