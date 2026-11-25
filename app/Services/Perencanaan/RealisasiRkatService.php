<?php

namespace App\Services\Perencanaan;

use App\Models\PosAnggaran;
use App\Models\RkatValue;
use App\Models\RkatVersion;
use App\Models\TahunAnggaran;
use App\Services\Rkat\HitungRkat;
use Illuminate\Support\Facades\DB;

class RealisasiRkatService
{
    /**
     * Mengambil tabel baris RKAT vs Realisasi lengkap (pos pohon L2-L7) berbasis view SQL v_realisasi_rkat.
     *
     * @return array{
     *     tahun: int,
     *     bulan: ?int,
     *     status_tahun: string,
     *     versi_rkat: ?string,
     *     total_rkat: int,
     *     total_realisasi: int,
     *     persen_total: ?float,
     *     persen_total_label: string,
     *     ringkasan_lampiran: array<string, array{target_rkat: int, realisasi: int, selisih: int, persen: ?float, persen_label: string}>,
     *     baris: list<array<string, mixed>>
     * }
     */
    public function hitung(int $tahun, ?int $bulan = null, ?string $lampiran = null): array
    {
        $tahunAnggaran = TahunAnggaran::where('tahun', $tahun)->first();
        $statusTahun = $tahunAnggaran?->status ?? 'draft';

        $rkat = RkatVersion::where('tahun', $tahun)
            ->where('status', 'disahkan')
            ->latest('id')
            ->first()
            ?? RkatVersion::where('tahun', $tahun)->latest('id')->first();

        $daunRkat = $rkat
            ? RkatValue::where('rkat_version_id', $rkat->id)
                ->whereNotNull('pos_anggaran_id')
                ->pluck('nilai', 'pos_anggaran_id')
                ->map(fn ($v) => (int) $v)
                ->all()
            : [];

        $semuaPos = PosAnggaran::query()
            ->with(['pemetaan'])
            ->where('aktif', true)
            ->get()
            ->sortBy(fn (PosAnggaran $p) => [$p->lampiran, HitungRkat::grup($p) === 'L2/LN' ? 1 : 0, $p->urutan, $p->id])
            ->values();

        // Hitung target RKAT untuk seluruh pohon (daun + induk)
        $targetRkat = HitungRkat::agregat($semuaPos, $daunRkat);

        // Ambil agregasi realisasi daun dari view PostgreSQL v_realisasi_rkat dalam 1 query
        $realisasiViewRows = DB::table('v_realisasi_rkat')
            ->selectRaw('pos_anggaran_id, COALESCE(SUM(nilai_otomatis), 0)::bigint AS nilai_otomatis, COALESCE(SUM(nilai_manual), 0)::bigint AS nilai_manual, COALESCE(SUM(nilai_realisasi), 0)::bigint AS nilai_realisasi')
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn ($q) => $q->where('bulan', '<=', $bulan))
            ->groupBy('pos_anggaran_id')
            ->get()
            ->keyBy('pos_anggaran_id');

        $daunRealisasi = $realisasiViewRows
            ->mapWithKeys(fn ($row) => [(int) $row->pos_anggaran_id => (int) $row->nilai_realisasi])
            ->all();

        // Rollup nilai realisasi dari daun ke induk
        $realisasiFinal = HitungRkat::agregat($semuaPos, $daunRealisasi);

        // Susun ringkasan per grup lampiran (L2 terpisah dari L2/LN)
        $ringkasanLampiran = [];
        foreach (['L2', 'L2/LN', 'L3', 'L4', 'L5', 'L6', 'L7'] as $grupKode) {
            $akarGrup = $semuaPos->filter(fn (PosAnggaran $p) => $p->induk_id === null && HitungRkat::grup($p) === $grupKode);
            $punyaRupiah = $akarGrup->contains(fn (PosAnggaran $p) => $p->tipe_nilai === 'rupiah');
            $akarDihitung = $punyaRupiah ? $akarGrup->where('tipe_nilai', 'rupiah') : $akarGrup;

            $tGrup = (int) $akarDihitung->sum(fn (PosAnggaran $p) => $targetRkat[$p->id] ?? 0);
            $rGrup = (int) $akarDihitung->sum(fn (PosAnggaran $p) => $realisasiFinal[$p->id] ?? 0);
            $pGrup = HitungRkat::persen($rGrup, $tGrup);

            $ringkasanLampiran[$grupKode] = [
                'target_rkat' => $tGrup,
                'realisasi' => $rGrup,
                'selisih' => $tGrup - $rGrup,
                'persen' => $pGrup,
                'persen_label' => HitungRkat::tampil($pGrup),
            ];
        }

        // Susun data baris presentasi sesuai filter lampiran bila ada
        $posTampil = $semuaPos->when($lampiran, function ($koleksi) use ($lampiran) {
            if ($lampiran === 'L2/LN') {
                return $koleksi->filter(fn (PosAnggaran $p) => HitungRkat::grup($p) === 'L2/LN')->values();
            }

            return $koleksi->where('lampiran', $lampiran)->values();
        });

        $baris = [];
        foreach ($posTampil as $pos) {
            $target = (int) ($targetRkat[$pos->id] ?? 0);
            $realisasi = (int) ($realisasiFinal[$pos->id] ?? 0);
            $selisih = $target - $realisasi;
            $persen = HitungRkat::persen($realisasi, $target);
            $viewBaris = $realisasiViewRows->get($pos->id);

            $baris[] = [
                'id' => $pos->id,
                'kode' => $pos->kode,
                'nama' => $pos->nama,
                'lampiran' => $pos->lampiran,
                'grup' => HitungRkat::grup($pos),
                'tingkat' => $pos->tingkat,
                'induk_id' => $pos->induk_id,
                'path' => $pos->path,
                'tipe_nilai' => $pos->tipe_nilai,
                'agregasi' => $pos->agregasi,
                'sumber' => $pos->pemetaan?->sumber_transaksi ?? 'manual',
                'nilai_otomatis' => (int) ($viewBaris->nilai_otomatis ?? 0),
                'nilai_manual' => (int) ($viewBaris->nilai_manual ?? 0),
                'boleh_manual' => $pos->agregasi === 'input_langsung',
                'target_rkat' => $target,
                'realisasi' => $realisasi,
                'selisih' => $selisih,
                'persen' => $persen,
                'persen_label' => HitungRkat::tampil($persen),
            ];
        }

        $totalRkatL2 = $ringkasanLampiran['L2']['target_rkat'];
        $totalRealisasiL2 = $ringkasanLampiran['L2']['realisasi'];
        $persenTotal = HitungRkat::persen($totalRealisasiL2, $totalRkatL2);

        return [
            'tahun' => $tahun,
            'bulan' => $bulan,
            'status_tahun' => $statusTahun,
            'versi_rkat' => $rkat?->label(),
            'total_rkat' => $totalRkatL2,
            'total_realisasi' => $totalRealisasiL2,
            'persen_total' => $persenTotal,
            'persen_total_label' => HitungRkat::tampil($persenTotal),
            'ringkasan_lampiran' => $ringkasanLampiran,
            'baris' => $baris,
        ];
    }
}
