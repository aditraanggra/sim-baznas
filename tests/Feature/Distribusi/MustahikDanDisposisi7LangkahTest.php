<?php

use App\Actions\Distribusi\BuatPermohonan;
use App\Actions\Distribusi\ProsesLangkahPermohonan;
use App\Actions\Distribusi\SimpanMustahik;
use App\Actions\Distribusi\UnggahDokumenMustahik;
use App\Actions\Distribusi\VerifikasiMustahik;
use App\Models\Asnaf;
use App\Models\AuditLog;
use App\Models\Desa;
use App\Models\JenisDana;
use App\Models\JenisDokumen;
use App\Models\Penyaluran;
use App\Models\Permohonan;
use App\Models\Program;
use App\Models\User;
use App\Services\IdentitasPribadiService;
use Database\Seeders\BidangSeeder;
use Database\Seeders\MasterDanaSeeder;
use Database\Seeders\MasterOperasionalSeeder;
use Database\Seeders\PeranSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed([
        PeranSeeder::class,
        BidangSeeder::class,
        WilayahSeeder::class,
        MasterDanaSeeder::class,
        MasterOperasionalSeeder::class,
    ]);
});

it('PDP-T01 & PDP-T02: mustahik menyimpan NIK terenkripsi, multi-asnaf, dokumen privat, dan verifikasi', function () {
    Storage::fake('private');

    $petugas = User::factory()->create()->assignRole(['pelayanan_mustahik', 'amil']);
    $this->actingAs($petugas);

    $desa = Desa::firstOrFail();
    $fakir = Asnaf::where('kode', 'ilike', 'fakir')->firstOrFail();
    $miskin = Asnaf::where('kode', 'ilike', 'miskin')->firstOrFail();
    $ktp = JenisDokumen::where('kode', 'KTP')->firstOrFail();

    $mustahik = app(SimpanMustahik::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Siti Aminah',
        'nik' => '3203014405850005',
        'telepon' => '0812-3456-7890',
        'desa_id' => $desa->id,
        'alamat' => 'Kp. Cianjur Selatan',
        'asnaf_ids' => [$fakir->id, $miskin->id],
        'asnaf_utama_id' => $fakir->id,
    ]);

    expect($mustahik->kode)->toStartWith('MST-')
        ->and($mustahik->nik_masked)->toBe('3203**********05')
        ->and(app(IdentitasPribadiService::class)->dekripsi($mustahik->nik_encrypted))->toBe('3203014405850005')
        ->and($mustahik->asnaf()->count())->toBe(2);

    $duplikat = app(SimpanMustahik::class)->cekKemungkinanDuplikat(null, '+62 812 3456 7890');
    expect($duplikat)->toHaveCount(1)
        ->and($duplikat[0]['alasan'])->toContain('Telepon sama');

    $dok = app(UnggahDokumenMustahik::class)->jalankan([
        'mustahik_id' => $mustahik->id,
        'jenis_dokumen_id' => $ktp->id,
        'berkas' => UploadedFile::fake()->create('ktp-siti.pdf', 120, 'application/pdf'),
    ]);

    expect(Storage::disk('private')->exists($dok->berkasPrivat->path))->toBeTrue();

    $verified = app(VerifikasiMustahik::class)->jalankan([
        'mustahik_id' => $mustahik->id,
        'status_verifikasi' => 'terverifikasi',
        'catatan_verifikasi' => 'Dokumen lengkap dan layak dibantu.',
    ]);

    expect($verified->status_verifikasi)->toBe('terverifikasi')
        ->and(AuditLog::where('entitas_tipe', get_class($mustahik))->where('entitas_id', $mustahik->id)->count())->toBeGreaterThanOrEqual(2);
});

