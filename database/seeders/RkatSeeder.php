<?php

namespace Database\Seeders;

use App\Actions\Rkat\BuatVersiRkat;
use App\Actions\Rkat\SimpanCatatanRkat;
use App\Actions\Rkat\SimpanNilaiRkat;
use App\Models\PosAnggaran;
use App\Models\RkatVersion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * RKAT 2026 Awal (Draft) dari docs/fixtures/rkat-2026.json dengan batas hak amil PRD 10.1.
 * Tidak disahkan: nomor surat pengesahan tidak diketahui. Idempoten: dilewati bila versi Awal 2026 sudah ada.
 */
class RkatSeeder extends Seeder
{
    public function run(): void
    {
        if (RkatVersion::where(['tahun' => 2026, 'jenis' => 'awal'])->exists()) {
            return;
        }

        $admin = User::where('email', 'admin@pkp.local')->firstOrFail();
        $sebelumnya = Auth::user();
        Auth::setUser($admin);

        try {
            $fixture = json_decode(file_get_contents(base_path('docs/fixtures/rkat-2026.json')), true, flags: JSON_THROW_ON_ERROR);
            $versi = app(BuatVersiRkat::class)->jalankan(['tahun' => 2026, 'jenis' => 'awal']);

            $posId = PosAnggaran::pluck('id', 'kode');
            $nilai = [];
            foreach ($fixture['pos'] as $p) {
                if ($p['agregasi'] === 'input_langsung') {
                    $nilai[$posId[$p['kode']]] = $p['nilai_2026'];
                }
            }

            app(SimpanNilaiRkat::class)->jalankan(['rkat_version_id' => $versi->id, 'nilai' => $nilai]);
            app(SimpanCatatanRkat::class)->jalankan([
                'rkat_version_id' => $versi->id,
                'batas_amil_zakat_bp' => 1250, 'batas_amil_infak_bp' => 2000, 'batas_amil_csr_bp' => 1000, 'batas_amil_dskl_bp' => 0,
            ]);
        } finally {
            $sebelumnya ? Auth::setUser($sebelumnya) : Auth::forgetGuards();
        }
    }
}
