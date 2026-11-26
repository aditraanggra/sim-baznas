<?php

namespace Database\Seeders;

use App\Models\PosAnggaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Menambahkan 9 pos L2 baru dari dokumen alur RKAT terbaru tanpa menghapus pos lama 2022-2026.
 * Idempoten: upsert pada (kode, tipe_nilai) serta merapikan urutan L2 Neraca.
 */
class PosAnggaranAlurBaruSeeder extends Seeder
{
    public const POS_L2_BARU = [
        ['kode' => 'L2/2.8', 'nama' => 'Infak/Sedekah Terikat - Program Natura', 'induk' => 'L2/2', 'urutan' => 15],
        ['kode' => 'L2/2.9', 'nama' => 'Infak/Sedekah Terikat - Program Dakwah', 'induk' => 'L2/2', 'urutan' => 16],
        ['kode' => 'L2/2.10', 'nama' => 'Infak/Sedekah Terikat - Program Kesehatan', 'induk' => 'L2/2', 'urutan' => 17],
        ['kode' => 'L2/2.11', 'nama' => 'Infak/Sedekah Terikat - Program Pendidikan', 'induk' => 'L2/2', 'urutan' => 18],
        ['kode' => 'L2/2.12', 'nama' => 'Infak/Sedekah Terikat - Program Ekonomi', 'induk' => 'L2/2', 'urutan' => 19],
        ['kode' => 'L2/2.13', 'nama' => 'Infak/Sedekah Terikat - Program Kemanusiaan dan Bencana', 'induk' => 'L2/2', 'urutan' => 20],
        ['kode' => 'L2/3.2', 'nama' => 'Corporate Social Responsibility - Natura', 'induk' => 'L2/3', 'urutan' => 23],
        ['kode' => 'L2/5.3', 'nama' => 'DSKL - Fidyah', 'induk' => 'L2/5', 'urutan' => 30],
        ['kode' => 'L2/5.4', 'nama' => 'DSKL - Dam', 'induk' => 'L2/5', 'urutan' => 31],
    ];

    private const URUTAN_L2_LAMA = [
        'L2/3' => 21,
        'L2/3.1' => 22,
        'L2/4' => 24,
        'L2/4.1' => 25,
        'L2/4.2' => 26,
        'L2/5' => 27,
        'L2/5.1' => 28,
        'L2/5.2' => 29,
    ];

    public function run(): void
    {
        self::pastikanTersedia();
    }

    public static function pastikanTersedia(): void
    {
        $indukMap = PosAnggaran::whereIn('kode', ['L2/2', 'L2/3', 'L2/5'])
            ->where('tipe_nilai', 'rupiah')
            ->get()
            ->keyBy('kode');

        if ($indukMap->count() < 3) {
            return;
        }

        DB::transaction(function () use ($indukMap): void {
            foreach (self::URUTAN_L2_LAMA as $kode => $urutan) {
                PosAnggaran::where('kode', $kode)
                    ->where('tipe_nilai', 'rupiah')
                    ->update(['urutan' => $urutan]);
            }

            foreach (self::POS_L2_BARU as $item) {
                $induk = $indukMap->get($item['induk']);
                if (! $induk) {
                    continue;
                }

                PosAnggaran::updateOrCreate(
                    ['kode' => $item['kode'], 'tipe_nilai' => 'rupiah'],
                    [
                        'nama' => $item['nama'],
                        'induk_id' => $induk->id,
                        'path' => $induk->path.'/'.$item['kode'],
                        'lampiran' => 'L2',
                        'tingkat' => 2,
                        'agregasi' => 'input_langsung',
                        'sifat_waktu' => 'aliran',
                        'berlaku_dari_tahun' => 2026,
                        'aktif' => true,
                        'urutan' => $item['urutan'],
                    ],
                );
            }
        });
    }
}
