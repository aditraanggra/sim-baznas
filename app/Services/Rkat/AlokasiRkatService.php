<?php

namespace App\Services\Rkat;

use App\Models\RkatKegiatanRencana;
use App\Models\RkatTahapBidang;
use App\Models\RkatVersion;

/**
 * Menghitung alokasi Hak Amil 22 baris, pagu penyaluran 7 Kelompok Dana,
 * serta pagu operasional SAU (Hak Amil + Hibah + Saldo Awal) secara deterministik di server.
 */
final class AlokasiRkatService
{
    /**
     * @return array{
     *     tahap: array<string, array{status: string, catatan_verifikasi: ?string, diajukan_pada: ?string, diverifikasi_pada: ?string}>,
     *     pengumpulan: array{
     *         target: int,
     *         total_neraca: int,
     *         selisih: int,
     *         terpenuhi: bool,
     *         total_hak_amil: int,
     *         total_alokasi_penyaluran: int,
     *         total_luar_neraca: int,
     *         rincian_amil: list<array{id: int, pos_anggaran_id: int, kode_pos: string, urutan: int, uraian: string, kelompok_penyaluran: string, persen_bp: int, persen_maks_bp: int, penerimaan: int, hak_amil: int, alokasi_penyaluran: int}>,
     *         hak_amil_l3: array<string, int>,
     *         hak_amil_l6: array<string, int>,
     *         orang_l5: array<string, int>
     *     },
     *     distribusi: array{
     *         terbuka: bool,
     *         total_pagu: int,
     *         total_terpakai: int,
     *         selisih: int,
     *         terpenuhi: bool,
     *         kelompok: list<array{kode: string, label: string, kode_pos_l3: list<string>, pagu: int, terpakai: int, selisih: int, terpenuhi: bool}>
     *     },
     *     umum: array{
     *         terbuka: bool,
     *         pagu_hak_amil: int,
     *         tambahan_hibah_saldo: int,
     *         total_pagu: int,
     *         terpakai: int,
     *         selisih: int,
     *         terpenuhi: bool
     *     }
     * }
     */
    public static function ringkasan(RkatVersion $versi): array
    {
        $aturanList = $versi->aturanAmil()->with('posAnggaran:id,kode,nama')->orderBy('urutan')->get();
        $kegiatan = $versi->kegiatanRencana()->aktif()->with(['posAnggaran:id,kode,nama', 'posProgram:id,kode,nama'])->get();
        $pelengkap = $versi->pelengkapBidang()->with('posAnggaran:id,kode,nama')->get();
        $tahapRows = $versi->tahapBidang()->get()->keyBy('bidang_kode');

        $metaBaris = collect(config('rkat_amil.baris', []))->keyBy('kode_pos');
        $kelompokConfig = config('rkat_amil.kelompok_penyaluran', []);

        // Peta status 3 tahap bidang
        $tahap = [];
        foreach (RkatTahapBidang::BIDANG as $kodeBidang) {
            $row = $tahapRows->get($kodeBidang);
            $tahap[$kodeBidang] = [
                'status' => $row?->status ?? RkatTahapBidang::STATUS_MENUNGGU,
                'catatan_verifikasi' => $row?->catatan_verifikasi,
                'diajukan_pada' => $row?->diajukan_pada?->toIso8601String(),
                'diverifikasi_pada' => $row?->diverifikasi_pada?->toIso8601String(),
            ];
        }
        $pengumpulanTerverifikasi = $tahap['pengumpulan']['status'] === RkatTahapBidang::STATUS_TERVERIFIKASI;

        // 1. Agregasi Bidang Pengumpulan
        $kegiatanPengumpulan = $kegiatan->where('bidang_kode', 'pengumpulan');
        $penerimaanPerPos = [];
        $orangPerPos = [];
        foreach ($kegiatanPengumpulan as $item) {
            $penerimaanPerPos[$item->pos_anggaran_id] = ($penerimaanPerPos[$item->pos_anggaran_id] ?? 0) + (int) $item->total;
            $orangPerPos[$item->pos_anggaran_id] = ($orangPerPos[$item->pos_anggaran_id] ?? 0) + (int) $item->jumlah;
        }

        $totalNeraca = (int) array_sum($penerimaanPerPos);
        $target = (int) ($versi->target_pengumpulan ?? 0);
        $selisihPengumpulan = $totalNeraca - $target;

        $rincianAmil = [];
        $totalHakAmil = 0;
        $totalAlokasiPenyaluran = 0;
        $paguPerKelompok = array_fill_keys(array_keys($kelompokConfig), 0);
        $hakAmilL3 = array_fill_keys(config('rkat_amil.kode_amil_l3_lengkap', []), 0);
        $hakAmilL6 = array_fill_keys(config('rkat_amil.kode_amil_l6_lengkap', []), 0);
        $orangL5 = [];

        foreach ($aturanList as $aturan) {
            $kodePos = $aturan->posAnggaran?->kode ?? '';
            $meta = $metaBaris->get($kodePos, []);
            $penerimaan = (int) ($penerimaanPerPos[$aturan->pos_anggaran_id] ?? 0);
            $hakAmil = intdiv($penerimaan * (int) $aturan->persen_bp, 10000);
            $alokasiPenyaluran = $penerimaan - $hakAmil;

            $totalHakAmil += $hakAmil;
            $totalAlokasiPenyaluran += $alokasiPenyaluran;

            if (array_key_exists($aturan->kelompok_penyaluran, $paguPerKelompok)) {
                $paguPerKelompok[$aturan->kelompok_penyaluran] += $alokasiPenyaluran;
            }

            if (isset($meta['kode_amil_l3'])) {
                $hakAmilL3[$meta['kode_amil_l3']] = ($hakAmilL3[$meta['kode_amil_l3']] ?? 0) + $hakAmil;
            }
            if (isset($meta['kode_amil_l6'])) {
                $hakAmilL6[$meta['kode_amil_l6']] = ($hakAmilL6[$meta['kode_amil_l6']] ?? 0) + $hakAmil;
            }
            if (isset($meta['kode_orang_l5'])) {
                $orangL5[$meta['kode_orang_l5']] = ($orangL5[$meta['kode_orang_l5']] ?? 0) + (int) ($orangPerPos[$aturan->pos_anggaran_id] ?? 0);
            }

            $rincianAmil[] = [
                'id' => $aturan->id,
                'pos_anggaran_id' => $aturan->pos_anggaran_id,
                'kode_pos' => $kodePos,
                'urutan' => (int) $aturan->urutan,
                'uraian' => $aturan->uraian,
                'kelompok_penyaluran' => $aturan->kelompok_penyaluran,
                'persen_bp' => (int) $aturan->persen_bp,
                'persen_maks_bp' => (int) $aturan->persen_maks_bp,
                'penerimaan' => $penerimaan,
                'hak_amil' => $hakAmil,
                'alokasi_penyaluran' => $alokasiPenyaluran,
            ];
        }

        $totalLuarNeraca = (int) $pelengkap
            ->where('bidang_kode', 'pengumpulan')
            ->sum('nilai');

        // 2. Agregasi Bidang Pendistribusian & Pendayagunaan (7 Kelompok Dana)
        $l3KeKelompok = [];
        foreach ($kelompokConfig as $kodeKelompok => $cfg) {
            foreach ($cfg['kode_pos_l3'] as $kodeL3) {
                $l3KeKelompok[$kodeL3] = $kodeKelompok;
            }
        }

        $terpakaiPerKelompok = array_fill_keys(array_keys($kelompokConfig), 0);
        foreach ($kegiatan->where('bidang_kode', 'distribusi') as $item) {
            /** @var RkatKegiatanRencana $item */
            $kodeL3 = $item->posAnggaran?->kode ?? '';
            $kelompok = $l3KeKelompok[$kodeL3] ?? null;
            if ($kelompok !== null) {
                $terpakaiPerKelompok[$kelompok] += (int) $item->total;
            }
        }

        $daftarKelompok = [];
        $totalTerpakaiPenyaluran = 0;
        $semuaKelompokPas = true;
        foreach ($kelompokConfig as $kodeKelompok => $cfg) {
            $pagu = (int) ($paguPerKelompok[$kodeKelompok] ?? 0);
            $terpakai = (int) ($terpakaiPerKelompok[$kodeKelompok] ?? 0);
            $selisih = $terpakai - $pagu;
            $pas = $selisih === 0;
            if (! $pas) {
                $semuaKelompokPas = false;
            }
            $totalTerpakaiPenyaluran += $terpakai;

            $daftarKelompok[] = [
                'kode' => $kodeKelompok,
                'label' => $cfg['label'],
                'kode_pos_l3' => $cfg['kode_pos_l3'],
                'pagu' => $pagu,
                'terpakai' => $terpakai,
                'selisih' => $selisih,
                'terpenuhi' => $pas,
            ];
        }

        // 3. Agregasi Bidang SAU (Operasional = Hak Amil + Hibah + Saldo Awal)
        $tambahanSau = (int) $pelengkap
            ->where('bidang_kode', 'umum')
            ->sum('nilai');
        $totalPaguSau = $totalHakAmil + $tambahanSau;
        $totalTerpakaiSau = (int) $kegiatan
            ->where('bidang_kode', 'umum')
            ->sum('total');
        $selisihSau = $totalTerpakaiSau - $totalPaguSau;

        return [
            'tahap' => $tahap,
            'pengumpulan' => [
                'target' => $target,
                'total_neraca' => $totalNeraca,
                'selisih' => $selisihPengumpulan,
                'terpenuhi' => $target > 0 && $selisihPengumpulan === 0,
                'total_hak_amil' => $totalHakAmil,
                'total_alokasi_penyaluran' => $totalAlokasiPenyaluran,
                'total_luar_neraca' => $totalLuarNeraca,
                'rincian_amil' => $rincianAmil,
                'hak_amil_l3' => $hakAmilL3,
                'hak_amil_l6' => $hakAmilL6,
                'orang_l5' => $orangL5,
            ],
            'distribusi' => [
                'terbuka' => $pengumpulanTerverifikasi,
                'total_pagu' => $totalAlokasiPenyaluran,
                'total_terpakai' => $totalTerpakaiPenyaluran,
                'selisih' => $totalTerpakaiPenyaluran - $totalAlokasiPenyaluran,
                'terpenuhi' => $pengumpulanTerverifikasi && $totalAlokasiPenyaluran > 0 && $semuaKelompokPas,
                'kelompok' => $daftarKelompok,
            ],
            'umum' => [
                'terbuka' => $pengumpulanTerverifikasi,
                'pagu_hak_amil' => $totalHakAmil,
                'tambahan_hibah_saldo' => $tambahanSau,
                'total_pagu' => $totalPaguSau,
                'terpakai' => $totalTerpakaiSau,
                'selisih' => $selisihSau,
                'terpenuhi' => $pengumpulanTerverifikasi && $totalPaguSau > 0 && $selisihSau === 0,
            ],
        ];
    }
}
