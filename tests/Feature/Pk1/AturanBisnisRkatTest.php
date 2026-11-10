<?php

use App\Actions\Rkat\AjukanRkat;
use App\Actions\Rkat\ArsipkanRkat;
use App\Actions\Rkat\BuatVersiRkat;
use App\Actions\Rkat\SahkanRkat;
use App\Actions\Rkat\SimpanCatatanRkat;
use App\Actions\Rkat\SimpanNilaiRkat;
use App\Models\AuditLog;
use App\Models\PosAnggaran;
use App\Models\RkatVersion;
use App\Models\User;
use App\Services\Rkat\HitungRkat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\RkatFixture;

beforeEach(function () {
    RkatFixture::siapkan();
    masuk('perencanaan');
});

function masuk(string $peran): User
{
    $user = User::factory()->create();
    $user->assignRole($peran);
    test()->actingAs($user);

    return $user;
}

function sahkan(RkatVersion $v, string $surat = 'B-1/2026'): RkatVersion
{
    app(AjukanRkat::class)->jalankan(['rkat_version_id' => $v->id]);

    return app(SahkanRkat::class)->jalankan(['rkat_version_id' => $v->id, 'nomor_surat' => $surat, 'tanggal_surat' => '2026-01-05']);
}

function posId(string $kode): int
{
    return PosAnggaran::where('kode', $kode)->value('id');
}

it('BR-01: hanya satu versi Disahkan per tahun; yang lama menjadi Digantikan', function () {
    $awal = sahkan(RkatFixture::versi(2026));

    $perubahan = app(BuatVersiRkat::class)->jalankan(['tahun' => 2026, 'jenis' => 'perubahan']);
    sahkan($perubahan, 'B-2/2026');

    expect($awal->fresh()->status)->toBe('digantikan')
        ->and($perubahan->fresh()->status)->toBe('disahkan')
        ->and(RkatVersion::where(['tahun' => 2026, 'status' => 'disahkan'])->count())->toBe(1);

    // Indeks unik parsial menolak dua Disahkan walau lewat SQL langsung.
    expect(fn () => $awal->fresh()->forceFill(['status' => 'disahkan'])->save())->toThrow(UniqueConstraintViolationException::class);
});

it('BR-02: versi Disahkan terkunci; perubahan lewat versi Perubahan yang menyalin nilai', function () {
    $awal = sahkan(RkatFixture::versi(2026));

    expect(fn () => app(SimpanNilaiRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'nilai' => [posId('L2/1.1') => 1]]))
        ->toThrow(ValidationException::class, 'terkunci');
    expect(fn () => app(SimpanCatatanRkat::class)->jalankan(['rkat_version_id' => $awal->id, 'catatan' => 'x']))
        ->toThrow(ValidationException::class);

    $perubahan = app(BuatVersiRkat::class)->jalankan(['tahun' => 2026, 'jenis' => 'perubahan']);
    expect($perubahan->nomor_perubahan)->toBe(1)
        ->and($perubahan->status)->toBe('draft')
        ->and($perubahan->disalin_dari_id)->toBe($awal->id)
        ->and($perubahan->nilai()->count())->toBe($awal->nilai()->count())
        ->and($perubahan->catatan->batas_amil_zakat_bp)->toBe(1250);

    app(SimpanNilaiRkat::class)->jalankan(['rkat_version_id' => $perubahan->id, 'nilai' => [posId('L2/1.1') => 40_000_000]]);
    expect($awal->nilai()->where('kode_asal', 'L2/1.1')->value('nilai'))->toBe(35_000_000);

    // Perubahan hanya bila tahun itu punya versi Disahkan.
    expect(fn () => app(BuatVersiRkat::class)->jalankan(['tahun' => 2027, 'jenis' => 'perubahan']))->toThrow(ValidationException::class);
});

it('BR-03: pos induk tidak dapat diinput; subtotal dan total dihitung server', function () {
    $v = RkatFixture::versi(2026);

    expect(fn () => app(SimpanNilaiRkat::class)->jalankan(['rkat_version_id' => $v->id, 'nilai' => [posId('L2/1') => 1]]))
        ->toThrow(ValidationException::class, 'dihitung sistem');

    app(SimpanNilaiRkat::class)->jalankan(['rkat_version_id' => $v->id, 'nilai' => [posId('L2/1.1') => 36_000_000]]);
    $n = HitungRkat::untukVersi($v->fresh())['per_kode'];

    expect($n['L2/1'])->toBe(10_604_520_000)
        ->and($n['L2/1.1'])->toBe(36_000_000);
});

it('BR-04: pengajuan versi manual ditolak bila VAL-1 gagal', function () {
    $v = RkatFixture::versi(2026, 'draft', ['L3/1.1' => 500_000_001]);
    expect(fn () => app(AjukanRkat::class)->jalankan(['rkat_version_id' => $v->id]))->toThrow(ValidationException::class, 'VAL-1');
    expect($v->fresh()->status)->toBe('draft');
});

