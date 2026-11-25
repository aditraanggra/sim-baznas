<?php

namespace App\Http\Controllers\Distribusi;

use App\Actions\Distribusi\BuatPermohonan;
use App\Actions\Distribusi\ProsesLangkahPermohonan;
use App\Actions\Distribusi\TolakPermohonan;
use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use App\Models\Desa;
use App\Models\JenisDana;
use App\Models\JenisDokumen;
use App\Models\MetodeBayar;
use App\Models\Mustahik;
use App\Models\Penyaluran;
use App\Models\Permohonan;
use App\Models\Program;
use App\Models\RekeningBank;
use App\Models\SumberDana;
use App\Models\User;
use App\Services\BerkasPrivatService;
use App\Services\IdentitasPribadiService;
use App\Services\Pdf\DokumenPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PermohonanController extends Controller
{
    public function index(Request $request): Response
    {
        $cari = trim($request->string('cari')->toString());
        $status = $request->string('status')->toString();

        $daftar = Permohonan::query()
            ->with(['mustahik:id,kode,nama,status_verifikasi', 'program:id,nama,sub_bidang', 'jenisDana:id,nama', 'asnaf:id,nama'])
            ->when($status !== '', fn ($q) => $q->where('status_kode', $status))
            ->when($cari !== '', fn ($q) => $q->where(function ($sub) use ($cari): void {
                $sub->where('nomor', 'ilike', "%{$cari}%")
                    ->orWhere('kode_tracking', 'ilike', "%{$cari}%")
                    ->orWhere('pemohon_nama', 'ilike', "%{$cari}%")
                    ->orWhereHas('mustahik', fn ($m) => $m->where('nama', 'ilike', "%{$cari}%"));
            }))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('distribusi/permohonan/index', [
            'permohonan' => $daftar->through(fn (Permohonan $p) => [
                'id' => $p->id,
                'nomor' => $p->nomor,
                'kode_tracking' => $p->kode_tracking,
                'tanggal' => $p->tanggal?->format('d/m/Y'),
                'mustahik' => $p->mustahik?->nama,
                'mustahik_kode' => $p->mustahik?->kode,
                'pemohon_sama_mustahik' => (bool) $p->pemohon_sama_mustahik,
                'pemohon_nama' => $p->pemohon_nama,
                'pemohon_telepon' => $p->pemohon_telepon,
                'pemohon_hubungan' => $p->pemohon_hubungan,
                'program' => $p->program?->nama,
                'sub_bidang' => $p->sub_bidang,
                'jenis_dana' => $p->jenisDana?->nama,
                'asnaf' => $p->asnaf?->nama,
                'nominal_diajukan' => $p->nominal_diajukan,
                'nominal_disetujui' => $p->nominal_disetujui,
                'status_kode' => $p->status_kode,
                'langkah_sekarang' => $p->langkah_sekarang,
                'kesiapan_keuangan' => $p->kesiapan_keuangan,
            ]),
            'filter' => ['cari' => $cari, 'status' => $status],
            'mustahik_list' => Mustahik::where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama', 'nik_masked', 'telepon']),
            'desa_list' => Desa::with('kecamatan:id,nama')->where('aktif', true)->orderBy('nama')->get()
                ->map(fn (Desa $d) => ['id' => $d->id, 'nama' => $d->nama, 'kecamatan' => $d->kecamatan?->nama]),
            'program_list' => Program::where('aktif', true)->orderBy('kode')->get(['id', 'kode', 'nama', 'sub_bidang']),
            'jenis_dana_list' => JenisDana::where('aktif', true)->orderBy('id')->get(['id', 'kode', 'nama', 'wajib_asnaf']),
            'asnaf_list' => Asnaf::where('aktif', true)->orderBy('urutan')->get(['id', 'kode', 'nama']),
            'bisa_kelola' => $request->user()->bolehAksiBidang('distribusi', 'create'),
        ]);
    }

    public function store(Request $request, BuatPermohonan $action): RedirectResponse
    {
        $permohonan = $action->jalankan($request->all());

        return to_route('distribusi.permohonan.show', $permohonan)
            ->with('sukses', "Permohonan {$permohonan->nomor} berhasil dibuat (Kode Lacak: {$permohonan->kode_tracking}).");
    }

    public function show(Request $request, Permohonan $permohonan, BerkasPrivatService $berkasService): Response
    {
        $permohonan->load([
            'mustahik.kecamatan:id,nama',
            'mustahik.desa:id,nama',
            'program:id,kode,nama,sub_bidang',
            'jenisDana:id,kode,nama,wajib_asnaf',
            'asnaf:id,kode,nama',
            'petugasLapangan:id,name',
            'keuanganDiprosesOleh:id,name',
            'disposisi.dariUser:id,name',
            'disposisi.keUser:id,name',
            'statusLog.user:id,name',
            'dokumen.jenisDokumen:id,nama',
            'dokumen.berkasPrivat',
        ]);

        $stafList = User::query()
            ->where('status', 'aktif')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['staf_pendistribusian', 'staf_pendayagunaan', 'kabag_pendis_pendag']))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('distribusi/permohonan/show', [
            'permohonan' => [
                'id' => $permohonan->id,
                'nomor' => $permohonan->nomor,
                'kode_tracking' => $permohonan->kode_tracking,
                'tanggal' => $permohonan->tanggal?->format('d/m/Y'),
                'mustahik_id' => $permohonan->mustahik_id,
                'mustahik' => $permohonan->mustahik?->nama,
                'mustahik_kode' => $permohonan->mustahik?->kode,
                'mustahik_telepon' => $permohonan->mustahik?->telepon,
                'mustahik_wilayah' => trim(($permohonan->mustahik?->desa?->nama ? "Desa {$permohonan->mustahik->desa->nama}, " : '').($permohonan->mustahik?->kecamatan?->nama ?? ''), ', '),
                'pemohon_sama_mustahik' => (bool) $permohonan->pemohon_sama_mustahik,
                'pemohon_nama' => $permohonan->pemohon_nama,
                'pemohon_telepon' => $permohonan->pemohon_telepon,
                'pemohon_hubungan' => $permohonan->pemohon_hubungan,
                'pemohon_alamat' => $permohonan->pemohon_alamat,
                'program' => $permohonan->program?->nama,
                'sub_bidang' => $permohonan->sub_bidang,
                'jenis_dana' => $permohonan->jenisDana?->nama,
                'asnaf' => $permohonan->asnaf?->nama,
                'nominal_diajukan' => $permohonan->nominal_diajukan,
                'nominal_disetujui' => $permohonan->nominal_disetujui,
                'status_kode' => $permohonan->status_kode,
                'status_publik' => $permohonan->status_publik,
                'langkah_sekarang' => $permohonan->langkah_sekarang,
                'petugas_lapangan_id' => $permohonan->petugas_lapangan_id,
                'petugas_lapangan' => $permohonan->petugasLapangan?->name,
                'kesiapan_keuangan' => $permohonan->kesiapan_keuangan,
                'keuangan_diproses_oleh' => $permohonan->keuanganDiprosesOleh?->name,
                'keuangan_siap_at' => $permohonan->keuangan_siap_at?->format('d/m/Y H:i'),
                'catatan_asesmen' => $permohonan->catatan_asesmen,
                'uraian' => $permohonan->uraian,
                'alasan_tolak' => $permohonan->alasan_tolak,
            ],
            'disposisi' => $permohonan->disposisi->map(fn ($d) => [
                'id' => $d->id,
                'nomor' => $d->nomor,
                'langkah' => $d->langkah,
                'status_dari' => $d->status_dari,
                'status_ke' => $d->status_ke,
                'dari' => $d->dariUser?->name ?? '-',
                'ke' => $d->keUser?->name ?? $d->ke_peran ?? '-',
                'status' => $d->status,
                'nominal_usulan' => $d->nominal_usulan,
                'instruksi' => $d->instruksi,
                'catatan' => $d->catatan,
                'waktu' => ($d->waktu_tindak_lanjut ?? $d->created_at)?->format('d/m/Y H:i'),
            ]),
            'status_log' => $permohonan->statusLog->map(fn ($l) => [
                'id' => $l->id,
                'status_kode' => $l->status_kode,
                'status_publik' => $l->status_publik,
                'judul_publik' => $l->judul_publik,
                'keterangan_internal' => $l->keterangan_internal,
                'petugas' => $l->user?->name ?? '-',
                'waktu' => $l->waktu?->format('d/m/Y H:i'),
            ]),
            'dokumen' => $permohonan->dokumen->map(fn ($dok) => [
                'id' => $dok->id,
                'jenis' => $dok->jenisDokumen?->nama,
                'nama_file' => $dok->berkasPrivat?->nama_asli,
                'url_unduh' => $dok->berkasPrivat ? $berkasService->urlBertandaTangan($dok->berkasPrivat) : null,
            ]),
            'staf_list' => $stafList,
            'penyaluran' => Penyaluran::query()
                ->where('permohonan_id', $permohonan->id)
                ->whereIn('status', ['draft', 'disetujui', 'dicairkan'])
                ->latest('id')
                ->first(['id', 'nomor', 'status']),
            'sumber_dana_list' => SumberDana::query()
                ->where('aktif', true)
                ->when($permohonan->jenis_dana_id, fn ($q) => $q->where('jenis_dana_id', $permohonan->jenis_dana_id))
                ->orderBy('id')
                ->get(['id', 'kode', 'nama']),
            'metode_bayar_list' => MetodeBayar::where('aktif', true)->orderBy('id')->get(['id', 'kode', 'nama', 'butuh_rekening']),
            'rekening_list' => RekeningBank::where('aktif', true)->orderBy('bank')->get(['id', 'bank', 'no_rekening', 'atas_nama']),
            'jenis_dokumen_list' => JenisDokumen::where('aktif', true)->orderBy('id')->get(['id', 'nama']),
            'peran_user' => $request->user()->getRoleNames()->all(),
        ]);
    }

    public function prosesLangkah(Request $request, Permohonan $permohonan, ProsesLangkahPermohonan $action): RedirectResponse
    {
        $action->jalankan([
            ...$request->all(),
            'permohonan_id' => $permohonan->id,
        ]);

        return back()->with('sukses', 'Langkah disposisi permohonan berhasil diproses.');
    }

    public function siapkanKeuangan(Request $request, Permohonan $permohonan, ProsesLangkahPermohonan $action): RedirectResponse
    {
        $action->jalankan([
            'permohonan_id' => $permohonan->id,
            'aksi' => 'siapkan_keuangan',
            'catatan' => $request->input('catatan'),
        ]);

        return back()->with('sukses', 'Kesiapan dana oleh Bagian Keuangan telah dicatat.');
    }

    public function tolak(Request $request, Permohonan $permohonan, TolakPermohonan $action): RedirectResponse
    {
        $action->jalankan([
            'permohonan_id' => $permohonan->id,
            'alasan_tolak' => $request->input('alasan_tolak'),
        ]);

        return back()->with('sukses', 'Permohonan telah ditolak dan dicatat ke log.');
    }

    public function lembarDisposisiPdf(Permohonan $permohonan, DokumenPdfService $pdfService): HttpResponse
    {
        $permohonan->load([
            'mustahik.desa',
            'mustahik.kecamatan',
            'program:id,nama',
            'jenisDana:id,nama',
            'asnaf:id,kode,nama',
            'disposisi.dariUser:id,name',
            'disposisi.keUser:id,name',
        ]);

        $namaBerkas = str_replace('/', '-', $permohonan->nomor).'-disposisi.pdf';
        $urlTracking = route('publik.lacak-permohonan', ['kode_tracking' => $permohonan->kode_tracking]);

        return $pdfService->stream('pdf.lembar-disposisi', $namaBerkas, [
            'judul' => 'LEMBAR DISPOSISI PERMOHONAN BANTUAN',
            'permohonan' => $permohonan,
            'disposisi' => $permohonan->disposisi,
            'qr_payload' => $urlTracking,
            'qr_label' => 'Pindai QR untuk melacak status permohonan bantuan secara publik',
        ], [
            'kertas' => 'a4',
            'orientasi' => 'portrait',
            'tipografi' => 'sans',
            'qr_payload' => $urlTracking,
            'qr_label' => 'Pindai QR untuk melacak status permohonan bantuan secara publik',
        ]);
    }

    public function tandaTerimaPdf(
        Request $request,
        Permohonan $permohonan,
        DokumenPdfService $pdfService,
        IdentitasPribadiService $identitas,
    ): HttpResponse {
        $permohonan->load([
            'mustahik.desa',
            'mustahik.kecamatan',
            'program:id,nama',
            'jenisDana:id,nama',
            'asnaf:id,kode,nama',
            'pembuat:id,name',
        ]);

        $namaBerkas = str_replace('/', '-', $permohonan->nomor).'-tanda-terima.pdf';
        $urlTracking = route('publik.lacak-permohonan', ['kode_tracking' => $permohonan->kode_tracking]);
        $teleponAcuan = $permohonan->teleponPemohonEfektif() ?? $permohonan->mustahik?->telepon;
        $empatDigit = $teleponAcuan ? $identitas->empatDigitAkhirTelepon($teleponAcuan) : null;
        $teleponHint = $empatDigit ? "****{$empatDigit}" : '4 digit akhir telepon terdaftar';

        $opsi = [
            'kertas' => 'a5',
            'orientasi' => 'portrait',
            'tipografi' => 'sans',
            'qr_payload' => $urlTracking,
            'qr_label' => 'Pindai QR untuk melacak progres permohonan bantuan secara publik',
        ];

        $data = [
            'judul' => 'TANDA TERIMA PERMOHONAN BANTUAN',
            'permohonan' => $permohonan,
            'url_tracking' => $urlTracking,
            'telepon_hint' => $teleponHint,
            'qr_payload' => $urlTracking,
            'qr_label' => $opsi['qr_label'],
        ];

        if ($request->boolean('unduh')) {
            return $pdfService->unduh('pdf.tanda-terima-permohonan', $namaBerkas, $data, $opsi);
        }

        return $pdfService->stream('pdf.tanda-terima-permohonan', $namaBerkas, $data, $opsi);
    }
}
