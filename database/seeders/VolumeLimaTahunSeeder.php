<?php

namespace Database\Seeders;

use App\Models\Asnaf;
use App\Models\BentukBantuan;
use App\Models\MetodeBayar;
use App\Models\Mustahik;
use App\Models\Muzakki;
use App\Models\Program;
use App\Models\SumberDana;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VolumeLimaTahunSeeder extends Seeder
{
    public function run(int $jumlahPerJenis = 1200): void
    {
        $petugasId = User::query()->value('id') ?? User::factory()->create()->id;
        $metodeId = MetodeBayar::query()->value('id') ?? 1;
        $zm = SumberDana::where('kode', 'ZM')->firstOrFail();
        $ift = SumberDana::where('kode', 'IFT')->firstOrFail();
        $fakirId = Asnaf::where('kode', 'ilike', 'fakir')->value('id');
        $uangId = BentukBantuan::where('kode', 'UANG')->value('id');
        $programId = Program::query()->value('id');

        $muzakkiId = Muzakki::query()->value('id');
        if (! $muzakkiId) {
            $muzakkiId = DB::table('muzakki')->insertGetId([
                'kode' => 'MZK-BENCH-001',
                'jenis' => 'perorangan',
                'nama' => 'Muzakki Benchmark',
                'aktif' => true,
                'created_by' => $petugasId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $mustahikId = Mustahik::query()->value('id');
        if (! $mustahikId) {
            $mustahikId = DB::table('mustahik')->insertGetId([
                'kode' => 'MST-BENCH-001',
                'jenis' => 'perorangan',
                'nama' => 'Mustahik Benchmark',
                'status_verifikasi' => 'terverifikasi',
                'aktif' => true,
                'created_by' => $petugasId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tanggalAwal = Carbon::create(2022, 1, 1);
        $rentangHari = 365 * 5;
        $now = now();

        $penerimaanRows = [];
        $penyaluranRows = [];

        for ($i = 1; $i <= $jumlahPerJenis; $i++) {
            $tgl = $tanggalAwal->copy()->addDays(($i * 17) % $rentangHari)->toDateString();

            $penerimaanRows[] = [
                'nomor' => sprintf('PNR-BENCH-%05d', $i),
                'tanggal' => $tgl,
                'status' => 'final',
                'muzakki_id' => $muzakkiId,
                'metode_bayar_id' => $metodeId,
                'petugas_id' => $petugasId,
                'created_by' => $petugasId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $penyaluranRows[] = [
                'nomor' => sprintf('PNY-BENCH-%05d', $i),
                'tanggal' => $tgl,
                'status' => 'dicairkan',
                'mustahik_id' => $mustahikId,
                'program_id' => $programId,
                'sub_bidang' => ($i % 2 === 0) ? 'pendayagunaan' : 'pendistribusian',
                'metode_bayar_id' => $metodeId,
                'petugas_id' => $petugasId,
                'disetujui_oleh' => $petugasId,
                'disetujui_at' => $now,
                'dicairkan_oleh' => $petugasId,
                'dicairkan_at' => $now,
                'created_by' => $petugasId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($penerimaanRows, 300) as $chunk) {
            DB::table('penerimaan')->insert($chunk);
        }
        foreach (array_chunk($penyaluranRows, 300) as $chunk) {
            DB::table('penyaluran')->insert($chunk);
        }

        $penerimaanIds = DB::table('penerimaan')->where('nomor', 'like', 'PNR-BENCH-%')->orderBy('id')->pluck('id');
        $itemMasuk = [];
        foreach ($penerimaanIds as $idx => $pid) {
            $itemMasuk[] = [
                'penerimaan_id' => $pid,
                'sumber_dana_id' => ($idx % 2 === 0) ? $zm->id : $ift->id,
                'jumlah' => 500_000,
                'created_by' => $petugasId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($itemMasuk, 400) as $chunk) {
            DB::table('penerimaan_item')->insert($chunk);
        }

        $penyaluranIds = DB::table('penyaluran')->where('nomor', 'like', 'PNY-BENCH-%')->orderBy('id')->pluck('id');
        $itemKeluar = [];
        foreach ($penyaluranIds as $idx => $yid) {
            $zakat = $idx % 2 === 0;
            $itemKeluar[] = [
                'penyaluran_id' => $yid,
                'sumber_dana_id' => $zakat ? $zm->id : $ift->id,
                'asnaf_id' => $zakat ? $fakirId : null,
                'bentuk_bantuan_id' => $uangId,
                'jumlah' => 250_000,
                'created_by' => $petugasId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($itemKeluar, 400) as $chunk) {
            DB::table('penyaluran_item')->insert($chunk);
        }
    }
}
