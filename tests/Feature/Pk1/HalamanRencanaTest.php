<?php

use App\Models\ImportJob;
use App\Models\PosAnggaran;
use App\Models\RkatDocument;
use App\Models\RkatVersion;
use App\Models\User;
use App\Services\Rkat\HitungRkat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\RkatFixture;

beforeEach(function () {
    RkatFixture::siapkan();
});

function login(string $peran): User
{
    $user = User::factory()->create();
    $user->assignRole($peran);
    test()->actingAs($user);

    return $user;
}

it('FR-R-01: daftar RKAT dan detail dapat dilihat semua peran; detail memuat pohon, validasi, dan total server', function () {
    $v = RkatFixture::versi(2026);

    foreach (['perencanaan', 'pelaporan', 'keuangan', 'pimpinan', 'admin'] as $peran) {
        login($peran);
        $this->get(route('pkp.rencana'))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('pkp/rencana/index')
            ->where('daftar.0.tahun', max((int) date('Y'), 2026))
        );
    }

    login('pimpinan');
    $this->get(route('pkp.rencana.show', $v))->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('pkp/rencana/show')
        ->where('dapat_tulis', false)
        ->has('validasi', 6)
        ->where('pos', fn ($pos) => collect($pos)->firstWhere('kode', 'L2/1')['nilai'] === 10_603_520_000
            && collect($pos)->firstWhere('kode', 'L2/1')['editable'] === false
            && collect($pos)->firstWhere('kode', 'L2/1.1')['editable'] === false)
    );
});

it('FR-R-02: perencanaan menyimpan nilai daun lewat HTTP; pelaporan ditolak; versi terkunci ditolak di server', function () {
    $v = RkatFixture::versi(2026);
    $id = PosAnggaran::where('kode', 'L2/1.1')->value('id');

    login('pelaporan');
    $this->post(route('pkp.rencana.nilai', $v), ['nilai' => [$id => 1]])->assertForbidden();

    login('perencanaan');
    $this->post(route('pkp.rencana.nilai', $v), ['nilai' => [$id => 36_000_000]])->assertRedirect();
    expect($v->nilai()->where('kode_asal', 'L2/1.1')->value('nilai'))->toBe(36_000_000);

    $v->update(['status' => 'disahkan']);
    $this->post(route('pkp.rencana.nilai', $v), ['nilai' => [$id => 1]])->assertSessionHasErrors('versi');
    expect($v->nilai()->where('kode_asal', 'L2/1.1')->value('nilai'))->toBe(36_000_000);
});

it('FR-R-04: ajukan lewat HTTP ditolak bila VAL gagal; lulus lalu disahkan dengan nomor surat', function () {
    login('perencanaan');
    $gagal = RkatFixture::versi(2025, 'draft', ['L3/1.1' => 500_000_001]);
    $this->post(route('pkp.rencana.ajukan', $gagal))->assertSessionHasErrors('validasi');
    expect($gagal->fresh()->status)->toBe('draft');

    $ok = RkatFixture::versi(2026);
    $this->post(route('pkp.rencana.ajukan', $ok))->assertSessionHasNoErrors();
    $this->post(route('pkp.rencana.sahkan', $ok), [])->assertSessionHasErrors(['nomor_surat', 'tanggal_surat']);
    $this->post(route('pkp.rencana.sahkan', $ok), ['nomor_surat' => 'B-9/2026', 'tanggal_surat' => '2026-01-05'])->assertSessionHasNoErrors();

    expect($ok->fresh()->status)->toBe('disahkan')->and($ok->fresh()->nomor_surat)->toBe('B-9/2026');
});

it('FR-R-05: unggah PDF surat pengesahan; berkas lama diarsipkan, bukan dihapus (BR-11)', function () {
    Storage::fake('local');
    login('perencanaan');
    $v = RkatFixture::versi(2026);

    foreach (['a.pdf', 'b.pdf'] as $nama) {
        $this->post(route('pkp.rencana.dokumen', $v), [
            'jenis' => 'surat_pengesahan',
            'berkas' => UploadedFile::fake()->create($nama, 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();
    }

    $dokumen = RkatDocument::where('rkat_version_id', $v->id)->orderBy('id')->get();
    expect($dokumen)->toHaveCount(2)
        ->and($dokumen[0]->diarsipkan_pada)->not->toBeNull()
        ->and($dokumen[1]->diarsipkan_pada)->toBeNull();
    Storage::disk('local')->assertExists($dokumen[0]->path);

    $this->get(route('pkp.rencana.dokumen.unduh', $dokumen[1]))->assertOk();
    $this->post(route('pkp.rencana.dokumen', $v), ['jenis' => 'surat_pengesahan', 'berkas' => UploadedFile::fake()->create('x.exe', 5)])->assertSessionHasErrors('berkas');
});

it('FR-R-03: wizard impor lewat HTTP (unggah, pratinjau, terapkan) membuat versi Draft sumber impor', function () {
    Storage::fake('local');
    login('perencanaan');

    $berkas = new UploadedFile(base_path('docs/fixtures/rkat-2022-2031.xlsx'), 'rkat.xlsx', null, null, true);
    $resp = $this->post(route('pkp.rencana.impor.unggah'), ['tahun' => 2026, 'berkas' => $berkas]);
    $resp->assertRedirect();

    $job = ImportJob::latest('id')->firstOrFail();
    $this->get(route('pkp.rencana.impor.pratinjau', $job))->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('pkp/rencana/impor-pratinjau')
        ->where('job.baris_gagal', 0)
        ->where('awal_ada', false)
    );

    $this->post(route('pkp.rencana.impor.terapkan', $job), ['rkat_version_id' => null])->assertRedirect();
    $versi = RkatVersion::firstOrFail();
    expect($versi->sumber)->toBe('impor')->and($versi->tahun)->toBe(2026)->and($versi->status)->toBe('draft');

    login('pimpinan');
    $this->get(route('pkp.rencana.impor'))->assertForbidden();
});

it('FR-R-02: pos luar neraca L2/LN berada setelah seluruh pos L2 neraca', function () {
    $v = RkatFixture::versi(2026);
    $kode = collect(HitungRkat::untukVersi($v)['pos'])->where('lampiran', 'L2')->pluck('kode')->values();

    $pertamaLn = $kode->search(fn ($k) => str_starts_with($k, 'L2/LN/'));
    expect($pertamaLn)->toBe($kode->count() - 4)
        ->and($kode->slice($pertamaLn)->every(fn ($k) => str_starts_with($k, 'L2/LN/')))->toBeTrue();
});
