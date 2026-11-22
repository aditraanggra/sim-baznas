<?php

namespace App\Http\Controllers\Distribusi;

use App\Actions\Distribusi\BatalkanBeritaAcaraPenyaluran;
use App\Actions\Distribusi\BuatBeritaAcaraPenyaluran;
use App\Actions\Distribusi\FinalkanBeritaAcaraPenyaluran;
use App\Actions\Distribusi\UbahBeritaAcaraPenyaluran;
use App\Http\Controllers\Controller;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Penyaluran;
use App\Services\Pdf\DokumenPdfService;
use App\Services\Terbilang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class BeritaAcaraPenyaluranController extends Controller
{
    public function index(Request $request): Response
    {
        $daftar = BeritaAcaraPenyaluran::query()
            ->with(['pembuatDokumen:id,name', 'penanggungJawab:id,name'])
            ->orderByDesc('tanggal')
            ->orderByDesc('revisi_ke')
            ->limit(60)
            ->get()
            ->map(fn (BeritaAcaraPenyaluran $ba) => [
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

        return Inertia::render('distribusi/berita-acara/index', [
            'berita_acara' => $daftar,
            'bisa_kelola' => $request->user()->bolehAksiBidang('distribusi', 'create'),
            'bisa_batalkan_final' => $request->user()->hasAnyRole(BatalkanBeritaAcaraPenyaluran::PERAN_BATALKAN_FINAL),
        ]);
    }

    public function update(Request $request, BeritaAcaraPenyaluran $beritaAcara, UbahBeritaAcaraPenyaluran $action): RedirectResponse
    {
        $ba = $action->jalankan([
            'id' => $beritaAcara->id,
            'catatan' => $request->input('catatan'),
            'penanggung_jawab_id' => $request->input('penanggung_jawab_id'),
        ]);

        return back()->with('sukses', "Draft Berita Acara {$ba->nomor} diperbarui.");
    }

    public function batalkan(Request $request, BeritaAcaraPenyaluran $beritaAcara, BatalkanBeritaAcaraPenyaluran $action): RedirectResponse
    {
        $ba = $action->jalankan([
            'id' => $beritaAcara->id,
            'alasan' => $request->input('alasan'),
        ]);

        return back()->with('sukses', "Berita Acara {$ba->nomor} dibatalkan. Tanggal {$ba->tanggal->format('d/m/Y')} dapat dibuatkan berita acara baru.");
    }

    public function store(Request $request, BuatBeritaAcaraPenyaluran $action): RedirectResponse
    {
        $ba = $action->jalankan($request->all());

        return back()->with('sukses', "Draft Berita Acara {$ba->nomor} berhasil dibuat.");
    }

    public function finalkan(Request $request, BeritaAcaraPenyaluran $beritaAcara, FinalkanBeritaAcaraPenyaluran $action): RedirectResponse
    {
        $ba = $action->jalankan([
            'id' => $beritaAcara->id,
            'catatan' => $request->input('catatan'),
        ]);

        return back()->with('sukses', "Berita Acara {$ba->nomor} difinalkan dan tanggal dikunci.");
    }

    public function pdf(BeritaAcaraPenyaluran $beritaAcara, DokumenPdfService $pdfService): HttpResponse
    {
        $beritaAcara->load(['penanggungJawab:id,name', 'pembuatDokumen:id,name']);

        $transaksi = Penyaluran::query()
            ->with(['mustahik:id,nama', 'program:id,nama', 'items.asnaf:id,nama'])
            ->whereDate('tanggal', $beritaAcara->tanggal)
            ->where('status', 'dicairkan')
            ->orderBy('id')
            ->get();

        $rekapProgram = $transaksi->flatMap(function (Penyaluran $py) {
            return $py->items->map(fn ($item) => [
                'program' => $py->program?->nama ?? '-',
                'asnaf' => $item->asnaf?->nama ?? 'Non-Zakat',
                'jumlah' => (int) $item->jumlah,
            ]);
        })
            ->groupBy(fn ($r) => $r['program'].'|'.$r['asnaf'])
            ->map(fn ($items) => [
                'program' => $items->first()['program'],
                'asnaf' => $items->first()['asnaf'],
                'total' => (int) $items->sum('jumlah'),
            ])
            ->values()
            ->all();

        $namaBerkas = str_replace('/', '-', $beritaAcara->nomor).'.pdf';

        return $pdfService->stream('pdf.berita-acara-penyaluran', $namaBerkas, [
            'judulDokumen' => 'Berita Acara Penyaluran Harian',
            'ba' => $beritaAcara,
            'transaksi' => $transaksi,
            'rekap_program' => $rekapProgram,
            'nominal' => (int) $beritaAcara->total_rupiah,
            'terbilang' => Terbilang::rupiah((int) $beritaAcara->total_rupiah),
            'penandatangan' => [
                [
                    'jabatan' => 'Pelaksana Pendistribusian',
                    'nama' => $beritaAcara->pembuatDokumen?->name ?? 'Petugas',
                ],
                [
                    'jabatan' => 'Penanggung Jawab',
                    'nama' => $beritaAcara->penanggungJawab?->name ?? 'Kepala Bagian',
                ],
            ],
        ], [
            'draft' => $beritaAcara->status === 'draft',
            'qr_payload' => "BA-PENYALURAN|{$beritaAcara->nomor}|{$beritaAcara->total_rupiah}",
            'qr_label' => 'Verifikasi BA Penyaluran',
        ]);
    }
}
