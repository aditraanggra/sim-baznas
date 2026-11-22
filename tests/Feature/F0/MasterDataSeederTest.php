<?php

use App\Models\Asnaf;
use App\Models\BentukBantuan;
use App\Models\Bidang;
use App\Models\DanaKelompok;
use App\Models\Desa;
use App\Models\JenisDana;
use App\Models\JenisDokumen;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\MetodeBayar;
use App\Models\Program;
use App\Models\Provinsi;
use App\Models\RekeningBank;
use App\Models\StatusDokumen;
use App\Models\SumberDana;
use Database\Seeders\BidangSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\WilayahSeeder;

it('F0-08: BidangSeeder menanam 4 bidang dengan warna PRD secara idempoten', function () {
    $this->seed(BidangSeeder::class);
    $this->seed(BidangSeeder::class);

    expect(Bidang::count())->toBe(4)
        ->and(Bidang::where('kode', 'pengumpulan')->value('warna'))->toBe('#259148')
        ->and(Bidang::where('kode', 'distribusi')->value('warna'))->toBe('#FDC727')
        ->and(Bidang::where('kode', 'umum')->value('warna'))->toBe('#1D4ED8')
        ->and(Bidang::where('kode', 'pkp')->value('warna'))->toBe('#5B21B6');
});

it('F0-09 K13: WilayahSeeder menanam 32 kecamatan dan 360 desa Kab. Cianjur secara idempoten', function () {
    $this->seed(WilayahSeeder::class);
    $this->seed(WilayahSeeder::class);

    expect(Provinsi::count())->toBe(1)
        ->and(Kabupaten::count())->toBe(1)
        ->and(Kabupaten::where('kode', '32.03')->value('nama'))->toBe('KABUPATEN CIANJUR')
        ->and(Kecamatan::count())->toBe(32)
        ->and(Desa::count())->toBe(360)
        ->and(Desa::where('nama', 'SINARLAUT')->exists())->toBeTrue()
        ->and(Desa::where('nama', 'CIGUNUNGHERANG')->exists())->toBeTrue();
});

it('F0-10 K10 K11 K14: MasterDanaSeeder menanam 4 jenis dana, 6 kelompok dana, 9 sumber dana, 8 asnaf, dan 7 program secara idempoten', function () {
    $this->seed(MasterDanaSeeder::class);
    $this->seed(MasterDanaSeeder::class);

    expect(JenisDana::count())->toBe(4)
        ->and(JenisDana::where('kode', 'ZAKAT')->value('wajib_asnaf'))->toBeTrue()
        ->and(JenisDana::where('kode', '!=', 'ZAKAT')->where('wajib_asnaf', true)->count())->toBe(0)
        ->and(DanaKelompok::count())->toBe(6)
        ->and(SumberDana::count())->toBe(9)
        ->and(SumberDana::pluck('kode')->sort()->values()->all())
        ->toBe(['CSR', 'FDY', 'IFT', 'IFTT', 'KA', 'KFT', 'KRB', 'ZF', 'ZM'])
        ->and(Asnaf::count())->toBe(8)
        ->and(Program::count())->toBe(7)
        ->and(Program::where('sub_bidang', 'pendistribusian')->count())->toBe(4)
        ->and(Program::where('sub_bidang', 'pendayagunaan')->count())->toBe(3);
});

it('F0-11: MasterOperasionalSeeder menanam metode bayar, rekening bank, jenis dokumen, status dokumen, dan bentuk bantuan secara idempoten', function () {
    $this->seed(MasterDanaSeeder::class);
    $this->seed(MasterOperasionalSeeder::class);
    $this->seed(MasterOperasionalSeeder::class);

    expect(MetodeBayar::count())->toBe(4)
        ->and(RekeningBank::count())->toBe(2)
        ->and(JenisDokumen::count())->toBe(7)
        ->and(StatusDokumen::where('kelompok', 'permohonan_publik')->count())->toBe(7)
        ->and(StatusDokumen::where('kelompok', 'permohonan')->count())->toBe(8)
        ->and(StatusDokumen::where('kelompok', 'surat_masuk')->count())->toBe(5)
        ->and(StatusDokumen::where('kelompok', 'surat_keluar')->count())->toBe(4)
        ->and(BentukBantuan::count())->toBe(2);
});
