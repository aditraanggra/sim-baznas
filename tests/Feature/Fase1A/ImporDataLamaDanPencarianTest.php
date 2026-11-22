<?php

use App\Actions\Distribusi\SimpanMustahik;
use App\Actions\Impor\MulaiImpor;
use App\Actions\Pengumpulan\SimpanMuzakki;
use App\Actions\Upz\SimpanUpz;
use App\Models\Desa;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\JenisUpz;
use App\Models\Mustahik;
use App\Models\Muzakki;
use App\Models\Upz;
use App\Models\User;
use Database\Seeders\BidangSeeder;
use Database\Seeders\JenisUpzSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed([PeranSeeder::class, BidangSeeder::class, WilayahSeeder::class, JenisUpzSeeder::class, MasterDanaSeeder::class]);
});

function buatBerkasExcel(array $baris, string $nama): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    foreach ($baris as $rowIdx => $cols) {
        foreach ($cols as $colIdx => $val) {
            $cell = Coordinate::stringFromColumnIndex($colIdx + 1).($rowIdx + 1);
            $sheet->setCellValueExplicit($cell, (string) $val, DataType::TYPE_STRING);
        }
    }

    $tmp = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($spreadsheet))->save($tmp);

    return new UploadedFile(
        $tmp,
        $nama,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}

it('X1-02: impor data lama muzakki, mustahik, dan upz berjalan idempoten tanpa duplikasi', function () {
    $admin = User::factory()->create([
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_confirmed_at' => now(),
    ])->assignRole(['admin', 'amil']);

    $desa = Desa::firstOrFail();

    $excelMuzakki = buatBerkasExcel([
        ['kode', 'jenis', 'nama', 'nik', 'telepon', 'desa'],
        ['MZK-IMP-01', 'perorangan', 'H. Hasanudin Lama', '3203010101800011', '081111112222', $desa->nama],
        ['MZK-IMP-02', 'perorangan', '', '123', '0811', $desa->nama],
    ], 'muzakki-lama.xlsx');

    $this->actingAs($admin)
        ->post(route('pengaturan.impor.simpan'), ['jenis' => 'muzakki', 'berkas' => $excelMuzakki])
        ->assertRedirect();

    expect(Muzakki::where('kode', 'MZK-IMP-01')->count())->toBe(1);
    $job1 = ImportJob::where('jenis', 'muzakki')->latest('id')->firstOrFail();
    expect($job1->baris_sukses)->toBe(1)->and($job1->baris_gagal)->toBe(1);

    // Impor ulang berkas identik harus melakukan update idempoten, bukan menambah baris duplikat.
    $excelMuzakkiUlang = buatBerkasExcel([
        ['kode', 'jenis', 'nama', 'nik', 'telepon', 'desa'],
        ['MZK-IMP-01', 'perorangan', 'H. Hasanudin Pembaruan', '3203010101800011', '081111112222', $desa->nama],
    ], 'muzakki-lama-2.xlsx');

    $this->actingAs($admin)
        ->post(route('pengaturan.impor.simpan'), ['jenis' => 'muzakki', 'berkas' => $excelMuzakkiUlang])
        ->assertRedirect();

    expect(Muzakki::where('kode', 'MZK-IMP-01')->count())->toBe(1)
        ->and(Muzakki::where('kode', 'MZK-IMP-01')->first()->nama)->toBe('H. Hasanudin Pembaruan')
        ->and(ImportRow::where('jenis', 'muzakki')->where('status', 'valid')->count())->toBe(1);

    // Mustahik & UPZ langsung lewat MulaiImpor juga idempoten.
    $this->actingAs($admin);
    app(MulaiImpor::class)->jalankan([
        'jenis' => 'mustahik',
        'nama_berkas' => 'mustahik.xlsx',
        'path' => 'impor/m.xlsx',
        'baris' => [[
            'nomor' => 2,
            'kunci' => 'MST-IMP-01',
            'data' => ['kode' => 'MST-IMP-01', 'nama' => 'Siti Aminah Lama', 'asnaf' => 'FAKIR,MISKIN'],
        ]],
    ]);
    app(MulaiImpor::class)->jalankan([
        'jenis' => 'mustahik',
        'nama_berkas' => 'mustahik2.xlsx',
        'path' => 'impor/m2.xlsx',
        'baris' => [[
            'nomor' => 2,
            'kunci' => 'MST-IMP-01',
            'data' => ['kode' => 'MST-IMP-01', 'nama' => 'Siti Aminah Revisi', 'asnaf' => 'FAKIR'],
        ]],
    ]);

    expect(Mustahik::where('kode', 'MST-IMP-01')->count())->toBe(1)
        ->and(Mustahik::where('kode', 'MST-IMP-01')->first()->asnaf()->count())->toBe(1);

    app(MulaiImpor::class)->jalankan([
        'jenis' => 'upz',
        'nama_berkas' => 'upz.xlsx',
        'path' => 'impor/u.xlsx',
        'baris' => [[
            'nomor' => 2,
            'kunci' => 'UPZ-IMP-01',
            'data' => ['kode' => 'UPZ-IMP-01', 'nama' => 'UPZ Masjid Agung Lama', 'jenis_upz' => 'DKM', 'desa' => $desa->nama],
        ]],
    ]);

    expect(Upz::where('kode', 'UPZ-IMP-01')->count())->toBe(1);
});

