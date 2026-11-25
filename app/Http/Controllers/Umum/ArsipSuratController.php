<?php

namespace App\Http\Controllers\Umum;

use App\Exports\AgendaSuratExport;
use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\Administrasi\ArsipSuratService;
use App\Services\Pdf\DokumenPdfService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ArsipSuratController extends Controller
{
    public function index(Request $request, ArsipSuratService $arsipService, AksesPersuratanService $akses): Response
    {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        $filter = $this->ekstrakFilter($request);
        $hasil = $arsipService->cari($user, $filter);

        return Inertia::render('umum/surat/arsip', [
            'items' => $hasil['items'],
            'ringkasan' => $hasil['ringkasan'],
            'filter' => $filter,
            'jenis_list' => JenisSurat::where('aktif', true)->orderBy('kode')->get(['id', 'kode', 'nama']),
        ]);
    }

    public function exportExcel(Request $request, ArsipSuratService $arsipService, AksesPersuratanService $akses): HttpResponse
    {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        $filter = $this->ekstrakFilter($request);
        $hasil = $arsipService->cari($user, $filter, 1000);

        return Excel::download(new AgendaSuratExport($hasil['items']->all(), $filter), 'buku-agenda-surat.xlsx');
    }

    public function exportPdf(
        Request $request,
        ArsipSuratService $arsipService,
        AksesPersuratanService $akses,
        DokumenPdfService $pdfService,
    ): HttpResponse {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        $filter = $this->ekstrakFilter($request);
        $hasil = $arsipService->cari($user, $filter, 1000);

        return $pdfService->unduh('pdf.agenda-surat', 'buku-agenda-surat.pdf', [
            'judul' => 'BUKU AGENDA SURAT MASUK DAN SURAT KELUAR',
            'items' => $hasil['items']->all(),
            'ringkasan' => $hasil['ringkasan'],
            'filter' => $filter,
        ], [
            'kertas' => 'a4',
            'orientasi' => 'landscape',
            'tipografi' => 'sans',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function ekstrakFilter(Request $request): array
    {
        return [
            'q' => trim($request->string('q')->toString()),
            'tipe' => in_array($request->string('tipe')->toString(), ['semua', 'masuk', 'keluar'], true)
                ? $request->string('tipe')->toString()
                : 'semua',
            'jenis_surat_id' => trim($request->string('jenis_surat_id')->toString()),
            'sifat' => trim($request->string('sifat')->toString()),
            'status' => trim($request->string('status')->toString()),
            'dari' => trim($request->string('dari')->toString()),
            'sampai' => trim($request->string('sampai')->toString()),
        ];
    }
}