it('PDP-T03, PDP-T04 & PDP-T05 K8 Q-14: rute permohonan 7 langkah dan gerbang kesiapan Keuangan ditegakkan', function () {
    $pelayanan = User::factory()->create(['name' => 'Staf Pelayanan Mustahik'])->assignRole(['pelayanan_mustahik', 'amil']);
    $ketua = User::factory()->create(['name' => 'Ketua BAZNAS'])->assignRole(['ketua', 'amil']);
    $kabag = User::factory()->create(['name' => 'Kabag Pendis Pendag'])->assignRole(['kabag_pendis_pendag', 'amil']);
    $stafPendis = User::factory()->create(['name' => 'Staf Lapangan Pendis'])->assignRole(['staf_pendistribusian', 'amil']);
    $waka2 = User::factory()->create(['name' => 'Waka II Bidang Pendistribusian'])->assignRole(['waka_2', 'amil']);
    $keuangan = User::factory()->create(['name' => 'Staf Keuangan Internal'])->assignRole(['keuangan', 'amil']);

    $this->actingAs($pelayanan);

    $mustahik = app(SimpanMustahik::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Ahmad Sudrajat',
        'telepon' => '085612345678',
    ]);

    $program = Program::where('sub_bidang', 'pendistribusian')->firstOrFail();
    $zakat = JenisDana::where('kode', 'ZAKAT')->firstOrFail();
    $infak = JenisDana::where('kode', 'INFAK')->firstOrFail();
    $miskin = Asnaf::where('kode', 'ilike', 'miskin')->firstOrFail();

    // K10: Zakat wajib asnaf, Infak tidak boleh memakai asnaf.
    expect(fn () => app(BuatPermohonan::class)->jalankan([
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'jenis_dana_id' => $zakat->id,
        'nominal_diajukan' => 2_000_000,
    ]))->toThrow(ValidationException::class);

    expect(fn () => app(BuatPermohonan::class)->jalankan([
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'jenis_dana_id' => $infak->id,
        'asnaf_id' => $miskin->id,
        'nominal_diajukan' => 2_000_000,
    ]))->toThrow(ValidationException::class);

    // Langkah 1: Pelayanan Mustahik input permohonan.
    $permohonan = app(BuatPermohonan::class)->jalankan([
        'tanggal' => '2026-11-22',
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'jenis_dana_id' => $zakat->id,
        'asnaf_id' => $miskin->id,
        'nominal_diajukan' => 3_000_000,
        'uraian' => 'Bantuan biaya pengobatan',
    ]);

    expect($permohonan->langkah_sekarang)->toBe(1)
        ->and($permohonan->status_kode)->toBe('diajukan')
        ->and($permohonan->kode_tracking)->toStartWith('TRK-');

    // Langkah 1 -> 2: Pelayanan meneruskan ke Ketua.
    $permohonan = app(ProsesLangkahPermohonan::class)->jalankan(['permohonan_id' => $permohonan->id]);
    expect($permohonan->status_kode)->toBe('disposisi_ketua')
        ->and($permohonan->status_publik)->toBe('disposisi');

    // Peran non-Ketua ditolak di Langkah 2.
    expect(fn () => app(ProsesLangkahPermohonan::class)->jalankan(['permohonan_id' => $permohonan->id]))
        ->toThrow(AuthorizationException::class);

    // Langkah 2 -> 3: Ketua disposisi ke Kabag + menugaskan Staf Lapangan Pendis.
    $this->actingAs($ketua);
    $permohonan = app(ProsesLangkahPermohonan::class)->jalankan([
        'permohonan_id' => $permohonan->id,
        'petugas_lapangan_id' => $stafPendis->id,
        'instruksi' => 'Survey lapangan kondisi keluarga mustahik.',
    ]);
    expect($permohonan->status_kode)->toBe('asesmen_kabag')
        ->and($permohonan->status_publik)->toBe('verifikasi');

    // Langkah 3 -> 4: Staf yang ditugaskan / Kabag mengirim hasil asesmen ke Waka II.
    $this->actingAs($stafPendis);
    $permohonan = app(ProsesLangkahPermohonan::class)->jalankan([
        'permohonan_id' => $permohonan->id,
        'catatan_asesmen' => 'Layak dibantu sesuai verifikasi rumah dan SKTM.',
        'nominal_usulan' => 2_500_000,
    ]);
    expect($permohonan->status_kode)->toBe('ajuan_waka_2')
        ->and($permohonan->status_publik)->toBe('acc_pimpinan');

    // Langkah 4 -> 5: Waka II mengajukan besaran bantuan ke Ketua.
    $this->actingAs($waka2);
    $permohonan = app(ProsesLangkahPermohonan::class)->jalankan([
        'permohonan_id' => $permohonan->id,
        'nominal_usulan' => 2_500_000,
    ]);
    expect($permohonan->status_kode)->toBe('acc_ketua')
        ->and($permohonan->status_publik)->toBe('disetujui');

    // Langkah 5 -> 6: Ketua ACC besaran -> status masuk minta_keuangan (tampil_publik=false -> publik tetap 'disetujui').
    $this->actingAs($ketua);
    $permohonan = app(ProsesLangkahPermohonan::class)->jalankan([
        'permohonan_id' => $permohonan->id,
        'nominal_disetujui' => 2_500_000,
    ]);
    expect($permohonan->status_kode)->toBe('minta_keuangan')
        ->and($permohonan->status_publik)->toBe('disetujui')
        ->and($permohonan->kesiapan_keuangan)->toBe('diminta');

    // Langkah 6 -> 7: Pelayanan Mustahik TIDAK boleh menyelesaikan sebelum Keuangan menandai siap.
    $this->actingAs($pelayanan);
    expect(fn () => app(ProsesLangkahPermohonan::class)->jalankan([
        'permohonan_id' => $permohonan->id,
    ]))->toThrow(ValidationException::class);

    // Bagian Keuangan menandai dana siap lewat endpoint khusus Keuangan.
    $this->actingAs($keuangan)
        ->post(route('distribusi.permohonan.siap-keuangan', $permohonan), ['catatan' => 'Dana kas siap'])
        ->assertRedirect();

    expect($permohonan->fresh()->kesiapan_keuangan)->toBe('siap');

    // Langkah 7: Pelayanan Mustahik menyelesaikan serah terima -> diterima_mustahik (dicairkan).
    $this->actingAs($pelayanan);
    $selesai = app(ProsesLangkahPermohonan::class)->jalankan([
        'permohonan_id' => $permohonan->id,
        'catatan' => 'Diserahkan langsung ke mustahik.',
    ]);

    expect($selesai->status_kode)->toBe('diterima_mustahik')
        ->and($selesai->status_publik)->toBe('dicairkan')
        ->and($selesai->langkah_sekarang)->toBe(7);

    // Lembar disposisi PDF dapat di-stream.
    $this->actingAs($kabag)
        ->get(route('distribusi.permohonan.lembar-disposisi', $permohonan))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('PDP-T04 & PDP-T08 K8: Administrator dapat menuntaskan 7 langkah sendirian dan serah terima menerbitkan penyaluran dicairkan', function () {
    $admin = User::factory()->create(['name' => 'Administrator'])->assignRole('admin');
    $this->actingAs($admin);

    $mustahik = app(SimpanMustahik::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Budi Santoso',
        'telepon' => '081234567001',
    ]);

    $program = Program::where('sub_bidang', 'pendistribusian')->firstOrFail();
    $zakat = JenisDana::where('kode', 'ZAKAT')->firstOrFail();
    $miskin = Asnaf::where('kode', 'ilike', 'miskin')->firstOrFail();

    $permohonan = app(BuatPermohonan::class)->jalankan([
        'tanggal' => now()->toDateString(),
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'jenis_dana_id' => $zakat->id,
        'asnaf_id' => $miskin->id,
        'nominal_diajukan' => 2_000_000,
    ]);

    $proses = fn (array $tambahan = []) => app(ProsesLangkahPermohonan::class)
        ->jalankan(['permohonan_id' => $permohonan->id, ...$tambahan]);

    $proses();
    $proses();
    $proses(['catatan_asesmen' => 'Layak dibantu.', 'nominal_usulan' => 1_800_000]);
    $proses(['nominal_usulan' => 1_800_000]);

    $permohonan = $proses(['nominal_disetujui' => 1_800_000]);
    expect($permohonan->status_kode)->toBe('minta_keuangan');

    $proses(['aksi' => 'siapkan_keuangan']);
    $selesai = $proses(['catatan' => 'Diserahkan.']);

    expect($selesai->status_kode)->toBe('diterima_mustahik');

    $penyaluran = Penyaluran::with('items')->where('permohonan_id', $permohonan->id)->firstOrFail();
    expect($penyaluran->status)->toBe('dicairkan')
        ->and($penyaluran->nomor)->not->toBeNull()
        ->and($penyaluran->mustahik_id)->toBe($mustahik->id)
        ->and($penyaluran->totalRupiah())->toBe(1_800_000)
        ->and($penyaluran->items->first()->asnaf_id)->toBe($miskin->id)
        ->and(AuditLog::where('entitas_tipe', Penyaluran::class)->where('entitas_id', $penyaluran->id)->where('aksi', 'terbit_dari_permohonan')->exists())->toBeTrue();
});