it('X1-03: pencarian global mematuhi cakupan peran dan isolasi UPZ', function () {
    $stafDist = User::factory()->create()->assignRole(['staf_pendistribusian', 'amil']);
    $stafPengumpul = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $stafUmum = User::factory()->create()->assignRole(['admin_arsip', 'amil']);

    $this->actingAs($stafPengumpul);
    app(SimpanMuzakki::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Cianjur Muzakki Rahasia',
    ]);

    $this->actingAs($stafDist);
    app(SimpanMustahik::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Cianjur Mustahik Target',
    ]);

    $jenis = JenisUpz::firstOrFail();
    $desa = Desa::firstOrFail();
    $this->actingAs($stafUmum);
    $upz1 = app(SimpanUpz::class)->jalankan([
        'nama' => 'Cianjur UPZ Satu',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'Ketua Satu',
        'alamat' => 'Cianjur',
        'pic_nama' => 'PIC Satu',
        'pic_telepon' => '081111111111',
    ]);
    app(SimpanUpz::class)->jalankan([
        'nama' => 'Cianjur UPZ Rahasia Dua',
        'jenis_upz_id' => $jenis->id,
        'desa_id' => $desa->id,
        'ketua' => 'Ketua Dua',
        'alamat' => 'Cianjur',
        'pic_nama' => 'PIC Dua',
        'pic_telepon' => '082222222222',
    ]);

    // Staf pengumpulan melihat Muzakki, tidak melihat Mustahik.
    $resPengumpul = $this->actingAs($stafPengumpul)->getJson(route('pencarian-global', ['q' => 'Cianjur']))
        ->assertOk()
        ->json('hasil');

    $kategoriPengumpul = collect($resPengumpul)->pluck('kategori')->all();
    expect($kategoriPengumpul)->toContain('Muzakki')
        ->not->toContain('Mustahik');

    // Akun UPZ hanya melihat UPZ miliknya.
    $akunUpz = User::factory()->create(['upz_id' => $upz1->id])->assignRole('upz');
    $resUpz = $this->actingAs($akunUpz)->getJson(route('pencarian-global', ['q' => 'Cianjur']))
        ->assertOk()
        ->json('hasil');

    expect(collect($resUpz)->pluck('judul')->all())
        ->toContain('Cianjur UPZ Satu')
        ->not->toContain('Cianjur UPZ Rahasia Dua');
});
