<?php

use App\Actions\Action;
use App\Models\Bidang;
use App\Models\MetodeBayar;
use App\Models\User;
use App\Services\MakerChecker;
use Database\Seeders\PeranSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(PeranSeeder::class);
});

it('F0-04: trait MemilikiPembuat otomatis mengisi created_by dan relasi pembuat', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $bidang = Bidang::create([
        'kode' => 'uji_bidang',
        'nama' => 'Bidang Uji',
        'warna' => '#0A7A3C',
        'urutan' => 9,
    ]);

    $metode = MetodeBayar::create([
        'kode' => 'UJI_TUNAI',
        'nama' => 'Uji Tunai',
        'butuh_rekening' => false,
    ]);

    expect($bidang->created_by)->toBe($user->id)
        ->and($bidang->pembuat->is($user))->toBeTrue()
        ->and($metode->created_by)->toBe($user->id);

    $actionUji = new class extends Action
    {
        public function jalankan(array $data): array
        {
            return $this->denganPembuat($data);
        }
    };

    expect($actionUji->jalankan(['nama' => 'Item Uji'])['created_by'])->toBe($user->id);
});

it('F0-05: MakerChecker menolak pembuat menyetujui transaksinya sendiri meski punya peran approve', function () {
    $makerChecker = app(MakerChecker::class);

    foreach (['kabag_pendis_pendag', 'ketua', 'admin'] as $peran) {
        $pembuat = User::factory()->create()->assignRole([$peran, 'amil']);
        $this->actingAs($pembuat);

        $dokumen = Bidang::create([
            'kode' => "mc_{$peran}",
            'nama' => "Dokumen {$peran}",
            'warna' => '#0A7A3C',
            'urutan' => 10,
        ]);

        expect(fn () => $makerChecker->pastikanBerhakMenyetujui('distribusi', $dokumen, $pembuat, 'dokumen uji'))
            ->toThrow(ValidationException::class);
    }
});

it('F0-05: MakerChecker mengizinkan penyetuju berbeda yang berwenang dan menolak pengguna tanpa hak approve', function () {
    $makerChecker = app(MakerChecker::class);

    $pembuat = User::factory()->create()->assignRole(['pelayanan_mustahik', 'amil']);
    $rekanTanpaApprove = User::factory()->create()->assignRole(['pelayanan_mustahik', 'amil']);
    $waka2 = User::factory()->create()->assignRole(['waka_2', 'amil']);
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);

    $modelLain = new class(['dibuat_oleh' => $pembuat->id]) extends Model
    {
        protected $guarded = [];
    };

    expect(fn () => $makerChecker->pastikanBerhakMenyetujui('distribusi', $modelLain, $rekanTanpaApprove, 'permohonan'))
        ->toThrow(AuthorizationException::class);

    $makerChecker->pastikanBerhakMenyetujui('distribusi', $modelLain, $waka2, 'permohonan');
    $makerChecker->pastikanBerhakMenyetujui('distribusi', $pembuat->id, $ketua, 'permohonan');

    expect(true)->toBeTrue();
});
