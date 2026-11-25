<?php

use App\Actions\Pengumpulan\CetakKwitansi;
use App\Actions\Pengumpulan\FinalkanPenerimaan;
use App\Actions\Pengumpulan\SimpanDraftPenerimaan;
use App\Actions\Pengumpulan\SimpanMuzakki;
use App\Models\KwitansiCetakLog;
use App\Models\MetodeBayar;
use App\Models\Muzakki;
use App\Models\Penerimaan;
use App\Models\RekeningBank;
use App\Models\SumberDana;
use App\Models\User;
use Database\Seeders\BidangSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([
        PeranSeeder::class,
        BidangSeeder::class,
        WilayahSeeder::class,
        MasterDanaSeeder::class,
        MasterOperasionalSeeder::class,
    ]);
});

it('PGM-T01 Q-11: SimpanMuzakki mengenkripsi NIK, menghasilkan hash unik + masking, dan mendeteksi duplikat telepon', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $this->actingAs($staf);

    $action = app(SimpanMuzakki::class);
    $muzakki = $action->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'H. Abdullah',
        'npwz' => '320300012345',
        'jenis_kelamin' => 'L',
        'nik' => '3203011205800001',
        'telepon' => '0813-1234-5678',
    ]);

    expect($muzakki->nik_masked)->toBe('3203**********01')
        ->and($muzakki->npwz)->toBe('320300012345')
        ->and($muzakki->jenis_kelamin)->toBe('L')
        ->and($muzakki->nik_hash)->not->toBeNull()
        ->and($muzakki->nik_encrypted)->not->toContain('3203011205800001')
        ->and($muzakki->telepon_norm)->toBe('081312345678');

    // NPWZ unik tidak boleh ganda.
    expect(fn () => $action->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Muzakki NPWZ Sama',
        'npwz' => '320300012345',
    ]))->toThrow(ValidationException::class);

    // Lembaga otomatis mengosongkan jenis_kelamin.
    $lembaga = $action->jalankan([
        'jenis' => 'lembaga',
        'nama' => 'PT Maju Berkah',
        'npwz' => '320300099999',
        'jenis_kelamin' => 'L',
    ]);
    expect($lembaga->jenis_kelamin)->toBeNull();

    $duplikat = $action->cekKemungkinanDuplikat(null, '+6281312345678');
    expect($duplikat)->toHaveCount(1)
        ->and($duplikat[0]['id'])->toBe($muzakki->id);

    // NIK identik ditolak agar tidak terjadi duplikasi.
    expect(fn () => $action->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Abdullah Ganda',
        'nik' => '3203011205800001',
    ]))->toThrow(ValidationException::class);

    expect(Muzakki::count())->toBe(2);
});

it('PGM-T03 & PGM-T04 HTTP: simpan draft muncul di daftar, error tanpa muzakki validasi field tepat, dan final + cetak kwitansi berjalan', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $this->actingAs($staf);

    $muzakki = app(SimpanMuzakki::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Budi Muzakki',
        'npwz' => '320300055511',
        'jenis_kelamin' => 'L',
    ]);
    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $zm = SumberDana::where('kode', 'ZM')->firstOrFail();

    // Tanpa muzakki_id harus mengembalikan validation error pada muzakki_id (bukan 'id').
    $this->post(route('pengumpulan.penerimaan.final'), [
        'id' => null,
        'tanggal' => '2026-11-24',
        'muzakki_id' => null,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 250_000, 'keterangan' => 'Zakat']],
    ])->assertSessionHasErrors(['muzakki_id']);

    // Simpan draft redirect ke daftar penerimaan dan tampil dengan status draft.
    $this->post(route('pengumpulan.penerimaan.draft'), [
        'id' => null,
        'tanggal' => '2026-11-24',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 500_000, 'keterangan' => 'Draft zakat']],
    ])->assertRedirect(route('pengumpulan.penerimaan.index'));

    $this->get(route('pengumpulan.penerimaan.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('pengumpulan/penerimaan/index')
            ->has('penerimaan.data', 1)
            ->where('penerimaan.data.0.status', 'draft')
            ->where('penerimaan.data.0.muzakki', 'Budi Muzakki'));

    $draft = Penerimaan::firstOrFail();

    // Finalkan draft -> redirect ke show?cetak=1 & cetak kwitansi mengembalikan PDF.
    $resFinal = $this->post(route('pengumpulan.penerimaan.final'), [
        'id' => $draft->id,
        'tanggal' => '2026-11-24',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 500_000, 'keterangan' => 'Zakat Mal']],
    ]);
    $resFinal->assertRedirect(route('pengumpulan.penerimaan.show', [$draft->id, 'cetak' => 1]));

    $pdfRes = $this->get(route('pengumpulan.penerimaan.kwitansi', $draft->id));
    $pdfRes->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($pdfRes->getContent())->toStartWith('%PDF-');
});

