<?php

use App\Models\RkatValue;
use App\Models\User;
use App\Services\Rkat\HitungRkat;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\Concerns\RkatFixture;

beforeEach(function () {
    RkatFixture::siapkan();
    $user = User::factory()->create();
    $user->assignRole('pimpinan');
    test()->actingAs($user);
});

function bacaEkspor($respons): Spreadsheet
{
    return IOFactory::load($respons->baseResponse->getFile()->getPathname());
}

/** @return list<list<mixed>> */
function barisSheet(Spreadsheet $s, string $nama): array
{
    return $s->getSheetByName($nama)->toArray(null, false, false, false);
}

function barisData(array $rows): array
{
    $awal = array_search('No', array_column($rows, 0), true);

    return array_slice($rows, $awal + 1);
}

function totalPuncakSheet(array $rows): int
{
    return array_sum(array_map(
        fn ($r) => str_contains((string) $r[0], '.') || ! is_numeric($r[0]) ? 0 : (int) $r[3],
        barisData($rows),
    ));
}

it('FR-R-08: ekspor Excel memuat satu sheet per lampiran dengan nilai kontrol RKAT 2026', function () {
    $versi = RkatFixture::versi();
    $respons = $this->get(route('pkp.rencana.ekspor.excel', $versi))->assertOk();
    $xlsx = bacaEkspor($respons);

    expect($respons->headers->get('content-disposition'))->toContain('RKAT-2026-awal.xlsx')
        ->and($xlsx->getSheetNames())->toBe(['L2 Neraca', 'L2 Luar Neraca', 'L3', 'L4', 'L5', 'L6', 'L7']);

    $neraca = barisSheet($xlsx, 'L2 Neraca');
    expect($neraca[0][0])->toBe('Lampiran 2 - Rencana Penerimaan')
        ->and($neraca[1][0])->toBe('RKAT 2026 - Awal - Draft')
        ->and(array_slice($neraca[3], 0, 5))->toBe(['No', 'Uraian', 'Persen', 'Jumlah', 'Satuan'])
        ->and(totalPuncakSheet($neraca))->toBe(22_015_000_000)
        ->and(array_sum(array_column(barisData(barisSheet($xlsx, 'L2 Luar Neraca')), 3)))->toBe(137_959_000_000);

    $l5 = collect(barisData(barisSheet($xlsx, 'L5')))->keyBy(0);
    expect((int) $l5['1'][3])->toBe(201_459)
        ->and($l5['1'][4])->toBe('orang')
        ->and($l5['1.2'][4])->toBe('badan')
        ->and((int) $l5['1.2'][3])->toBe(29)
        ->and($l5['1'][2])->toBe('n/a')
        ->and((int) $l5['1'][3])->toBe(HitungRkat::untukVersi($versi)['per_kode']['L5/1']);
    $rowsL5 = barisSheet($xlsx, 'L5');
    $sheetL5 = $xlsx->getSheetByName('L5');
    $induk = array_search('1', array_column($rowsL5, 0), true) + 1;
    $anak = array_search('1.1', array_column($rowsL5, 0), true) + 1;
    expect($sheetL5->getStyle("A{$induk}")->getFont()->getBold())->toBeTrue()
        ->and($sheetL5->getStyle("A{$anak}")->getFont()->getBold())->toBeFalse()
        ->and($sheetL5->getStyle("B{$anak}")->getAlignment()->getIndent())->toBeGreaterThan(0)
        ->and($sheetL5->getStyle("D{$anak}")->getNumberFormat()->getFormatCode())->toBe('#,##0')
        ->and($sheetL5->getCell("D{$anak}")->getValue())->toBe(176434);
});

it('FR-R-08: ekspor PDF menghasilkan berkas PDF valid', function () {
    $versi = RkatFixture::versi();
    $respons = $this->get(route('pkp.rencana.ekspor.pdf', $versi))->assertOk();

    expect($respons->headers->get('content-type'))->toContain('application/pdf')
        ->and($respons->headers->get('content-disposition'))->toContain('RKAT-2026-awal.pdf')
        ->and(substr($respons->getContent(), 0, 4))->toBe('%PDF');
});

it('BR-10: pos tak terpetakan tidak ikut ekspor', function () {
    $versi = RkatFixture::versi();
    RkatValue::create([
        'rkat_version_id' => $versi->id, 'pos_anggaran_id' => null, 'kode_asal' => 'X/9',
        'nama_asal' => 'Pos Asing', 'tipe_nilai' => 'rupiah', 'nilai' => 5_000_000,
    ]);

    $xlsx = bacaEkspor($this->get(route('pkp.rencana.ekspor.excel', $versi))->assertOk());

    expect(totalPuncakSheet(barisSheet($xlsx, 'L2 Neraca')))->toBe(22_015_000_000);

    $semua = collect($xlsx->getSheetNames())->flatMap(fn ($n) => barisSheet($xlsx, $n))->flatten()->filter();
    $lainnya = $semua->filter(fn ($sel) => str_contains((string) $sel, 'Pos Asing'));
    expect($lainnya)->toHaveCount(1)
        ->and($lainnya->first())->toBe('X/9 Pos Asing: 5.000.000')
        ->and(collect(barisData(barisSheet($xlsx, 'L7')))->pluck(1)->filter(fn ($u) => str_contains((string) $u, 'Pos Asing')))->toBeEmpty();
});

it('FR-R-08: persen dibulatkan hanya saat tampil', function () {
    $versi = RkatFixture::versi();
    $xlsx = bacaEkspor($this->get(route('pkp.rencana.ekspor.excel', $versi))->assertOk());
    $baris = collect(barisData(barisSheet($xlsx, 'L2 Neraca')))->keyBy(0)['1'];

    $mentah = HitungRkat::persen(10_603_520_000, 22_015_000_000);
    expect($mentah)->not->toBe(round($mentah, 1))
        ->and($baris[2])->toBe('48,2%')
        ->and((int) $baris[3])->toBe(10_603_520_000);
});
