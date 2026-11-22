<?php

namespace App\Http\Controllers\Pengumpulan;

use App\Http\Controllers\Controller;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\PembatalanPenerimaan;
use App\Models\Penerimaan;
use App\Services\LaporanPenerimaanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DasborPengumpulanController extends Controller
{
    public function index(Request $request, LaporanPenerimaanService $laporanService): Response
    {
        $tahun = (int) $request->session()->get('tahun_aktif', date('Y'));
        $bulan = (int) date('n');
        $hariIni = now()->toDateString();

        $ringkasanBulan = $laporanService->ringkas([
            'jenis' => 'bulanan',
            'tahun' => $tahun,
            'bulan' => $bulan,
        ]);

        $totalHariIni = (int) DB::table('penerimaan_item as pi')
            ->join('penerimaan as p', 'p.id', '=', 'pi.penerimaan_id')
            ->where('p.status', 'final')
            ->whereDate('p.tanggal', $hariIni)
            ->sum('pi.jumlah');

        $transaksiHariIni = Penerimaan::where('status', 'final')->whereDate('tanggal', $hariIni)->count();
        $pembatalanMenunggu = PembatalanPenerimaan::with(['penerimaan.muzakki:id,nama', 'peminta:id,name'])
            ->where('status', 'menunggu')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (PembatalanPenerimaan $pb) => [
                'id' => $pb->id,
                'penerimaan_id' => $pb->penerimaan_id,
                'nomor' => $pb->penerimaan?->nomor,
                'muzakki' => $pb->penerimaan?->muzakki?->nama,
                'alasan' => $pb->alasan,
                'peminta' => $pb->peminta?->name,
            ]);

        $baDraft = BeritaAcaraPenerimaan::where('status', 'draft')
            ->latest('tanggal')
            ->limit(5)
            ->get()
            ->map(fn (BeritaAcaraPenerimaan $ba) => [
                'id' => $ba->id,
                'nomor' => $ba->nomor,
                'tanggal' => $ba->tanggal?->format('d/m/Y'),
                'total_rupiah' => $ba->total_rupiah,
            ]);

        $draftPenerimaan = Penerimaan::where('status', 'draft')->count();

        return Inertia::render('pengumpulan/dasbor', [
            'kpi' => [
                'penerimaan_hari_ini' => $totalHariIni,
                'transaksi_hari_ini' => $transaksiHariIni,
                'penerimaan_bulan_ini' => $ringkasanBulan['total_nominal'],
                'transaksi_bulan_ini' => $ringkasanBulan['jumlah_transaksi'],
                'draft_belum_final' => $draftPenerimaan,
                'pembatalan_menunggu' => $pembatalanMenunggu->count(),
            ],
            'tren_harian' => $ringkasanBulan['tren_harian'],
            'komposisi_sumber_dana' => array_slice($ringkasanBulan['per_sumber_dana'], 0, 6),
            'pembatalan_list' => $pembatalanMenunggu,
            'ba_draft' => $baDraft,
            'label_bulan' => $ringkasanBulan['periode']['label'],
        ]);
    }
}
