<?php

use App\Actions\Rkat\AjukanRkat;
use App\Actions\Rkat\PetakanAliasRkat;
use App\Actions\Rkat\PratinjauImporRkat;
use App\Actions\Rkat\SahkanRkat;
use App\Actions\Rkat\SimpanCatatanRkat;
use App\Actions\Rkat\TerapkanImporRkat;
use App\Models\ImportRow;
use App\Models\PosAnggaran;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use App\Models\User;
use App\Services\Rkat\HitungRkat;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\RkatFixture;

const BERKAS_SUMBER = 'docs/fixtures/rkat-2022-2031.xlsx';

beforeEach(function () {
    RkatFixture::siapkan();
    $user = User::factory()->create();
    $user->assignRole('perencanaan');
    test()->actingAs($user);
});

function imporRkat(string $berkas, int $tahun, ?int $versiId = null): array
{
    $job = app(PratinjauImporRkat::class)->jalankan([
        'tahun' => $tahun, 'path_absolut' => $berkas, 'path' => 'impor/uji.xlsx', 'nama_berkas' => basename($berkas),
    ]);

    return ['job' => $job, 'hasil' => app(TerapkanImporRkat::class)->jalankan(['import_job_id' => $job->id, 'rkat_version_id' => $versiId])];
}

function valVersi(RkatVersion $v): array
{
    app(SimpanCatatanRkat::class)->jalankan(['rkat_version_id' => $v->id, ...RkatFixture::batas2026()]);

    return collect(AjukanRkat::evaluasiVersi($v->fresh()))->keyBy('id')->all();
}

/** Berkas sintetis dengan template yang sama tetapi kode tak dikenal (mis. template tahun lama). */
function berkasSintetis(array $baris, string $judulSheet = 'A1.Rencana Penerimaan', string $kolom = 'Tahun 2021'): string
{
    $s = new Spreadsheet;
    $sheet = $s->getActiveSheet()->setTitle($judulSheet);
    $sheet->fromArray([['Rencana'], [], ['No', 'Jenis Dana', $kolom], ...$baris], null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'rkat').'.xlsx';
    (new Xlsx($s))->save($path);

    return $path;
}

it('FR-R-03: impor RKAT 2026 dari berkas sumber sama dengan fixture dan lulus VAL-1..VAL-6', function () {
    ['job' => $job, 'hasil' => $hasil] = imporRkat(base_path(BERKAS_SUMBER), 2026);

    expect($job->baris_gagal)->toBe(0)
        ->and($hasil['versi']->sumber)->toBe('impor')
        ->and($hasil['versi']->status)->toBe('draft')
        ->and($hasil['tak_terpetakan'])->toBe([])
        ->and($hasil['peringatan_subtotal'])->toBe([]);

    $daun = collect(RkatFixture::fixture()['pos'])->where('agregasi', 'input_langsung');
    expect($hasil['diterapkan'])->toBe($daun->count());
    foreach ($daun as $p) {
        expect($hasil['versi']->nilai()->where('kode_asal', $p['kode'])->value('nilai'))->toBe($p['nilai_2026'], $p['kode']);
    }

    expect(collect(valVersi($hasil['versi']))->pluck('lulus')->all())->toBe(array_fill(0, 6, true));
});

it('FR-R-03: impor idempoten; impor ulang tidak menggandakan data', function () {
    $a = imporRkat(base_path(BERKAS_SUMBER), 2025);
    $nilai = RkatValue::count();
    $baris = ImportRow::count();

    $b = imporRkat(base_path(BERKAS_SUMBER), 2025, $a['hasil']['versi']->id);

    expect(RkatValue::count())->toBe($nilai)
        ->and(ImportRow::count())->toBe($baris)
        ->and(RkatVersion::count())->toBe(1)
        ->and($b['hasil']['versi']->id)->toBe($a['hasil']['versi']->id);

    // Pratinjau lama sudah digantikan: menerapkannya lagi ditolak.
    expect(fn () => app(TerapkanImporRkat::class)->jalankan(['import_job_id' => $a['job']->id]))->toThrow(ValidationException::class, 'digantikan');

    // Impor awal kedua untuk tahun sama tanpa memilih versi tujuan ditolak.
    expect(fn () => app(TerapkanImporRkat::class)->jalankan(['import_job_id' => $b['job']->id]))->toThrow(ValidationException::class, 'sudah ada');
});

