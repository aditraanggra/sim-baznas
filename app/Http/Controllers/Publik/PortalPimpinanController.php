<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\AksesDokumenLog;
use App\Models\DokumenArsip;
use App\Models\KategoriDokumen;
use App\Models\TautanAkses;
use App\Services\Pdf\DokumenPdfService;
use App\Services\Pkp\LaporanKinerjaZisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class PortalPimpinanController extends Controller
{
    public function index(Request $request, string $token, LaporanKinerjaZisService $kinerjaService): Response
    {
        $resolved = $this->selesaikanTautan($request, $token);
        if ($resolved instanceof Response) {
            return $resolved;
        }

        $tautan = $resolved;
        $tautan->increment('jumlah_akses');
        $tautan->forceFill(['terakhir_diakses_at' => now()])->save();

        $tabDiminta = (string) $request->query('tab', '');
        $tabAktif = match ($tautan->jenis) {
            TautanAkses::JENIS_LAPORAN_KINERJA => 'kinerja',
            TautanAkses::JENIS_ARSIP => 'arsip',
            default => in_array($tabDiminta, ['kinerja', 'arsip'], true) ? $tabDiminta : 'kinerja',
        };

        $tahunInput = $request->filled('tahun_kinerja')
            ? (int) $request->input('tahun_kinerja')
            : ($request->filled('tahun') && $tabAktif === 'kinerja' ? (int) $request->input('tahun') : null);
        $tahunKinerja = $kinerjaService->tentukanTahunDefault($tahunInput);

        $kinerja = null;
        if ($tautan->bolehKinerja()) {
            $kinerja = $kinerjaService->ringkasanTahunan($tahunKinerja);

            if ($tabAktif === 'kinerja') {
                AksesDokumenLog::create([
                    'dokumen_arsip_id' => null,
                    'tautan_akses_id' => $tautan->id,
                    'user_id' => null,
                    'konteks' => 'kinerja',
                    'aksi' => 'lihat',
                    'tahun_filter' => $tahunKinerja,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'waktu' => now(),
                ]);
            }
        }

        $arsip = null;
        if ($tautan->bolehArsip()) {
            $q = trim((string) $request->query('q', ''));
            $kategoriId = $request->filled('kategori_id') ? (int) $request->query('kategori_id') : null;
            $tahunArsip = $request->filled('tahun_arsip') ? (int) $request->query('tahun_arsip') : null;

            $daftarDokumen = DokumenArsip::query()
                ->with(['kategori:id,kode,nama', 'bidang:id,nama', 'berkas:id,uuid,nama_asli,ukuran'])
                ->where('status', DokumenArsip::STATUS_DISAHKAN)
                ->when($q !== '', function ($query) use ($q): void {
                    $query->where(function ($sub) use ($q): void {
                        $sub->where('judul', 'ilike', "%{$q}%")
                            ->orWhere('nomor', 'ilike', "%{$q}%")
                            ->orWhere('ringkasan', 'ilike', "%{$q}%");
                    });
                })
                ->when($kategoriId !== null, fn ($query) => $query->where('kategori_dokumen_id', $kategoriId))
                ->when($tahunArsip !== null, fn ($query) => $query->where('tahun', $tahunArsip))
                ->orderByDesc('tahun')
                ->orderByDesc('tanggal_pengesahan')
                ->orderByDesc('id')
                ->get()
                ->map(fn (DokumenArsip $d) => [
                    'id' => $d->id,
                    'uuid' => $d->uuid,
                    'judul' => $d->judul,
                    'nomor' => $d->nomor,
                    'tahun' => $d->tahun,
                    'versi' => $d->versi,
                    'tanggal_pengesahan' => $d->tanggal_pengesahan?->format('d/m/Y'),
                    'ringkasan' => $d->ringkasan,
                    'kategori' => $d->kategori?->nama,
                    'kategori_id' => $d->kategori_dokumen_id,
                    'bidang' => $d->bidang?->nama,
                    'ukuran' => $d->berkas?->ukuran ?? 0,
                    'nama_berkas' => $d->berkas?->nama_asli ?? 'dokumen.pdf',
                    'url_lihat' => route('akses.arsip.berkas', ['token' => $token, 'uuid' => $d->uuid, 'aksi' => 'lihat']),
                    'url_cetak' => route('akses.arsip.berkas', ['token' => $token, 'uuid' => $d->uuid, 'aksi' => 'cetak']),
                    'url_unduh' => route('akses.arsip.berkas', ['token' => $token, 'uuid' => $d->uuid, 'aksi' => 'unduh']),
                ])
                ->all();

            $tahunTersedia = DokumenArsip::query()
                ->where('status', DokumenArsip::STATUS_DISAHKAN)
                ->distinct()
                ->orderByDesc('tahun')
                ->pluck('tahun')
                ->all();

            $arsip = [
                'filter' => [
                    'q' => $q,
                    'kategori_id' => $kategoriId,
                    'tahun_arsip' => $tahunArsip,
                ],
                'daftar_kategori' => KategoriDokumen::where('aktif', true)->orderBy('urutan')->get(['id', 'kode', 'nama']),
                'daftar_tahun' => $tahunTersedia,
                'dokumen' => $daftarDokumen,
            ];
        }

        return Inertia::render('publik/portal-pimpinan', [
            'token' => $token,
            'tautan' => [
                'label' => $tautan->label,
                'jenis' => $tautan->jenis,
                'jenis_label' => TautanAkses::labelJenis($tautan->jenis),
                'boleh_kinerja' => $tautan->bolehKinerja(),
                'boleh_arsip' => $tautan->bolehArsip(),
                'kadaluarsa_at' => $tautan->kadaluarsa_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
            ],
            'tab_aktif' => $tabAktif,
            'kinerja' => $kinerja,
            'arsip' => $arsip,
            'url_pdf_kinerja' => $tautan->bolehKinerja()
                ? route('akses.kinerja.pdf', ['token' => $token, 'tahun' => $tahunKinerja])
                : null,
        ])
            ->toResponse($request)
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function unduhPdfKinerja(
        Request $request,
        string $token,
        LaporanKinerjaZisService $kinerjaService,
        DokumenPdfService $pdfService,
    ): Response {
        $resolved = $this->selesaikanTautan($request, $token);
        if ($resolved instanceof Response) {
            return $resolved;
        }

        $tautan = $resolved;
        abort_unless($tautan->bolehKinerja(), 403, 'Tautan ini tidak memiliki izin melihat Laporan Kinerja ZIS.');

        $tahunInput = $request->filled('tahun') ? (int) $request->input('tahun') : null;
        $tahun = $kinerjaService->tentukanTahunDefault($tahunInput);
        $data = $kinerjaService->ringkasanTahunan($tahun);

        $tautan->forceFill(['terakhir_diakses_at' => now()])->save();

        AksesDokumenLog::create([
            'dokumen_arsip_id' => null,
            'tautan_akses_id' => $tautan->id,
            'user_id' => null,
            'konteks' => 'kinerja_pdf',
            'aksi' => 'unduh',
            'tahun_filter' => $tahun,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'waktu' => now(),
        ]);

        $namaBerkas = "Ringkasan-Kinerja-ZIS-{$tahun}.pdf";

        return $pdfService->stream('pdf.ringkasan-kinerja-zis', $namaBerkas, [
            'judul' => "RINGKASAN KINERJA PENGELOLAAN ZIS TAHUN {$tahun}",
            'tahun' => $tahun,
            'kinerja' => $data,
        ], [
            'kertas' => 'a4',
            'orientasi' => 'portrait',
            'draft' => false,
        ])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function berkasArsip(Request $request, string $token, string $uuid): Response
    {
        $resolved = $this->selesaikanTautan($request, $token);
        if ($resolved instanceof Response) {
            return $resolved;
        }

        $tautan = $resolved;
        abort_unless($tautan->bolehArsip(), 403, 'Tautan ini tidak memiliki izin membuka Arsip Dokumen PKP.');

        $dokumen = DokumenArsip::with('berkas')->where('uuid', $uuid)->firstOrFail();
        abort_unless($dokumen->status === DokumenArsip::STATUS_DISAHKAN, 404, 'Dokumen tidak tersedia untuk diakses.');

        $berkas = $dokumen->berkas;
        abort_unless($berkas !== null && Storage::disk($berkas->disk)->exists($berkas->path), 404, 'Berkas fisik tidak ditemukan.');

        $aksi = in_array($request->query('aksi'), ['lihat', 'cetak', 'unduh'], true)
            ? (string) $request->query('aksi')
            : 'lihat';

        $tautan->forceFill(['terakhir_diakses_at' => now()])->save();

        AksesDokumenLog::create([
            'dokumen_arsip_id' => $dokumen->id,
            'tautan_akses_id' => $tautan->id,
            'user_id' => null,
            'konteks' => 'arsip',
            'aksi' => $aksi,
            'tahun_filter' => $dokumen->tahun,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'waktu' => now(),
        ]);

        if ($aksi === 'unduh') {
            return Storage::disk($berkas->disk)->download($berkas->path, $berkas->nama_asli, [
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        return Storage::disk($berkas->disk)->response($berkas->path, $berkas->nama_asli, [
            'Content-Type' => $berkas->mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($berkas->nama_asli).'"',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function selesaikanTautan(Request $request, string $token): TautanAkses|Response
    {
        $tautan = TautanAkses::cariDariToken($token);

        if (! $tautan) {
            return Inertia::render('publik/tautan-tidak-aktif', [
                'alasan' => 'tidak_ditemukan',
                'label' => null,
            ])
                ->toResponse($request)
                ->setStatusCode(404)
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        if (! $tautan->masihAktif()) {
            return Inertia::render('publik/tautan-tidak-aktif', [
                'alasan' => $tautan->statusKode(),
                'label' => $tautan->label,
            ])
                ->toResponse($request)
                ->setStatusCode(410)
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return $tautan;
    }
}