it('BR-04: pengajuan ditolak bila VAL-2, VAL-3, VAL-4 atau VAL-6 gagal', function (array $ubah, bool $tanpaCatatan, string $val) {
    $v = RkatFixture::versi(2026, 'draft', $ubah, 'manual', ! $tanpaCatatan);
    expect(fn () => app(AjukanRkat::class)->jalankan(['rkat_version_id' => $v->id]))->toThrow(ValidationException::class, $val);
    expect($v->fresh()->status)->toBe('draft');
})->with([
    'VAL-2' => [['L4/2.1' => 2_391_321_601], false, 'VAL-2'],
    'VAL-3' => [['L6/1.1' => 1_325_440_001], false, 'VAL-3'],
    'VAL-4' => [['L6/1.5' => 1], false, 'VAL-4'],
    'VAL-6 batas kosong' => [[], true, 'VAL-6'],
]);

it('BR-04: pengajuan lolos bila enam VAL lulus; versi impor lolos dengan peringatan (O-1)', function () {
    $ok = app(AjukanRkat::class)->jalankan(['rkat_version_id' => RkatFixture::versi(2026)->id]);
    expect($ok['versi']->status)->toBe('diajukan')->and($ok['peringatan'])->toBeFalse();

    $impor = RkatFixture::versi(2024, 'draft', ['L3/1.1' => 1], 'impor');
    $hasil = app(AjukanRkat::class)->jalankan(['rkat_version_id' => $impor->id]);
    expect($hasil['versi']->status)->toBe('diajukan')->and($hasil['peringatan'])->toBeTrue();

    // Edit manual mengembalikan sumber ke manual sehingga BR-04 berlaku lagi.
    $impor2 = RkatFixture::versi(2023, 'draft', [], 'impor');
    app(SimpanNilaiRkat::class)->jalankan(['rkat_version_id' => $impor2->id, 'nilai' => [posId('L3/1.1') => 1]]);
    expect($impor2->fresh()->sumber)->toBe('manual');
    expect(fn () => app(AjukanRkat::class)->jalankan(['rkat_version_id' => $impor2->id]))->toThrow(ValidationException::class, 'VAL-1');
});

it('BR-11: tanpa hard delete pada RKAT; versi diarsipkan', function () {
    $v = RkatFixture::versi(2026);

    expect(fn () => $v->delete())->toThrow(LogicException::class);
    expect(fn () => $v->nilai()->first()->delete())->toThrow(LogicException::class);

    app(ArsipkanRkat::class)->jalankan(['rkat_version_id' => $v->id, 'alasan' => 'salah tahun']);
    expect($v->fresh()->arsip)->toBeTrue();

    $aktif = sahkan(RkatFixture::versi(2025));
    expect(fn () => app(ArsipkanRkat::class)->jalankan(['rkat_version_id' => $aktif->id]))->toThrow(ValidationException::class);
});

it('BR-12: persen dihitung dari nilai asli, dibulatkan hanya saat tampil', function () {
    expect(HitungRkat::persen(1, 3))->toBe(100 / 3)
        ->and(HitungRkat::tampil(HitungRkat::persen(1, 3)))->toBe('33,3%')
        ->and(HitungRkat::tampil(HitungRkat::persen(5, 0)))->toBe('n/a');

    $r = HitungRkat::untukVersi(RkatFixture::versi(2026));
    $total = HitungRkat::totalPuncak($r['pos'], $r['nilai'])['L2'];
    expect($total)->toBe(22_015_000_000)
        ->and(HitungRkat::persen($r['per_kode']['L2/1'], $total))->toBe(10_603_520_000 * 100 / 22_015_000_000);
});

it('hak: hanya perencanaan dan admin yang menulis RKAT; audit tercatat per aksi', function () {
    foreach (['pelaporan', 'keuangan', 'pimpinan'] as $peran) {
        masuk($peran);
        expect(fn () => app(BuatVersiRkat::class)->jalankan(['tahun' => 2026, 'jenis' => 'awal']))->toThrow(AuthorizationException::class);
    }

    masuk('admin');
    $v = app(BuatVersiRkat::class)->jalankan(['tahun' => 2026, 'jenis' => 'awal']);
    app(SimpanNilaiRkat::class)->jalankan(['rkat_version_id' => $v->id, 'nilai' => [posId('L2/1.1') => 5]]);

    $audit = AuditLog::where('entitas_tipe', RkatVersion::class)->where('entitas_id', $v->id)->orderBy('id')->get();
    expect($audit->pluck('aksi')->all())->toBe(['buat', 'ubah_nilai'])
        ->and($audit[1]->nilai_baru)->toBe(['L2/1.1' => 5])
        ->and($audit[1]->user_id)->not->toBeNull();
});
