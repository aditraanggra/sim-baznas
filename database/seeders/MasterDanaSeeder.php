<?php

namespace Database\Seeders;

use App\Models\Asnaf;
use App\Models\Bidang;
use App\Models\DanaKelompok;
use App\Models\JenisDana;
use App\Models\Program;
use App\Models\SumberDana;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDanaSeeder extends Seeder
{
    public function run(): void
    {
        if (Bidang::where('kode', 'distribusi')->doesntExist()) {
            $this->call(BidangSeeder::class);
        }

        DB::transaction(function (): void {
            $jenisDanaMap = [];
            foreach ([
                ['kode' => 'ZAKAT', 'nama' => 'Zakat', 'wajib_asnaf' => true],
                ['kode' => 'INFAK', 'nama' => 'Infak', 'wajib_asnaf' => false],
                ['kode' => 'DSKL', 'nama' => 'DSKL', 'wajib_asnaf' => false],
                ['kode' => 'CSR', 'nama' => 'CSR', 'wajib_asnaf' => false],
            ] as $item) {
                $jenis = JenisDana::updateOrCreate(
                    ['kode' => $item['kode']],
                    ['nama' => $item['nama'], 'wajib_asnaf' => $item['wajib_asnaf'], 'aktif' => true],
                );
                $jenisDanaMap[$item['kode']] = $jenis->id;
            }

            $kelompokMap = [];
            foreach ([
                ['kode' => 'ZAKAT', 'nama' => 'Zakat'],
                ['kode' => 'INFAK', 'nama' => 'Infak/Sedekah'],
                ['kode' => 'DSKL', 'nama' => 'DSKL'],
                ['kode' => 'CSR', 'nama' => 'CSR'],
                ['kode' => 'AMIL', 'nama' => 'Amil'],
                ['kode' => 'NONHALAL', 'nama' => 'Nonhalal'],
            ] as $item) {
                $kelompok = DanaKelompok::updateOrCreate(
                    ['kode' => $item['kode']],
                    ['nama' => $item['nama'], 'aktif' => true],
                );
                $kelompokMap[$item['kode']] = $kelompok->id;
            }

            foreach ([
                ['kode' => 'ZF', 'nama' => 'Zakat Fitrah', 'jenis' => 'ZAKAT', 'kelompok' => 'ZAKAT', 'terikat' => false],
                ['kode' => 'ZM', 'nama' => 'Zakat Mal', 'jenis' => 'ZAKAT', 'kelompok' => 'ZAKAT', 'terikat' => false],
                ['kode' => 'IFTT', 'nama' => 'Infak Sedekah Tidak Terikat', 'jenis' => 'INFAK', 'kelompok' => 'INFAK', 'terikat' => false],
                ['kode' => 'IFT', 'nama' => 'Infak Sedekah Terikat', 'jenis' => 'INFAK', 'kelompok' => 'INFAK', 'terikat' => true],
                ['kode' => 'FDY', 'nama' => 'Fidyah', 'jenis' => 'DSKL', 'kelompok' => 'DSKL', 'terikat' => true],
                ['kode' => 'KRB', 'nama' => 'Kurban', 'jenis' => 'DSKL', 'kelompok' => 'DSKL', 'terikat' => true],
                ['kode' => 'KA', 'nama' => 'Kotak Amal', 'jenis' => 'DSKL', 'kelompok' => 'DSKL', 'terikat' => false],
                ['kode' => 'KFT', 'nama' => 'Kafarat', 'jenis' => 'DSKL', 'kelompok' => 'DSKL', 'terikat' => true],
                ['kode' => 'CSR', 'nama' => 'CSR', 'jenis' => 'CSR', 'kelompok' => 'CSR', 'terikat' => true],
            ] as $item) {
                SumberDana::updateOrCreate(
                    ['kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'jenis_dana_id' => $jenisDanaMap[$item['jenis']],
                        'dana_kelompok_id' => $kelompokMap[$item['kelompok']],
                        'terikat' => $item['terikat'],
                        'aktif' => true,
                    ],
                );
            }

            foreach ([
                ['kode' => 'FAKIR', 'nama' => 'Fakir', 'urutan' => 1],
                ['kode' => 'MISKIN', 'nama' => 'Miskin', 'urutan' => 2],
                ['kode' => 'AMIL', 'nama' => 'Amil', 'urutan' => 3],
                ['kode' => 'MUALAF', 'nama' => 'Mualaf', 'urutan' => 4],
                ['kode' => 'RIQOB', 'nama' => 'Riqob', 'urutan' => 5],
                ['kode' => 'IBNU_SABIL', 'nama' => 'Ibnu Sabil', 'urutan' => 6],
                ['kode' => 'GHARIMIN', 'nama' => 'Gharimin', 'urutan' => 7],
                ['kode' => 'FISABILILLAH', 'nama' => 'Fisabilillah', 'urutan' => 8],
            ] as $item) {
                Asnaf::updateOrCreate(
                    ['kode' => $item['kode']],
                    ['nama' => $item['nama'], 'urutan' => $item['urutan'], 'aktif' => true],
                );
            }

            $bidangDistribusiId = Bidang::where('kode', 'distribusi')->value('id');

            foreach ([
                ['kode' => 'PDS-KEMANUSIAAN', 'nama' => 'Kemanusiaan', 'sub_bidang' => 'pendistribusian'],
                ['kode' => 'PDS-KESEHATAN', 'nama' => 'Kesehatan', 'sub_bidang' => 'pendistribusian'],
                ['kode' => 'PDS-PENDIDIKAN', 'nama' => 'Pendidikan', 'sub_bidang' => 'pendistribusian'],
                ['kode' => 'PDS-DAKWAH', 'nama' => 'Dakwah-Advokasi', 'sub_bidang' => 'pendistribusian'],
                ['kode' => 'PDG-EKONOMI', 'nama' => 'Ekonomi', 'sub_bidang' => 'pendayagunaan'],
                ['kode' => 'PDG-KESEHATAN', 'nama' => 'Kesehatan', 'sub_bidang' => 'pendayagunaan'],
                ['kode' => 'PDG-PENDIDIKAN', 'nama' => 'Pendidikan', 'sub_bidang' => 'pendayagunaan'],
            ] as $item) {
                Program::updateOrCreate(
                    ['kode' => $item['kode']],
                    [
                        'nama' => $item['nama'],
                        'bidang_id' => $bidangDistribusiId,
                        'sub_bidang' => $item['sub_bidang'],
                        'aktif' => true,
                    ],
                );
            }
        });
    }
}
