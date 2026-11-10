<?php

use App\Services\Rkat\HitungRkat;
use App\Services\Rkat\ValidasiRkat;
use Tests\Concerns\RkatFixture;

beforeEach(fn () => RkatFixture::siapkan());

function hasilVal(array $ubah = [], ?array $batas = null): array
{
    static $tahun = 3000;
    $versi = RkatFixture::versi($tahun++, 'draft', $ubah);
    $n = HitungRkat::untukVersi($versi)['per_kode'];
    $b = $batas ?? ['zakat' => 1250, 'infak' => 2000, 'csr' => 1000, 'dskl' => 0];

    return collect(ValidasiRkat::evaluasi($n, $b))->keyBy('id')->all();
}

it('VAL-1: total penerimaan L2 sama dengan total penyaluran L3 (22.015.000.000)', function () {
    $r = hasilVal()['VAL-1'];
    expect($r['lulus'])->toBeTrue()->and($r['kiri'])->toBe(22_015_000_000)->and($r['kanan'])->toBe(22_015_000_000);

    $gagal = hasilVal(['L3/1.1' => 500_000_001])['VAL-1'];
    expect($gagal['lulus'])->toBeFalse()->and($gagal['selisih'])->toBe(-1);
});

it('VAL-2: pendistribusian + pendayagunaan L4 sama dengan penerimaan dikurangi hak amil (18.489.764.000)', function () {
    $r = hasilVal()['VAL-2'];
    expect($r['lulus'])->toBeTrue()->and($r['kiri'])->toBe(18_489_764_000);
    expect(12_898_442_400 + 5_591_321_600)->toBe(18_489_764_000);

    expect(hasilVal(['L4/2.1' => 2_391_321_601])['VAL-2']['lulus'])->toBeFalse();
});

it('VAL-3: bagian amil L6 sama dengan penyaluran untuk amil L3 (3.525.236.000)', function () {
    $r = hasilVal()['VAL-3'];
    expect($r['lulus'])->toBeTrue()->and($r['kanan'])->toBe(3_525_236_000);

    expect(hasilVal(['L6/1.1' => 1_325_440_001])['VAL-3']['lulus'])->toBeFalse();
});

it('VAL-4: dana operasional L6 sama dengan hak amil + hibah operasional (3.775.236.000)', function () {
    $r = hasilVal()['VAL-4'];
    expect($r['lulus'])->toBeTrue()->and($r['kiri'])->toBe(3_775_236_000);

    // Infak Operasional L6/1.5 tidak ada di rumus PRD, jadi nilainya membuat VAL-4 gagal (open-questions).
    expect(hasilVal(['L6/1.5' => 950_000_000])['VAL-4']['lulus'])->toBeFalse();
});

it('VAL-5: total mustahik dan orang sama dengan rincian (35.372 dan 201.430)', function () {
    $r = hasilVal()['VAL-5'];
    expect($r['lulus'])->toBeTrue()
        ->and($r['rincian']['mustahik']['total'])->toBe(35_372)
        ->and($r['rincian']['orang']['total'])->toBe(201_430);

    $n = HitungRkat::untukVersi(RkatFixture::versi(3999))['per_kode'];
    expect($n['L5/1.2'])->toBe(29);

    $n['L5/2'] += 1;
    $gagal = collect(ValidasiRkat::evaluasi($n, ['zakat' => 1250, 'infak' => 2000, 'csr' => 1000, 'dskl' => 0]))->firstWhere('id', 'VAL-5');
    expect($gagal['lulus'])->toBeFalse();
});

it('VAL-6: bagian amil per jenis dana tidak melebihi batas (zakat 12,5; infak 20; CSR 10; DSKL 0)', function () {
    $r = hasilVal()['VAL-6'];
    expect($r['lulus'])->toBeTrue()
        ->and($r['rincian']['zakat']['lulus'])->toBeTrue()
        ->and($r['rincian']['infak']['lulus'])->toBeTrue();

    // Zakat tepat di batas lulus, lebih 1 rupiah gagal (aritmetika bilangan bulat).
    expect(hasilVal(['L3/1.3' => 1_325_440_001])['VAL-6']['rincian']['zakat']['lulus'])->toBeFalse();

    // DSKL batas 0: nilai amil > 0 gagal, rasio n/a bila penerimaan nol.
    $dskl = hasilVal(['L3/8.2' => 1, 'L2/5.1' => 0])['VAL-6']['rincian']['dskl'];
    expect($dskl['lulus'])->toBeFalse()->and(HitungRkat::tampil($dskl['rasio_persen']))->toBe('n/a');

    // Batas belum diisi = tidak dapat dievaluasi = gagal.
    $kosong = hasilVal([], ['zakat' => null, 'infak' => 2000, 'csr' => 1000, 'dskl' => 0])['VAL-6'];
    expect($kosong['lulus'])->toBeFalse()->and($kosong['rincian']['zakat']['keterangan'])->toBe('Batas belum diisi');
});

it('VAL-1..6: semua lulus pada RKAT 2026 dan nilai kontrol lain cocok', function () {
    $hasil = HitungRkat::untukVersi(RkatFixture::versi(3998));
    $semua = ValidasiRkat::evaluasi($hasil['per_kode'], ['zakat' => 1250, 'infak' => 2000, 'csr' => 1000, 'dskl' => 0]);
    expect(ValidasiRkat::semuaLulus($semua))->toBeTrue()->and($semua)->toHaveCount(6);

    $n = $hasil['per_kode'];
    expect($n['L2/LN/1.1'] + $n['L2/LN/1.2'] + $n['L2/LN/1.3'] + $n['L2/LN/1.4'])->toBe(137_959_000_000);
});
