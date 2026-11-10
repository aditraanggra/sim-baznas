<?php

namespace Database\Seeders;

use App\Models\PosAnggaran;
use Illuminate\Database\Seeder;

/**
 * Seed pos baku dari docs/fixtures/rkat-2026.json (112 pos, kontrol PRD 10.1 cocok).
 * Idempoten: upsert pada (kode, tipe_nilai).
 */
class PosAnggaranSeeder extends Seeder
{
    public function run(): void
    {
        $berkas = base_path('docs/fixtures/rkat-2026.json');
        $fixture = json_decode(file_get_contents($berkas), true, flags: JSON_THROW_ON_ERROR);
        $tahun = $fixture['tahun'];

        $posByKode = [];
        foreach ($fixture['pos'] as $pos) {
            $induk = $pos['induk'] ? ($posByKode[$pos['induk']] ?? null) : null;
            $model = PosAnggaran::updateOrCreate(
                ['kode' => $pos['kode'], 'tipe_nilai' => $pos['tipe_nilai']],
                [
                    'nama' => $pos['nama'],
                    'lampiran' => $pos['lampiran'],
                    'tingkat' => (int) substr($pos['lampiran'], 1),
                    'agregasi' => $pos['agregasi'],
                    'sifat_waktu' => $pos['sifat_waktu'],
                    'berlaku_dari_tahun' => $tahun,
                    'urutan' => $pos['urutan'],
                    'aktif' => true,
                    'induk_id' => $induk?->id,
                    'path' => $induk ? $induk->path.'/'.$pos['kode'] : $pos['kode'],
                ],
            );
            $posByKode[$pos['kode']] = $model;
        }
    }
}
