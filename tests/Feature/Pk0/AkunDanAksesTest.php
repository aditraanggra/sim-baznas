<?php

namespace Tests\Feature\Pk0;

use App\Actions\Pos\SimpanPos;
use App\Models\AuditLog;
use App\Models\PosAnggaran;
use App\Models\User;
use Database\Seeders\PeranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkunDanAksesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PeranSeeder::class);
    }

    /** RBAC: 5 peran x objek matriks PRD bagian 2. */
    public function test_rbac_matriks_lima_peran(): void
    {
        $akses = [
            'admin' => ['/pengaturan/pengguna', '/pengaturan/pos-anggaran', '/pengaturan/impor', '/pengaturan/audit', '/dashboard'],
            'perencanaan' => ['/dashboard', '/pkp/rencana'],
            'pelaporan' => ['/dashboard', '/pkp/realisasi'],
            'keuangan' => ['/dashboard', '/pkp/keuangan'],
            'pimpinan' => ['/dashboard', '/pkp/rencana', '/pkp/realisasi', '/pkp/kinerja', '/pkp/keuangan'],
        ];

        foreach ($akses as $peran => $rute) {
            $user = User::factory()->create();
            $user->assignRole($peran);

            foreach ($rute as $url) {
                $this->actingAs($user)->get($url)->assertOk();
            }
        }

        foreach (['perencanaan', 'pelaporan', 'keuangan', 'pimpinan'] as $peran) {
            $user = User::factory()->create();
            $user->assignRole($peran);
            $this->actingAs($user)->get('/pengaturan/pengguna')->assertForbidden();
        }
    }

    public function test_pendaftaran_mandiri_dinonaktifkan(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_login_dengan_email_berhasil(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /** Audit: setiap Action menghasilkan satu baris audit dengan nilai lama/baru. */
    public function test_audit_tercatat_pada_setiap_action(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);
        $pos = app(SimpanPos::class)->jalankan([
            'kode' => 'L2/9.9',
            'tipe_nilai' => 'rupiah',
            'nama' => 'Pos Uji',
            'lampiran' => 'L2',
            'tingkat' => 2,
            'agregasi' => 'input_langsung',
            'sifat_waktu' => 'aliran',
            'berlaku_dari_tahun' => 2026,
        ]);

        $log = AuditLog::where('entitas_tipe', PosAnggaran::class)->where('entitas_id', $pos->id)->firstOrFail();
        $this->assertSame('buat', $log->aksi);
        $this->assertSame('Pos Uji', $log->nilai_baru['nama']);
        $this->assertNull($log->nilai_lama);
        $this->assertSame($admin->id, $log->user_id);

        app(SimpanPos::class)->jalankan(['id' => $pos->id, 'kode' => 'L2/9.9', 'tipe_nilai' => 'rupiah', 'nama' => 'Pos Uji Ubah', 'lampiran' => 'L2', 'tingkat' => 2, 'agregasi' => 'input_langsung', 'sifat_waktu' => 'aliran', 'berlaku_dari_tahun' => 2026]);
        $ubah = AuditLog::where('entitas_id', $pos->id)->latest('id')->first();
        $this->assertSame('Pos Uji', $ubah->nilai_lama['nama']);
        $this->assertSame('Pos Uji Ubah', $ubah->nilai_baru['nama']);
        $this->assertSame(2, AuditLog::count());
    }

    public function test_audit_append_only_menolak_ubah_dan_hapus(): void
    {
        $log = AuditLog::create(['aksi' => 'uji', 'entitas_tipe' => 'x', 'entitas_id' => 1]);

        $this->expectException(\LogicException::class);
        $log->delete();
    }
}