it('PDP-T04 K8: non-admin pembuat permohonan tetap ditolak maker-checker saat ACC Ketua', function () {
    $ketua = User::factory()->create()->assignRole(['ketua', 'amil']);
    $this->actingAs($ketua);

    $mustahik = app(SimpanMustahik::class)->jalankan(['jenis' => 'perorangan', 'nama' => 'Cecep', 'telepon' => '081234567002']);
    $program = Program::where('sub_bidang', 'pendistribusian')->firstOrFail();
    $infak = JenisDana::where('kode', 'INFAK')->firstOrFail();

    $permohonan = app(BuatPermohonan::class)->jalankan([
        'tanggal' => now()->toDateString(),
        'mustahik_id' => $mustahik->id,
        'program_id' => $program->id,
        'jenis_dana_id' => $infak->id,
        'nominal_diajukan' => 500_000,
    ]);

    $permohonan->forceFill(['status_kode' => 'acc_ketua', 'langkah_sekarang' => 5])->save();

    expect(fn () => app(ProsesLangkahPermohonan::class)->jalankan([
        'permohonan_id' => $permohonan->id,
        'nominal_disetujui' => 500_000,
    ]))->toThrow(ValidationException::class);
});

it('PDP-T03 & PDP-T12: permohonan memisahkan data pemohon vs mustahik dan menerbitkan PDF bukti kode lacak publik', function () {
    $pelayanan = User::factory()->create()->assignRole(['pelayanan_mustahik', 'amil']);
    $this->actingAs($pelayanan);

    $mustahik = app(SimpanMustahik::class)->jalankan([
        'jenis' => 'perorangan',
        'nama' => 'Nenek Maryam',
        'telepon' => '081200001111',
    ]);
    $program = Program::where('sub_bidang', 'pendistribusian')->firstOrFail();
    $infak = JenisDana::where('kode', 'INFAK')->firstOrFail();

    // Bila pemohon_sama_mustahik = false, nama dan telepon pemohon wajib diisi.
    expect(fn () => app(BuatPermohonan::class)->jalankan([
        'mustahik_id' => $mustahik->id,
        'pemohon_sama_mustahik' => false,
        'program_id' => $program->id,
        'jenis_dana_id' => $infak->id,
        'nominal_diajukan' => 750_000,
    ]))->toThrow(ValidationException::class);

    $permohonan = app(BuatPermohonan::class)->jalankan([
        'mustahik_id' => $mustahik->id,
        'pemohon_sama_mustahik' => false,
        'pemohon_nama' => 'Ustadz Hasan',
        'pemohon_telepon' => '085788889999',
        'pemohon_hubungan' => 'Pengurus DKM / RT / RW',
        'pemohon_alamat' => 'Kp. Sadewata RT 02',
        'program_id' => $program->id,
        'jenis_dana_id' => $infak->id,
        'nominal_diajukan' => 750_000,
        'uraian' => 'Bantuan biaya berobat lansia',
    ]);

    expect($permohonan->pemohon_sama_mustahik)->toBeFalse()
        ->and($permohonan->pemohon_nama)->toBe('Ustadz Hasan')
        ->and($permohonan->pemohon_telepon)->toBe('085788889999');

    $pdf = $this->get(route('distribusi.permohonan.tanda-terima', $permohonan));
    $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($pdf->getContent())->toStartWith('%PDF-');
});
