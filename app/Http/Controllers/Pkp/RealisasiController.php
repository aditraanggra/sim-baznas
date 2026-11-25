<?php

namespace App\Http\Controllers\Pkp;

use App\Actions\Perencanaan\KunciRealisasiManual;
use App\Actions\Perencanaan\SimpanRealisasiManual;
use App\Exports\RealisasiRkatExport;
use App\Http\Controllers\Controller;
use App\Models\PosAnggaran;
use App\Models\RealisasiManual;
use App\Models\TahunAnggaran;
use App\Services\Pdf\DokumenPdfService;
use App\Services\Perencanaan\RealisasiRkatService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RealisasiController extends Controller
{
    public function index(Request $request, RealisasiRkatService $service): Response
    {
        $tahun = (int) ($request->input('tahun') ?: (TahunAnggaran::where('status', TahunAnggaran::STATUS_BERJALAN)->value('tahun') ?: date('Y')));
        $bulan = $request->filled('bulan') ? (int) $request->input('bulan') : null;
        $lampiran = $request->filled('lampiran') ? (string) $request->input('lampiran') : null;
        $user = $request->user();

        $data = $service->hitung($tahun, $bulan, $lampiran);
        $tahunList = TahunAnggaran::orderByDesc('tahun')->get(['id', 'tahun', 'status']);
        $bulanInput = $bulan ?? (int) date('n');

        $manualBulanIni = RealisasiManual::where('tahun', $tahun)
            ->where('bulan', $bulanInput)
            ->get()
            ->mapWithKeys(fn (RealisasiManual $m) => [
                $m->pos_anggaran_id => [
                    'nilai' => (int) $m->nilai,
                    'catatan' => $m->catatan,
                    'terkunci' => (bool) $m->terkunci,
                ],
            ])
            ->all();

        $statusKunciBulan = $bulan !== null
            ? RealisasiManual::where('tahun', $tahun)->where('bulan', $bulan)->where('terkunci', true)->exists()
            : false;

        return Inertia::render('pkp/realisasi/index', [
            'bidang' => config('sisfozis.bidang.pkp'),
            'tahun_terpilih' => $tahun,
            'bulan_terpilih' => $bulan,
            'bulan_input_default' => $bulanInput,
            'lampiran_terpilih' => $lampiran,
            'daftar_tahun' => $tahunList,
            'hasil' => $data,
            'manual_bulan_ini' => $manualBulanIni,
            'status_kunci_bulan' => $statusKunciBulan,
            'pos_daun' => PosAnggaran::where('aktif', true)
                ->where('agregasi', 'input_langsung')
                ->orderBy('lampiran')
                ->orderBy('urutan')
                ->get(['id', 'kode', 'nama', 'lampiran', 'tipe_nilai']),
            'akses' => [
                'boleh_tulis' => $user?->bolehAksiBidang('pkp', 'create') ?? false,
                'boleh_kunci' => $user?->bolehAksiBidang('pkp', 'approve') ?? false,
            ],
        ]);
    }

    public function storeManual(Request $request, SimpanRealisasiManual $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Nilai realisasi manual berhasil disimpan.');
    }

    public function kunci(Request $request, KunciRealisasiManual $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Status kunci periode realisasi berhasil diperbarui.');
    }

    public function cetakPdf(Request $request, RealisasiRkatService $service, DokumenPdfService $pdfService): HttpResponse
    {
        $tahun = (int) ($request->input('tahun') ?: date('Y'));
        $bulan = $request->filled('bulan') ? (int) $request->input('bulan') : null;
        $lampiran = $request->filled('lampiran') ? (string) $request->input('lampiran') : null;

        $data = $service->hitung($tahun, $bulan, $lampiran);
        $periodeTeks = $this->formatPeriode($tahun, $bulan);
        $namaBerkas = "RKAT-vs-Realisasi-{$tahun}".($bulan ? "-Bulan-{$bulan}" : '').'.pdf';

        return $pdfService->stream('pdf.realisasi-rkat', $namaBerkas, [
            'judul' => 'LAPORAN RKAT VS REALISASI',
            'tahun' => $tahun,
            'periode' => $periodeTeks,
            'lampiran' => $lampiran,
            'hasil' => $data,
        ], [
            'kertas' => 'a4',
            'orientasi' => 'landscape',
            'draft' => $data['status_tahun'] !== TahunAnggaran::STATUS_TUTUP,
        ]);
    }

    public function eksporExcel(Request $request, RealisasiRkatService $service): HttpResponse
    {
        $tahun = (int) ($request->input('tahun') ?: date('Y'));
        $bulan = $request->filled('bulan') ? (int) $request->input('bulan') : null;
        $lampiran = $request->filled('lampiran') ? (string) $request->input('lampiran') : null;

        $data = $service->hitung($tahun, $bulan, $lampiran);
        $periodeTeks = $this->formatPeriode($tahun, $bulan);
        $namaBerkas = "RKAT-vs-Realisasi-{$tahun}".($bulan ? "-Bulan-{$bulan}" : '').'.xlsx';

        return Excel::download(new RealisasiRkatExport($data, $periodeTeks), $namaBerkas);
    }

    private function formatPeriode(int $tahun, ?int $bulan): string
    {
        if ($bulan === null) {
            return "Tahun Anggaran {$tahun} (Januari – Desember)";
        }

        $namaBulan = Carbon::createFromDate($tahun, $bulan, 1)->locale('id')->translatedFormat('F');

        return "Januari – {$namaBulan} {$tahun}";
    }
}