it('PGM-T03, PGM-T05 & PGM-T06: simpan draft lalu finalkan penerimaan menerbitkan kwitansi unik, log cetak ulang, dan verifikasi publik tanpa identitas muzakki', function () {
    $staf = User::factory()->create()->assignRole(['pelayanan_muzakki', 'amil']);
    $this->actingAs($staf);

    $muzakki = app(SimpanMuzakki::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Ibu Rahmawati Rahasia',
        'nik' => '3203014506850002',
    ]);

    $tunai = MetodeBayar::where('kode', 'TUNAI')->firstOrFail();
    $transfer = MetodeBayar::where('kode', 'TRANSFER')->firstOrFail();
    $rekening = RekeningBank::firstOrFail();
    $zm = SumberDana::where('kode', 'ZM')->firstOrFail();
    $iftt = SumberDana::where('kode', 'IFTT')->firstOrFail();

    // Transfer wajib memilih rekening.
    expect(fn () => app(SimpanDraftPenerimaan::class)->jalankan([
        'tanggal' => '2026-11-22',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $transfer->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 1_000_000]],
    ]))->toThrow(ValidationException::class);

    $draft = app(SimpanDraftPenerimaan::class)->jalankan([
        'tanggal' => '2026-11-22',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $transfer->id,
        'rekening_id' => $rekening->id,
        'items' => [
            ['sumber_dana_id' => $zm->id, 'jumlah' => 2_500_000, 'keterangan' => 'Zakat Mal'],
            ['sumber_dana_id' => $iftt->id, 'jumlah' => 500_000, 'keterangan' => 'Infak'],
        ],
    ]);

    expect($draft->status)->toBe('draft')
        ->and($draft->nomor)->toBeNull()
        ->and($draft->totalRupiah())->toBe(3_000_000);

    $final = app(FinalkanPenerimaan::class)->jalankan(['id' => $draft->id]);

    expect($final->status)->toBe('final')
        ->and($final->nomor)->toContain('PNR/')
        ->and($final->kwitansi)->not->toBeNull()
        ->and($final->kwitansi->nomor)->toContain('KW/');

    // Cetak ke-1 dan ke-2 tercatat di log.
    $cetak1 = app(CetakKwitansi::class)->jalankan(['penerimaan_id' => $final->id]);
    $cetak2 = app(CetakKwitansi::class)->jalankan(['penerimaan_id' => $final->id]);

    expect($cetak1['cetak_ke'])->toBe(1)
        ->and($cetak2['cetak_ke'])->toBe(2)
        ->and($cetak2['pdf']->output())->toStartWith('%PDF-')
        ->and(KwitansiCetakLog::where('kwitansi_id', $final->kwitansi->id)->count())->toBe(2);

    // Tidak bisa diedit kembali setelah final.
    expect(fn () => app(SimpanDraftPenerimaan::class)->jalankan([
        'id' => $final->id,
        'tanggal' => '2026-11-22',
        'muzakki_id' => $muzakki->id,
        'metode_bayar_id' => $tunai->id,
        'items' => [['sumber_dana_id' => $zm->id, 'jumlah' => 100_000]],
    ]))->toThrow(ValidationException::class);

    // PGM-T06: halaman publik verifikasi kwitansi menampilkan nomor & nominal, tanpa nama muzakki.
    $this->get(route('publik.verifikasi-kwitansi', $final->kwitansi->token_verifikasi))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('publik/verifikasi-kwitansi')
            ->where('ditemukan', true)
            ->where('data.nomor', $final->kwitansi->nomor)
            ->where('data.total_rupiah', 3_000_000))
        ->assertDontSee('Rahmawati Rahasia')
        ->assertDontSee('3203**********02');
});
