<?php

namespace App\Http\Controllers\Distribusi;

use App\Exports\LaporanPendistribusianExport;
use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use App\Models\JenisDana;
use App\Models\Kecamatan;
use App\Models\Program;
use App\Models\SumberDana;
use App\Services\LaporanPendistribusianService;
use App\Services\Pdf\DokumenPdfService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LaporanPendistribusianController extends Controller
{
    public function index(Request $request, LaporanPendistribusianService $laporanService): Response
    {
        $filter = $request->only([
            'jenis', 'tahun', 'bulan', 'triwulan', 'semester', 'tanggal',
            'sub_bidang', 'program_id', 'jenis_dana_id', 'sumber_dana_id', 'asnaf_id', 'kecamatan_id',
        ]);
        $ringkas = $laporanService->ringkas($filter);

        return Inertia::render('distribusi/laporan', [
            'laporan' => $ringkas,
            'filter' => $filter,
            'program_list' => Program::where('aktif', true)->orderBy('kode')->get(['id', 'nama', 'sub_bidang']),
            'jenis_dana_list' => JenisDana::where('aktif', true)->orderBy('id')->get(['id', 'nama']),
            'sumber_dana_list' => SumberDana::where('aktif', true)->orderBy('id')->get(['id', 'nama']),
            'asnaf_list' => Asnaf::where('aktif', true)->orderBy('urutan')->get(['id', 'nama']),
            'kecamatan_list' => Kecamatan::where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
            'bisa_ekspor' => $request->user()->bolehAksiBidang('distribusi', 'export'),
        ]);
    }

    public function pdf(Request $request, LaporanPendistribusianService $laporanService, DokumenPdfService $pdfService): HttpResponse
    {
        $filter = $request->only([
            'jenis', 'tahun', 'bulan', 'triwulan', 'semester', 'tanggal',
            'sub_bidang', 'program_id', 'jenis_dana_id', 'sumber_dana_id', 'asnaf_id', 'kecamatan_id',
        ]);
        $ringkas = $laporanService->ringkas($filter);

        return $pdfService->unduh('pdf.laporan-pendistribusian', 'laporan-pendistribusian-'.$ringkas['periode']['tahun'].'.pdf', [
            'judulDokumen' => 'Laporan Pendistribusian dan Pendayagunaan',
            'label_periode' => $ringkas['periode']['label'],
            'nominal' => $ringkas['total_nominal'],
            'jumlah_transaksi' => $ringkas['jumlah_transaksi'],
            'jumlah_mustahik' => $ringkas['jumlah_mustahik'],
            'baris' => $ringkas['baris'],
        ], [
            'orientasi' => 'landscape',
        ]);
    }

    public function excel(Request $request, LaporanPendistribusianService $laporanService): BinaryFileResponse
    {
        $filter = $request->only([
            'jenis', 'tahun', 'bulan', 'triwulan', 'semester', 'tanggal',
            'sub_bidang', 'program_id', 'jenis_dana_id', 'sumber_dana_id', 'asnaf_id', 'kecamatan_id',
        ]);
        $ringkas = $laporanService->ringkas($filter);

        return Excel::download(
            new LaporanPendistribusianExport($ringkas),
            'laporan-pendistribusian-'.$ringkas['periode']['tahun'].'.xlsx',
        );
    }
}
