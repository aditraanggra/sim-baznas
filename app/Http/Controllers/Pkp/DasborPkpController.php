<?php

namespace App\Http\Controllers\Pkp;

use App\Http\Controllers\Controller;
use App\Services\Pdf\DokumenPdfService;
use App\Services\Pkp\LaporanKinerjaZisService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DasborPkpController extends Controller
{
    public function index(Request $request, LaporanKinerjaZisService $service): Response
    {
        $tahunInput = $request->filled('tahun') ? (int) $request->input('tahun') : null;
        $tahun = $service->tentukanTahunDefault($tahunInput);
        $data = $service->ringkasanTahunan($tahun);

        return Inertia::render('pkp/dasbor', [
            'bidang' => config('sisfozis.bidang.pkp'),
            ...$data,
        ]);
    }

    public function unduhPdf(Request $request, LaporanKinerjaZisService $service, DokumenPdfService $pdfService): HttpResponse
    {
        $tahunInput = $request->filled('tahun') ? (int) $request->input('tahun') : null;
        $tahun = $service->tentukanTahunDefault($tahunInput);
        $data = $service->ringkasanTahunan($tahun);

        $namaBerkas = "Ringkasan-Kinerja-ZIS-{$tahun}.pdf";

        return $pdfService->stream('pdf.ringkasan-kinerja-zis', $namaBerkas, [
            'judul' => "RINGKASAN KINERJA PENGELOLAAN ZIS TAHUN {$tahun}",
            'tahun' => $tahun,
            'kinerja' => $data,
        ], [
            'kertas' => 'a4',
            'orientasi' => 'portrait',
            'draft' => false,
        ]);
    }
}
