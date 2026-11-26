<?php

namespace Database\Seeders;

use App\Models\Asnaf;
use App\Models\Bidang;
use App\Models\JenisDana;
use App\Models\PosAnggaran;
use App\Models\PosAnggaranPemetaan;
use App\Models\Program;
use App\Models\RkatVersion;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerencanaanJembatanSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            // 1. Tahun Anggaran 2022 s.d. 2026
            foreach ([
                ['tahun' => 2022, 'status' => TahunAnggaran::STATUS_TUTUP, 'mulai' => '2022-01-01', 'selesai' => '2022-12-31'],
                ['tahun' => 2023, 'status' => TahunAnggaran::STATUS_TUTUP, 'mulai' => '2023-01-01', 'selesai' => '2023-12-31'],
                ['tahun' => 2024, 'status' => TahunAnggaran::STATUS_TUTUP, 'mulai' => '2024-01-01', 'selesai' => '2024-12-31'],
                ['tahun' => 2025, 'status' => TahunAnggaran::STATUS_TUTUP, 'mulai' => '2025-01-01', 'selesai' => '2025-12-31'],
                ['tahun' => 2026, 'status' => TahunAnggaran::STATUS_BERJALAN, 'mulai' => '2026-01-01', 'selesai' => '2026-12-31'],
            ] as $ta) {
                $th = TahunAnggaran::updateOrCreate(
                    ['tahun' => $ta['tahun']],
                    [
                        'status' => $ta['status'],
                        'tanggal_mulai' => $ta['mulai'],
                        'tanggal_selesai' => $ta['selesai'],
                        'catatan' => 'Tahun anggaran ' . $ta['tahun'],
                    ],
                );

                RkatVersion::where('tahun', $ta['tahun'])
                    ->whereNull('tahun_anggaran_id')
                    ->update(['tahun_anggaran_id' => $th->id]);
            }

            // 2. Ambil referensi master
            $bidang = Bidang::pluck('id', 'kode')->all();
            $jenisDana = JenisDana::pluck('id', 'kode')->all();
            $sumberDana = SumberDana::pluck('id', 'kode')->all();
            $asnaf = Asnaf::pluck('id', 'kode')->all();
            $program = Program::pluck('id', 'kode')->all();

            $peta = [
                // === L2: PENERIMAAN ===
                'L2/1.1' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'ZM', 'upz' => false, 'jenis_muzakki' => 'lembaga'],
                'L2/1.2' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'ZM', 'upz' => false, 'jenis_muzakki' => 'perorangan'],
                'L2/1.3' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'ZM', 'upz' => true, 'jenis_muzakki' => 'perorangan'],
                'L2/1.4' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'ZF', 'upz' => false],
                'L2/1.5' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'ZF', 'upz' => true],
                'L2/2.1' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'IFTT', 'upz' => false],
                'L2/2.2' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'IFTT', 'upz' => true],
                'L2/2.3' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'IFT', 'upz' => false],
                'L2/2.4' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'IFT'],
                'L2/2.5' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'IFT', 'upz' => true],
                'L2/2.6' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'IFT'],
                'L2/2.7' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'IFT'],
                // Sub-program & natura Infak Terikat memakai manual di v_realisasi_rkat agar tidak double-counting penerimaan_item IFT.
                'L2/2.8' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'sumber_dana' => 'IFT'],
                'L2/2.9' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'sumber_dana' => 'IFT'],
                'L2/2.10' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'sumber_dana' => 'IFT'],
                'L2/2.11' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'sumber_dana' => 'IFT'],
                'L2/2.12' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'sumber_dana' => 'IFT'],
                'L2/2.13' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'sumber_dana' => 'IFT'],
                'L2/3.1' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'CSR'],
                'L2/3.2' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'sumber_dana' => 'CSR'],
                'L2/4.1' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'KRB', 'upz' => false],
                'L2/4.2' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'sumber_dana' => 'KRB', 'upz' => true],
                'L2/5.1' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'jenis_dana' => 'DSKL', 'upz' => false],
                'L2/5.2' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan', 'jenis_dana' => 'DSKL', 'upz' => true],
                // Fidyah & Dam memakai manual sementara di v_realisasi_rkat karena L2/5.1 mencakup seluruh DSKL non-UPZ.
                'L2/5.3' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'sumber_dana' => 'FDY'],
                'L2/5.4' => ['bidang' => 'pengumpulan', 'sumber' => 'manual', 'jenis_dana' => 'DSKL'],
                'L2/LN/1.1' => ['bidang' => 'pengumpulan', 'sumber' => 'manual'],
                'L2/LN/1.2' => ['bidang' => 'pengumpulan', 'sumber' => 'manual'],
                'L2/LN/1.3' => ['bidang' => 'pengumpulan', 'sumber' => 'manual'],
                'L2/LN/1.4' => ['bidang' => 'pengumpulan', 'sumber' => 'manual'],

                // === L3: PENYALURAN ===
                'L3/1.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'ZAKAT', 'asnaf' => 'FAKIR'],
                'L3/1.2' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'ZAKAT', 'asnaf' => 'MISKIN'],
                'L3/1.3' => ['bidang' => 'pkp', 'sumber' => 'manual'],
                'L3/1.4' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'ZAKAT', 'asnaf' => 'MUALAF'],
                'L3/1.5' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'ZAKAT', 'asnaf' => 'RIQOB'],
                'L3/1.6' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'ZAKAT', 'asnaf' => 'GHARIMIN'],
                'L3/1.7' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'ZAKAT', 'asnaf' => 'FISABILILLAH'],
                'L3/1.8' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'ZAKAT', 'asnaf' => 'IBNU_SABIL'],
                'L3/2.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'sumber_dana' => 'IFTT'],
                'L3/2.2' => ['bidang' => 'pkp', 'sumber' => 'manual'],
                'L3/3.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'sumber_dana' => 'IFT'],
                'L3/3.2' => ['bidang' => 'pkp', 'sumber' => 'manual'],
                'L3/4.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'sumber_dana' => 'CSR'],
                'L3/4.2' => ['bidang' => 'pkp', 'sumber' => 'manual'],
                'L3/5.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'INFAK'],
                'L3/6.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'sumber_dana' => 'KRB'],
                'L3/6.2' => ['bidang' => 'pkp', 'sumber' => 'manual'],
                'L3/7.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'sumber_dana' => 'FDY'],
                'L3/7.2' => ['bidang' => 'pkp', 'sumber' => 'manual'],
                'L3/8.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'jenis_dana' => 'DSKL'],
                'L3/8.2' => ['bidang' => 'pkp', 'sumber' => 'manual'],
                'L3/9.1' => ['bidang' => 'pkp', 'sumber' => 'manual'],

                // === L4: PROGRAM ===
                'L4/1.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'program' => 'PDS-KEMANUSIAAN'],
                'L4/1.2' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'program' => 'PDS-KESEHATAN'],
                'L4/1.3' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'program' => 'PDS-PENDIDIKAN'],
                'L4/1.4' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'program' => 'PDS-DAKWAH'],
                'L4/2.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'program' => 'PDG-EKONOMI'],
                'L4/2.2' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'program' => 'PDG-KESEHATAN'],
                'L4/2.3' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran', 'program' => 'PDG-PENDIDIKAN'],

                // === L5: ORANG / BADAN ===
                'L5/1.1' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan_orang', 'jenis_muzakki' => 'perorangan'],
                'L5/1.2' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan_orang', 'jenis_muzakki' => 'lembaga'],
                'L5/1.3' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan_orang', 'jenis_dana' => 'INFAK'],
                'L5/1.4' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan_orang', 'jenis_dana' => 'CSR'],
                'L5/1.5' => ['bidang' => 'pengumpulan', 'sumber' => 'penerimaan_orang', 'jenis_dana' => 'DSKL'],
                'L5/2.1' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran_orang', 'program' => 'PDG-EKONOMI'],
                'L5/2.2' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran_orang', 'program' => 'PDS-PENDIDIKAN'],
                'L5/2.3' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran_orang', 'program' => 'PDS-KESEHATAN'],
                'L5/2.4' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran_orang', 'program' => 'PDS-KEMANUSIAAN'],
                'L5/2.5' => ['bidang' => 'distribusi', 'sumber' => 'penyaluran_orang', 'program' => 'PDS-DAKWAH'],
                'L5/3.1' => ['bidang' => 'distribusi', 'sumber' => 'manual'],
                'L5/3.2' => ['bidang' => 'distribusi', 'sumber' => 'manual'],
            ];

            // Tambahkan semua pos daun L6 dan L7 sebagai manual
            $semuaPos = PosAnggaran::where('agregasi', 'input_langsung')->get();

            foreach ($semuaPos as $pos) {
                $definisi = $peta[$pos->kode] ?? null;

                $kodeBidang = $definisi['bidang'] ?? match ($pos->lampiran) {
                    'L2' => 'pengumpulan',
                    'L3', 'L4', 'L5' => 'distribusi',
                    'L6' => 'pkp',
                    'L7' => 'umum',
                    default => 'pkp',
                };

                $sumberTransaksi = $definisi['sumber'] ?? 'manual';
                $jenisDanaId = isset($definisi['jenis_dana']) ? ($jenisDana[$definisi['jenis_dana']] ?? null) : null;
                $sumberDanaId = isset($definisi['sumber_dana']) ? ($sumberDana[$definisi['sumber_dana']] ?? null) : null;
                $asnafId = isset($definisi['asnaf']) ? ($asnaf[$definisi['asnaf']] ?? null) : null;
                $programId = isset($definisi['program']) ? ($program[$definisi['program']] ?? null) : null;
                $filterUpz = $definisi['upz'] ?? null;
                $filterJenisMuzakki = $definisi['jenis_muzakki'] ?? null;

                PosAnggaranPemetaan::updateOrCreate(
                    ['pos_anggaran_id' => $pos->id],
                    [
                        'bidang_id' => $bidang[$kodeBidang] ?? $bidang['pkp'],
                        'sumber_transaksi' => $sumberTransaksi,
                        'jenis_dana_id' => $jenisDanaId,
                        'sumber_dana_id' => $sumberDanaId,
                        'asnaf_id' => $asnafId,
                        'program_id' => $programId,
                        'filter_via_upz' => $filterUpz,
                        'filter_jenis_muzakki' => $filterJenisMuzakki,
                        'nama_kegiatan' => $pos->nama,
                        'aktif' => true,
                    ]
                );
            }
        });
    }
}
