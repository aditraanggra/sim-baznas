<?php

namespace App\Http\Controllers\Pengumpulan;

use App\Actions\Pengumpulan\BatalkanBeritaAcaraPenerimaan;
use App\Actions\Pengumpulan\BuatBeritaAcaraPenerimaan;
use App\Actions\Pengumpulan\FinalkanBeritaAcaraPenerimaan;
use App\Actions\Pengumpulan\UbahBeritaAcaraPenerimaan;
use App\Http\Controllers\Controller;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\Penerimaan;
use App\Services\Pdf\DokumenPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BeritaAcaraPenerimaanController extends Controller
{
    public function index(Request $request): Response
    {
        $daftar = BeritaAcaraPenerimaan::query()
            ->with(['pembuatDokumen:id,name', 'penanggungJawab:id,name'])
            ->orderByDesc('tanggal')
            ->orderByDesc('revisi_ke')
            ->limit(60)
            ->get()
            ->map(fn (BeritaAcaraPenerimaan $ba) => [
                'id' => $ba->id,
                'nomor' => $ba->nomor,
                'tanggal' => $ba->tanggal?->format('Y-m-d'),
                'tanggal_tampil' => $ba->tanggal?->format('d/m/Y'),
                'revisi_ke' => $ba->revisi_ke,
                'status' => $ba->status,
                'total_rupiah' => $ba->total_rupiah,
                'jumlah_transaksi' => $ba->jumlah_transaksi,
                'penanggung_jawab' => $ba->penanggungJawab?->name,
                'catatan' => $ba->catatan,
                'alasan_batal' => $ba->alasan_batal,
            ]);

        return Inertia::render('pengumpulan/berita-acara/index', [
            'berita_acara' => $daftar,
            'bisa_kelola' => $request->user()->bolehAksiBidang('pengumpulan', 'create'),
            'bisa_batalkan_final' => $request->user()->hasAnyRole(BatalkanBeritaAcaraPenerimaan::PERAN_BATALKAN_FINAL),
        ]);
    }

    public function update(Request $request, BeritaAcaraPenerimaan $beritaAcara, UbahBeritaAcaraPenerimaan $action): RedirectResponse
    {
        $ba = $action->jalankan([
            'id' => $beritaAcara->id,
            'catatan' => $request->input('catatan'),
            'penanggung_jawab_id' => $request->input('penanggung_jawab_id'),
        ]);

        return back()->with('sukses', "Draft Berita Acara {$ba->nomor} diperbarui.");
    }

    public function batalkan(Request $request, BeritaAcaraPenerimaan $beritaAcara, BatalkanBeritaAcaraPenerimaan $action): RedirectResponse
    {
        $ba = $action->jalankan([
            'id' => $beritaAcara->id,
            'alasan' => $request->input('alasan'),
        ]);

        return back()->with('sukses', "Berita Acara {$ba->nomor} dibatalkan. Tanggal {$ba->tanggal->format('d/m/Y')} dapat dibuatkan berita acara baru.");
    }

    public function store(Request $request, BuatBeritaAcaraPenerimaan $action): RedirectResponse
    {
        $ba = $action->jalankan($request->all());

        return back()->with('sukses', "Draft Berita Acara {$ba->nomor} berhasil dibuat.");
    }

    public function finalkan(Request $request, BeritaAcaraPenerimaan $beritaAcara, FinalkanBeritaAcaraPenerimaan $action): RedirectResponse
    {
        $ba = $action->jalankan([
            'id' => $beritaAcara->id,
            'catatan' => $request->input('catatan'),
        ]);

        return back()->with('sukses', "Berita Acara {$ba->nomor} difinalkan dan tanggal dikunci.");
    }

    public function pdf(BeritaAcaraPenerimaan $beritaAcara, DokumenPdfService $pdfService): HttpResponse
    {
        $beritaAcara->load(['penanggungJawab:id,name', 'pembuatDokumen:id,name']);

        $transaksi = Penerimaan::query()
            ->with(['muzakki:id,nama', 'metodeBayar:id,nama', 'kwitansi:id,penerimaan_id,nomor', 'items.sumberDana.jenisDana'])
            ->whereDate('tanggal', $beritaAcara->tanggal)
            ->where('status', 'final')
            ->orderBy('id')
            ->get();

        $rekapSumber = $transaksi->flatMap(fn (Penerimaan $p) => $p->items)
            ->groupBy('sumber_dana_id')
            ->map(function ($items): array {
                $pertama = $items->first();

                return [
                    'sumber_dana' => $pertama?->sumberDana?->nama ?? '-',
                    'jenis_dana' => $pertama?->sumberDana?->jenisDana?->nama ?? '-',
                    'total' => (int) $items->sum('jumlah'),
                ];
            })
            ->values()
            ->all();

        $namaBerkas = str_replace('/', '-', $beritaAcara->nomor).'.pdf';

        return $pdfService->stream('pdf.berita-acara-penerimaan', $namaBerkas, [
            'judul' => 'BERITA ACARA PENERIMAAN HARIAN DANA ZIS',
            'nomor' => $beritaAcara->nomor,
            'ba' => $beritaAcara,
            'nominal' => $beritaAcara->total_rupiah,
            'rekap_sumber' => $rekapSumber,
            'transaksi' => $transaksi,
            'penandatangan' => [
                ['jabatan' => 'Dibuat Oleh', 'nama' => $beritaAcara->pembuatDokumen?->name ?? 'Petugas Pengumpulan'],
                ['jabatan' => 'Penanggung Jawab', 'nama' => $beritaAcara->penanggungJawab?->name ?? 'Kepala Bagian Pengumpulan'],
            ],
        ], [
            'kertas' => 'a4',
            'orientasi' => 'portrait',
            'draft' => $beritaAcara->status === 'draft',
            'qr_payload' => $beritaAcara->nomor,
        ]);
    }
}
