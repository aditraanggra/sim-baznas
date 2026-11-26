<?php

namespace App\Http\Controllers\Pkp;

use App\Actions\Arsip\CabutDokumenArsip;
use App\Actions\Arsip\SahkanDokumenArsip;
use App\Actions\Arsip\SimpanDokumenArsip;
use App\Http\Controllers\Controller;
use App\Models\AksesDokumenLog;
use App\Models\Bidang;
use App\Models\DokumenArsip;
use App\Models\KategoriDokumen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ArsipDokumenController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $kategoriId = $request->filled('kategori_id') ? (int) $request->query('kategori_id') : null;
        $tahun = $request->filled('tahun') ? (int) $request->query('tahun') : null;
        $status = (string) $request->query('status', 'semua');

        $daftarDokumen = DokumenArsip::query()
            ->with([
                'kategori:id,kode,nama',
                'bidang:id,nama',
                'berkas:id,uuid,nama_asli,ukuran',
                'pengunggah:id,name',
                'pengesah:id,name',
                'pencabut:id,name',
                'indukVersi:id,judul,versi',
            ])
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($sub) use ($q): void {
                    $sub->where('judul', 'ilike', "%{$q}%")
                        ->orWhere('nomor', 'ilike', "%{$q}%")
                        ->orWhere('ringkasan', 'ilike', "%{$q}%");
                });
            })
            ->when($kategoriId !== null, fn ($query) => $query->where('kategori_dokumen_id', $kategoriId))
            ->when($tahun !== null, fn ($query) => $query->where('tahun', $tahun))
            ->when(
                in_array($status, [DokumenArsip::STATUS_DRAFT, DokumenArsip::STATUS_DISAHKAN, DokumenArsip::STATUS_DICABUT], true),
                fn ($query) => $query->where('status', $status),
            )
            ->orderByDesc('tahun')
            ->orderByDesc('id')
            ->get()
            ->map(fn (DokumenArsip $d) => [
                'id' => $d->id,
                'uuid' => $d->uuid,
                'kategori_dokumen_id' => $d->kategori_dokumen_id,
                'kategori' => $d->kategori?->nama,
                'bidang_id' => $d->bidang_id,
                'bidang' => $d->bidang?->nama,
                'tahun' => $d->tahun,
                'nomor' => $d->nomor,
                'judul' => $d->judul,
                'versi' => $d->versi,
                'induk_versi_id' => $d->induk_versi_id,
                'induk_versi_label' => $d->indukVersi ? "v{$d->indukVersi->versi}" : null,
                'tanggal_pengesahan' => $d->tanggal_pengesahan?->format('Y-m-d'),
                'tanggal_pengesahan_label' => $d->tanggal_pengesahan?->format('d/m/Y'),
                'ringkasan' => $d->ringkasan,
                'status' => $d->status,
                'ukuran' => $d->berkas?->ukuran ?? 0,
                'nama_berkas' => $d->berkas?->nama_asli ?? 'dokumen.pdf',
                'pengunggah' => $d->pengunggah?->name,
                'pengesah' => $d->pengesah?->name,
                'disahkan_at' => $d->disahkan_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'pencabut' => $d->pencabut?->name,
                'alasan_cabut' => $d->alasan_cabut,
                'url_lihat' => route('pkp.arsip.berkas', ['dokumen' => $d->id, 'aksi' => 'lihat']),
                'url_cetak' => route('pkp.arsip.berkas', ['dokumen' => $d->id, 'aksi' => 'cetak']),
                'url_unduh' => route('pkp.arsip.berkas', ['dokumen' => $d->id, 'aksi' => 'unduh']),
            ])
            ->all();

        $daftarTahun = DokumenArsip::query()
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->all();

        $logTerbaru = AksesDokumenLog::query()
            ->with(['dokumen:id,judul,nomor,versi', 'tautanAkses:id,label', 'user:id,name'])
            ->where('konteks', 'arsip')
            ->latest('waktu')
            ->limit(15)
            ->get()
            ->map(fn (AksesDokumenLog $log) => [
                'id' => $log->id,
                'dokumen_judul' => $log->dokumen?->judul ?? '-',
                'dokumen_versi' => $log->dokumen?->versi,
                'aksi' => $log->aksi,
                'pelaku' => $log->user?->name ?? ($log->tautanAkses ? "Tautan: {$log->tautanAkses->label}" : 'Tamu'),
                'waktu' => $log->waktu?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'ip_address' => $log->ip_address,
            ])
            ->all();

        return Inertia::render('pkp/arsip/index', [
            'bidang' => config('sisfozis.bidang.pkp'),
            'filter' => [
                'q' => $q,
                'kategori_id' => $kategoriId,
                'tahun' => $tahun,
                'status' => $status,
            ],
            'daftar_kategori' => KategoriDokumen::where('aktif', true)->orderBy('urutan')->get(['id', 'kode', 'nama']),
            'daftar_bidang' => Bidang::where('aktif', true)->orderBy('urutan')->get(['id', 'kode', 'nama']),
            'daftar_tahun' => $daftarTahun,
            'dokumen' => $daftarDokumen,
            'log_terbaru' => $logTerbaru,
            'akses' => [
                'boleh_tulis' => $user?->bolehAksiBidang('pkp', 'create') ?? false,
                'boleh_sah' => $user?->bolehAksiBidang('pkp', 'approve') ?? false,
            ],
        ]);
    }

    public function simpan(Request $request, SimpanDokumenArsip $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Dokumen arsip PKP berhasil disimpan.');
    }

    public function sahkan(Request $request, DokumenArsip $dokumen, SahkanDokumenArsip $action): RedirectResponse
    {
        $action->jalankan([
            ...$request->all(),
            'id' => $dokumen->id,
        ]);

        return back()->with('sukses', 'Dokumen arsip PKP resmi disahkan dan siap diakses pimpinan.');
    }

    public function cabut(Request $request, DokumenArsip $dokumen, CabutDokumenArsip $action): RedirectResponse
    {
        $action->jalankan([
            ...$request->all(),
            'id' => $dokumen->id,
        ]);

        return back()->with('sukses', 'Dokumen arsip PKP berhasil dicabut dari peredaran aktif.');
    }

    public function berkas(Request $request, DokumenArsip $dokumen): HttpResponse
    {
        $berkas = $dokumen->berkas;
        abort_unless($berkas !== null && Storage::disk($berkas->disk)->exists($berkas->path), 404, 'Berkas fisik tidak ditemukan.');

        $aksi = in_array($request->query('aksi'), ['lihat', 'cetak', 'unduh'], true)
            ? (string) $request->query('aksi')
            : 'lihat';

        AksesDokumenLog::create([
            'dokumen_arsip_id' => $dokumen->id,
            'tautan_akses_id' => null,
            'user_id' => $request->user()?->id,
            'konteks' => 'arsip',
            'aksi' => $aksi,
            'tahun_filter' => $dokumen->tahun,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'waktu' => now(),
        ]);

        if ($aksi === 'unduh') {
            return Storage::disk($berkas->disk)->download($berkas->path, $berkas->nama_asli);
        }

        return Storage::disk($berkas->disk)->response($berkas->path, $berkas->nama_asli, [
            'Content-Type' => $berkas->mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($berkas->nama_asli).'"',
        ]);
    }
}
