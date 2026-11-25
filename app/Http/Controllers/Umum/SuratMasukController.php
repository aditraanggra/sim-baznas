<?php

namespace App\Http\Controllers\Umum;

use App\Actions\Administrasi\CatatSuratMasuk;
use App\Actions\Administrasi\DisposisiSuratMasuk;
use App\Actions\Administrasi\SelesaikanDisposisiSurat;
use App\Actions\Administrasi\TeruskanSuratMasukKeKetua;
use App\Actions\Administrasi\TutupSuratMasuk;
use App\Http\Controllers\Controller;
use App\Models\DisposisiSurat;
use App\Models\SuratMasuk;
use App\Models\User;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\BerkasPrivatService;
use App\Services\Pdf\DokumenPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SuratMasukController extends Controller
{
    public function index(Request $request, AksesPersuratanService $akses): Response
    {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        $cari = trim($request->string('cari')->toString());
        $status = trim($request->string('status')->toString());
        $sifat = trim($request->string('sifat')->toString());
        $hanyaSaya = $request->boolean('disposisi_saya');

        $query = SuratMasuk::query()
            ->with(['pencatat:id,name', 'disposisi.kepadaUser:id,name'])
            ->when($cari !== '', fn ($q) => $q->where(function ($sub) use ($cari): void {
                $sub->where('nomor_agenda', 'ilike', "%{$cari}%")
                    ->orWhere('nomor_surat', 'ilike', "%{$cari}%")
                    ->orWhere('pengirim', 'ilike', "%{$cari}%")
                    ->orWhere('perihal', 'ilike', "%{$cari}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status_kode', $status))
            ->when($sifat !== '', fn ($q) => $q->where('sifat', $sifat))
            ->when($hanyaSaya, fn ($q) => $q->whereHas('disposisi', fn ($d) => $d
                ->where('kepada_user_id', $user->id)
                ->where('status', 'menunggu')));

        $akses->terapkanCakupanSuratMasuk($query, $user);

        $daftar = $query
            ->orderByDesc('tanggal_terima')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function (SuratMasuk $s) use ($user): array {
                $adaDisposisiSaya = $s->disposisi->contains(fn (DisposisiSurat $d) => (int) $d->kepada_user_id === (int) $user->id && $d->status === 'menunggu');

                return [
                    'id' => $s->id,
                    'nomor_agenda' => $s->nomor_agenda,
                    'nomor_surat' => $s->nomor_surat,
                    'tanggal_surat' => $s->tanggal_surat?->format('d/m/Y'),
                    'tanggal_terima' => $s->tanggal_terima?->format('d/m/Y'),
                    'pengirim' => $s->pengirim,
                    'perihal' => $s->perihal,
                    'sifat' => $s->sifat,
                    'status_kode' => $s->status_kode,
                    'status_label' => SuratMasuk::labelStatus($s->status_kode),
                    'jumlah_disposisi' => $s->disposisi->count(),
                    'menunggu_saya' => $adaDisposisiSaya,
                ];
            });

        return Inertia::render('umum/surat/masuk-index', [
            'surat_list' => $daftar,
            'filter' => [
                'cari' => $cari,
                'status' => $status,
                'sifat' => $sifat,
                'disposisi_saya' => $hanyaSaya,
            ],
            'bisa_catat' => $akses->bolehKelolaSurat($user),
        ]);
    }

    public function store(Request $request, CatatSuratMasuk $action): RedirectResponse
    {
        $surat = $action->jalankan($request->all());

        return to_route('umum.surat.masuk.show', $surat)
            ->with('sukses', "Surat masuk {$surat->nomor_agenda} berhasil dicatat.");
    }

    public function show(
        Request $request,
        SuratMasuk $suratMasuk,
        AksesPersuratanService $akses,
        BerkasPrivatService $berkasService,
    ): Response {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        if (! $akses->bolehLihatSuratMasuk($user, $suratMasuk)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat surat masuk ini.');
        }

        $suratMasuk->load([
            'pencatat:id,name',
            'berkasPrivat',
            'statusLog.user:id,name',
            'disposisi.dariUser:id,name',
            'disposisi.kepadaUser:id,name',
            'disposisi.berkasBukti',
            'suratBalasan:id,nomor,tanggal,perihal,status_kode',
        ]);

        $wakaList = User::query()
            ->where('status', 'aktif')
            ->role(['waka_1', 'waka_2', 'waka_3', 'waka_4'])
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'jabatan' => $u->getRoleNames()
                    ->filter(fn ($r) => str_starts_with($r, 'waka_'))
                    ->map(fn ($r) => config("sisfozis.peran.{$r}.nama", $r))
                    ->implode(', '),
            ])
            ->values();

        $internalList = User::query()
            ->where('status', 'aktif')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'upz'))
            ->with('roles:id,name')
            ->orderBy('name')
            ->get()
            ->filter(fn (User $u) => ! $u->adalahUpzEksternal() && (int) $u->id !== (int) $user->id)
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'peran' => $u->getRoleNames()
                    ->reject(fn ($r) => $r === 'amil')
                    ->map(fn ($r) => config("sisfozis.peran.{$r}.nama", $r))
                    ->implode(', ') ?: 'Amil',
            ])
            ->values();

        $disposisiAktifSaya = $suratMasuk->disposisi
            ->where('kepada_user_id', $user->id)
            ->where('status', 'menunggu')
            ->first();

        return Inertia::render('umum/surat/masuk-show', [
            'surat' => [
                'id' => $suratMasuk->id,
                'uuid' => $suratMasuk->uuid,
                'nomor_agenda' => $suratMasuk->nomor_agenda,
                'nomor_surat' => $suratMasuk->nomor_surat,
                'tanggal_surat' => $suratMasuk->tanggal_surat?->format('d/m/Y'),
                'tanggal_terima' => $suratMasuk->tanggal_terima?->format('d/m/Y'),
                'pengirim' => $suratMasuk->pengirim,
                'perihal' => $suratMasuk->perihal,
                'ringkasan' => $suratMasuk->ringkasan,
                'sifat' => $suratMasuk->sifat,
                'status_kode' => $suratMasuk->status_kode,
                'status_label' => SuratMasuk::labelStatus($suratMasuk->status_kode),
                'pencatat' => $suratMasuk->pencatat?->name ?? '-',
                'selesai_at' => $suratMasuk->selesai_at?->format('d/m/Y H:i'),
                'catatan_penutupan' => $suratMasuk->catatan_penutupan,
                'berkas_nama' => $suratMasuk->berkasPrivat?->nama_asli,
                'berkas_url' => $suratMasuk->berkasPrivat ? $berkasService->urlBertandaTangan($suratMasuk->berkasPrivat, 30, 'lihat') : null,
                'berkas_unduh_url' => $suratMasuk->berkasPrivat ? $berkasService->urlBertandaTangan($suratMasuk->berkasPrivat, 30, 'unduh') : null,
            ],
            'disposisi' => $suratMasuk->disposisi->map(fn (DisposisiSurat $d) => [
                'id' => $d->id,
                'parent_disposisi_id' => $d->parent_disposisi_id,
                'tahap' => $d->tahap,
                'urutan' => $d->urutan,
                'dari_user_id' => $d->dari_user_id,
                'dari_nama' => $d->dariUser?->name ?? '-',
                'kepada_user_id' => $d->kepada_user_id,
                'kepada_nama' => $d->kepadaUser?->name ?? '-',
                'kepada_peran' => $d->kepada_peran ? config("sisfozis.peran.{$d->kepada_peran}.nama", $d->kepada_peran) : null,
                'instruksi' => $d->instruksi,
                'tenggat' => $d->tenggat?->format('d/m/Y'),
                'keputusan' => $d->keputusan,
                'status' => $d->status,
                'catatan_tindak_lanjut' => $d->catatan_tindak_lanjut,
                'waktu' => $d->waktu?->format('d/m/Y H:i'),
                'selesai_at' => $d->selesai_at?->format('d/m/Y H:i'),
                'bukti_nama' => $d->berkasBukti?->nama_asli,
                'bukti_url' => $d->berkasBukti ? $berkasService->urlBertandaTangan($d->berkasBukti, 30, 'unduh') : null,
            ])->values(),
            'status_log' => $suratMasuk->statusLog->map(fn ($l) => [
                'id' => $l->id,
                'status_kode' => $l->status_kode,
                'status_label' => SuratMasuk::labelStatus($l->status_kode),
                'pelaku' => $l->user?->name ?? 'Sistem',
                'waktu' => $l->waktu?->format('d/m/Y H:i'),
                'catatan' => $l->catatan,
            ])->values(),
            'surat_balasan' => $suratMasuk->suratBalasan->map(fn ($b) => [
                'id' => $b->id,
                'nomor' => $b->nomor ?? '(Draft)',
                'tanggal' => $b->tanggal?->format('d/m/Y'),
                'perihal' => $b->perihal,
                'status_kode' => $b->status_kode,
            ])->values(),
            'waka_list' => $wakaList,
            'internal_list' => $internalList,
            'disposisi_aktif_saya' => $disposisiAktifSaya ? [
                'id' => $disposisiAktifSaya->id,
                'tahap' => $disposisiAktifSaya->tahap,
                'instruksi' => $disposisiAktifSaya->instruksi,
            ] : null,
            'akses' => [
                'boleh_kelola' => $akses->bolehKelolaSurat($user),
                'boleh_disposisi_ketua' => $akses->bolehDisposisiDariKetua($user),
                'boleh_tutup' => $akses->bolehTutupSuratMasuk($user),
                'adalah_waka' => $user->hasAnyRole(['waka_1', 'waka_2', 'waka_3', 'waka_4', 'admin']),
            ],
        ]);
    }

    public function teruskanKetua(Request $request, SuratMasuk $suratMasuk, TeruskanSuratMasukKeKetua $action): RedirectResponse
    {
        $action->jalankan([
            ...$request->all(),
            'surat_masuk_id' => $suratMasuk->id,
        ]);

        return back()->with('sukses', 'Surat masuk berhasil diteruskan ke Ketua.');
    }

    public function disposisi(Request $request, SuratMasuk $suratMasuk, DisposisiSuratMasuk $action): RedirectResponse
    {
        $action->jalankan([
            ...$request->all(),
            'surat_masuk_id' => $suratMasuk->id,
        ]);

        return back()->with('sukses', 'Disposisi surat berhasil dikirim.');
    }

    public function selesaikanDisposisi(
        Request $request,
        SuratMasuk $suratMasuk,
        DisposisiSurat $disposisiSurat,
        SelesaikanDisposisiSurat $action,
    ): RedirectResponse {
        if ((int) $disposisiSurat->surat_masuk_id !== (int) $suratMasuk->id) {
            abort(404);
        }

        $action->jalankan([
            ...$request->all(),
            'disposisi_id' => $disposisiSurat->id,
        ]);

        return back()->with('sukses', 'Tindak lanjut disposisi berhasil diselesaikan.');
    }

    public function tutup(Request $request, SuratMasuk $suratMasuk, TutupSuratMasuk $action): RedirectResponse
    {
        $action->jalankan([
            ...$request->all(),
            'surat_masuk_id' => $suratMasuk->id,
        ]);

        return back()->with('sukses', 'Surat masuk berhasil ditandai selesai.');
    }

    public function lembarDisposisiPdf(
        Request $request,
        SuratMasuk $suratMasuk,
        AksesPersuratanService $akses,
        DokumenPdfService $pdfService,
    ): HttpResponse {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        if (! $akses->bolehLihatSuratMasuk($user, $suratMasuk)) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak lembar disposisi surat ini.');
        }

        $suratMasuk->load([
            'pencatat:id,name',
            'disposisi.dariUser:id,name',
            'disposisi.kepadaUser:id,name',
        ]);

        $namaBerkas = str_replace(['/', ' '], '-', $suratMasuk->nomor_agenda).'-lembar-disposisi.pdf';

        return $pdfService->stream('pdf.lembar-disposisi-surat', $namaBerkas, [
            'judul' => 'LEMBAR DISPOSISI SURAT MASUK',
            'surat' => $suratMasuk,
            'disposisi' => $suratMasuk->disposisi,
        ], [
            'kertas' => 'a4',
            'orientasi' => 'portrait',
            'tipografi' => 'sans',
        ]);
    }
}
