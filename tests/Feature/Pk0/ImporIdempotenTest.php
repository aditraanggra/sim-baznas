<?php

namespace Tests\Feature\Pk0;

use App\Actions\Impor\MulaiImpor;
use App\Models\ImportJob;
use App\Models\ImportRow;
use App\Models\PosAnggaran;
use App\Models\User;
use Database\Seeders\PeranSeeder;
use Database\Seeders\PosAnggaranSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImporIdempotenTest extends TestCase
{
    use RefreshDatabase;

    /** Impor idempoten: impor dua kali dengan berkas sama tidak menambah baris. */
    public function test_impor_idempoten_berkas_sama_dua_kali(): void
    {
        $this->seed(PeranSeeder::class);
        $gudang = sys_get_temp_dir().'/pkp-impor-uji';
        @mkdir($gudang);
        $berkas = $gudang.'/realisasi.xlsx';

        // Berkas minimal: header + 1 baris.
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['tahun', 'bulan', 'kode_pos', 'tipe_nilai', 'nilai'],
            [2026, 1, 'L2/1.1', 'rupiah', 35000000],
        ]);
        (new Xlsx($spreadsheet))->save($berkas);

        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $kirim = fn () => $this->post('/pengaturan/impor', [
            'jenis' => 'realisasi',
            'berkas' => new UploadedFile($berkas, 'realisasi.xlsx', null, null, true),
        ]);

        $kirim()->assertRedirect();
        $kirim()->assertRedirect();

        $this->assertSame(2, ImportJob::count(), 'dua job tercatat (riwayat)');
        $this->assertSame(1, ImportRow::count(), 'satu baris unik per kunci, bukan per job');

        $baris = ImportRow::firstOrFail();
        $this->assertSame('valid', $baris->status);
        $this->assertSame('2026/1/L2/1.1/rupiah', $baris->kunci);
    }

    public function test_baris_galat_ditandai_per_baris(): void
    {
        $job = app(MulaiImpor::class)->jalankan([
            'jenis' => 'rkat',
            'nama_berkas' => 'uji.xlsx',
            'path' => 'impor/uji.xlsx',
            'baris' => [
                ['nomor' => 1, 'kunci' => 'L2/1.1|rupiah', 'data' => ['kode_pos' => 'L2/1.1', 'tipe_nilai' => 'rupiah', 'nilai' => 100]],
                ['nomor' => 2, 'kunci' => 'L2/1.2|rupiah', 'data' => ['kode_pos' => 'L2/1.2', 'tipe_nilai' => 'rupiah', 'nilai' => 'x']],
                ['nomor' => 3, 'kunci' => 'L2/1.3|rupiah', 'data' => ['kode_pos' => 'L2/1.3', 'nilai' => 5]],
            ],
        ]);

        $this->assertSame(1, $job->baris_sukses);
        $this->assertSame(2, $job->baris_gagal);
        $this->assertSame(['nilai'], array_keys($job->rows->firstWhere('nomor_baris', 2)->galat));
    }

    public function test_seed_pos_baku_lengkap_dan_idempoten(): void
    {
        $this->seed(PosAnggaranSeeder::class);
        $this->seed(PosAnggaranSeeder::class);

        $this->assertSame(112, PosAnggaran::count(), 'fixture 112 pos, seed ulang tidak menggandakan');
        $this->assertSame(27, PosAnggaran::whereNull('induk_id')->count());

        // Kontrol: jumlah pos rupiah Lampiran 2 = VAL-1.
        $penerimaan = PosAnggaran::where('kode', 'L2/1')->where('tipe_nilai', 'rupiah')->firstOrFail();
        $this->assertSame('L2/1', $penerimaan->path);
        $this->assertSame('jumlah_anak', $penerimaan->agregasi);

        $anak = PosAnggaran::where('induk_id', $penerimaan->id)->get();
        $this->assertTrue($anak->every(fn ($p) => str_starts_with($p->path, 'L2/1/')));
    }
}