it('FR-R-03: RKAT 2022-2025 terimpor tanpa galat; 2025 lulus VAL, 2022-2024 gagal VAL-1/VAL-2 tetapi dapat disahkan (O-1)', function () {
    $gagalDiharapkan = [2022 => ['VAL-1', 'VAL-2'], 2023 => ['VAL-1', 'VAL-2', 'VAL-4'], 2024 => ['VAL-1', 'VAL-2'], 2025 => []];

    foreach ($gagalDiharapkan as $tahun => $gagal) {
        ['job' => $job, 'hasil' => $hasil] = imporRkat(base_path(BERKAS_SUMBER), $tahun);
        expect($job->baris_gagal)->toBe(0, "galat impor $tahun")->and($hasil['tak_terpetakan'])->toBe([]);

        $val = valVersi($hasil['versi']);
        expect(collect($val)->where('lulus', false)->keys()->all())->toBe($gagal, "VAL gagal $tahun");

        $ajukan = app(AjukanRkat::class)->jalankan(['rkat_version_id' => $hasil['versi']->id]);
        expect($ajukan['peringatan'])->toBe($gagal !== []);
        app(SahkanRkat::class)->jalankan(['rkat_version_id' => $hasil['versi']->id, 'nomor_surat' => "S-$tahun", 'tanggal_surat' => "$tahun-01-10"]);
        expect($hasil['versi']->fresh()->status)->toBe('disahkan');
    }
});

it('BR-10: kode tak terpetakan ditandai dan tidak ikut total sampai dipetakan lewat alias', function () {
    $berkas = berkasSintetis([
        ['1.1', 'Zakat Mal-Badan', 100],
        ['1.9', 'Zakat Jenis Lama', 50],
        ['1', 'Zakat', 1],
    ]);

    ['job' => $job, 'hasil' => $hasil] = imporRkat($berkas, 2021);
    $versi = $hasil['versi'];

    expect($job->baris_gagal)->toBe(0)
        ->and($hasil['tak_terpetakan'])->toBe(['L2/1.9'])
        ->and($versi->nilai()->whereNull('pos_anggaran_id')->count())->toBe(1)
        ->and($hasil['peringatan_subtotal'])->toBe([['kode' => 'L2/1', 'berkas' => 1, 'hitung' => 100]]);

    expect(HitungRkat::untukVersi($versi)['per_kode']['L2/1'])->toBe(100);
    expect(HitungRkat::untukVersi($versi)['tak_terpetakan'])->toHaveCount(1);

    $posTujuan = PosAnggaran::where('kode', 'L2/1.2')->first();
    $r = app(PetakanAliasRkat::class)->jalankan(['tahun' => 2021, 'kode_alias' => 'L2/1.9', 'pos_anggaran_id' => $posTujuan->id]);

    expect($r['dipetakan'])->toBe(1)
        ->and(HitungRkat::untukVersi($versi->fresh())['per_kode']['L2/1'])->toBe(150)
        ->and(HitungRkat::untukVersi($versi->fresh())['tak_terpetakan'])->toHaveCount(0);

    // Impor ulang memakai alias yang sudah ada: kode terpetakan, tidak menggandakan baris.
    $ulang = imporRkat($berkas, 2021, $versi->id)['hasil'];
    expect($ulang['tak_terpetakan'])->toBe([])->and($versi->nilai()->count())->toBe(2);

    // Pos induk tidak boleh jadi tujuan alias (BR-03).
    expect(fn () => app(PetakanAliasRkat::class)->jalankan(['tahun' => 2021, 'kode_alias' => 'X', 'pos_anggaran_id' => PosAnggaran::where('kode', 'L2/1')->value('id')]))
        ->toThrow(ValidationException::class);
});

it('FR-R-03: galat per baris dan template tidak dikenali dilaporkan, versi Disahkan tidak menerima impor (BR-02)', function () {
    $berkas = berkasSintetis([['1.1', 'Zakat Mal-Badan', 'abc'], ['1.2', 'Zakat Mal-Perorangan', 12.5], ['1.3', 'x', 7]]);
    $job = app(PratinjauImporRkat::class)->jalankan(['tahun' => 2021, 'path_absolut' => $berkas, 'path' => 'i/x.xlsx', 'nama_berkas' => 'x.xlsx']);
    expect($job->baris_sukses)->toBe(1)->and($job->baris_gagal)->toBe(2);

    expect(fn () => app(PratinjauImporRkat::class)->jalankan(['tahun' => 2020, 'path_absolut' => $berkas, 'path' => 'i/x.xlsx', 'nama_berkas' => 'x.xlsx']))
        ->toThrow(ValidationException::class, 'Tahun 2020');
    expect(fn () => app(PratinjauImporRkat::class)->jalankan(['tahun' => 2021, 'path_absolut' => berkasSintetis([['1.1', 'x', 1]], 'Lembar1'), 'path' => 'i/x.xlsx', 'nama_berkas' => 'x.xlsx']))
        ->toThrow(ValidationException::class, 'Template tidak dikenali');

    $disahkan = RkatFixture::versi(2021, 'disahkan');
    $ok = app(PratinjauImporRkat::class)->jalankan(['tahun' => 2021, 'path_absolut' => berkasSintetis([['1.1', 'x', 1]]), 'path' => 'i/x.xlsx', 'nama_berkas' => 'x.xlsx']);
    expect(fn () => app(TerapkanImporRkat::class)->jalankan(['import_job_id' => $ok->id, 'rkat_version_id' => $disahkan->id]))
        ->toThrow(ValidationException::class, 'terkunci');
});
