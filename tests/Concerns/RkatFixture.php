<?php

namespace Tests\Concerns;

use App\Models\PosAnggaran;
use App\Models\RkatNote;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use Database\Seeders\PeranSeeder;
use Database\Seeders\PosAnggaranSeeder;

/** Pembantu uji PK1: RKAT 2026 dari docs/fixtures/rkat-2026.json (angka kontrol PRD 10.1). */
final class RkatFixture
{
    public static function fixture(): array
    {
        return json_decode(file_get_contents(base_path('docs/fixtures/rkat-2026.json')), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function siapkan(): void
    {
        app(PeranSeeder::class)->run();
        app(PosAnggaranSeeder::class)->run();
    }

    /** Batas hak amil RKAT 2026 (PRD 10.1 VAL-6) dalam basis poin. */
    public static function batas2026(): array
    {
        return [
            'batas_amil_zakat_bp' => 1250,
            'batas_amil_infak_bp' => 2000,
            'batas_amil_csr_bp' => 1000,
            'batas_amil_dskl_bp' => 0,
        ];
    }

    /** Versi berisi seluruh daun fixture 2026; $ubah = [kode => nilai baru]. */
    public static function versi(int $tahun = 2026, string $status = 'draft', array $ubah = [], string $sumber = 'manual', bool $denganCatatan = true): RkatVersion
    {
        $versi = RkatVersion::create(['tahun' => $tahun, 'jenis' => 'awal', 'nomor_perubahan' => 0, 'status' => $status, 'sumber' => $sumber]);
        $pos = PosAnggaran::all()->keyBy('kode');

        foreach (self::fixture()['pos'] as $p) {
            if ($p['agregasi'] !== 'input_langsung') {
                continue;
            }
            RkatValue::create([
                'rkat_version_id' => $versi->id,
                'pos_anggaran_id' => $pos[$p['kode']]->id,
                'kode_asal' => $p['kode'],
                'nama_asal' => $p['nama'],
                'tipe_nilai' => $p['tipe_nilai'],
                'nilai' => $ubah[$p['kode']] ?? $p['nilai_2026'],
            ]);
        }

        if ($denganCatatan) {
            RkatNote::create(['rkat_version_id' => $versi->id, ...self::batas2026()]);
        }

        return $versi;
    }
}
