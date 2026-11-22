<?php

namespace App\Http\Controllers\Pengumpulan;

use App\Exports\LaporanPenerimaanExport;
use App\Http\Controllers\Controller;
use App\Models\JenisDana;
use App\Models\MetodeBayar;
use App\Models\SumberDana;
use App\Models\Upz;
use App\Services\LaporanPenerimaanService;
use App\Services\Pdf\DokumenPdfService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LaporanPenerimaanController extends Controller
{
    public function index(Request $request, LaporanPenerimaanService $service): Response
    {
        $filter = $request->only([
            'jenis', 'tahun', 'bulan', 'triwulan', 'semester', 'tanggal',
            'jenis_dana_id', 'sumber_dana_id', 'upz_id', 'metode_bayar_id',
        ]);

        return Inertia::render('pengumpulan/laporan', [
            'laporan' => $service->ringkas($filter),
            'filter' => [
                'jenis_dana_id' => $request->input('jenis_dana_id', ''),
                'sumber_dana_id' => $request->input('sumber_dana_id', ''),
                'upz_id' => $request->input('upz_id', ''),
                'metode_bayar_id' => $request->input('metode_bayar_id', ''),
            ],
            'opsi' => [
                'jenis_dana' => JenisDana::where('aktif', true)->orderBy('id')->get(['id', 'nama']),
                'sumber_dana' => SumberDana::where('aktif', true)->orderBy('id')->get(['id', 'nama', 'jenis_dana_id']),
                'upz' => Upz::where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
                'metode_bayar' => MetodeBayar::where('aktif', true)->orderBy('id')->get(['id', 'nama']),
            ],
        ]);
    }

    public function pdf(Request $request, LaporanPenerimaanService $service, DokumenPdfService $pdfService): HttpResponse
    {
        $data = $service->ringkas($request->all());

        return $pdfService->unduh('pdf.laporan-penerimaan', 'laporan-penerimaan.pdf', [
            'judul' => 'LAPORAN PENERIMAAN DANA ZIS',
            'label_periode' => $data['periode']['label'],
            'nominal' => $data['total_nominal'],
            'jumlah_transaksi' => $data['jumlah_transaksi'],
            'baris' => $data['baris'],
        ], ['kertas' => 'a4', 'orientasi' => 'portrait']);
    }

    public function excel(Request $request, LaporanPenerimaanService $service): HttpResponse
    {
        $data = $service->ringkas($request->all());

        return Excel::download(new LaporanPenerimaanExport($data), 'laporan-penerimaan.xlsx');
    }
}
