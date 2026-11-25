<?php

namespace App\Http\Controllers\Umum;

use App\Actions\Administrasi\AjukanSuratKeluar;
use App\Actions\Administrasi\BatalkanSuratKeluar;
use App\Actions\Administrasi\CetakSuratKeluar;
use App\Actions\Administrasi\SetujuiSuratKeluar;
use App\Actions\Administrasi\SimpanSuratKeluar;
use App\Actions\Administrasi\TolakRevisiSuratKeluar;
use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\TemplateSurat;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\Administrasi\TemplateSuratService;
use App\Services\BerkasPrivatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class SuratKeluarController extends Controller
{
    public function index(Request $request, AksesPersuratanService $akses): Response
    {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        $cari = trim($request->string('cari')->toString());
        $status = trim($request->string('status')->toString());
        $jenisId = $request->integer('jenis_surat_id');
        $sifat = trim($request->string('sifat')->toString());

        $query = SuratKeluar::query()
            ->with(['jenisSurat:id,kode,nama', 'pembuat:id,name', 'penyetuju:id,name'])
            ->when($cari !== '', fn ($q) => $q->where(function ($sub) use ($cari): void {
                $sub->where('nomor', 'ilike', "%{$cari}%")
                    ->orWhere('tujuan', 'ilike', "%{$cari}%")
                    ->orWhere('perihal', 'ilike', "%{$cari}%");
            }))
            ->when($status !== '', fn ($q) => $q->where('status_kode', $status))
            ->when($jenisId > 0, fn ($q) => $q->where('jenis_surat_id', $jenisId))
            ->when($sifat !== '', fn ($q) => $q->where('sifat', $sifat));

        $akses->terapkanCakupanSuratKeluar($query, $user);

        $daftar = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SuratKeluar $s) => [
                'id' => $s->id,
                'nomor' => $s->nomor,
                'jenis' => $s->jenisSurat?->nama ?? '-',
                'jenis_kode' => $s->jenisSurat?->kode ?? '-',
                'tanggal' => $s->tanggal?->format('d/m/Y'),
                'tujuan' => $s->tujuan,
                'perihal' => $s->perihal,
                'sifat' => $s->sifat,
                'status_kode' => $s->status_kode,
                'status_label' => SuratKeluar::labelStatus($s->status_kode),
                'pembuat' => $s->pembuat?->name ?? '-',
                'penyetuju' => $s->penyetuju?->name,
            ]);

        return Inertia::render('umum/surat/keluar-index', [
            'surat_list' => $daftar,
            'jenis_list' => JenisSurat::where('aktif', true)->orderBy('kode')->get(['id', 'kode', 'nama']),
            'filter' => [
                'cari' => $cari,
                'status' => $status,
                'jenis_surat_id' => $jenisId ?: null,
                'sifat' => $sifat,
            ],
            'bisa_buat' => $akses->bolehKelolaSurat($user),
        ]);
    }

    public function create(Request $request, AksesPersuratanService $akses, TemplateSuratService $templateService): Response
    {
        $user = $request->user();
        if (! $akses->bolehKelolaSurat($user)) {
            abort(403, 'Anda tidak memiliki wewenang membuat surat keluar.');
        }

        return Inertia::render('umum/surat/keluar-form', [
            'surat' => null,
            'surat_masuk_awal_id' => $request->integer('surat_masuk_id') ?: null,
            ...$this->opsiForm($templateService),
        ]);
    }

    public function store(Request $request, SimpanSuratKeluar $simpan, AjukanSuratKeluar $ajukan): RedirectResponse
    {
        $surat = $simpan->jalankan($request->all());

        if ($request->boolean('ajukan_persetujuan')) {
            $ajukan->jalankan(['surat_keluar_id' => $surat->id]);

            return to_route('umum.surat.keluar.show', $surat)
                ->with('sukses', 'Surat keluar berhasil disimpan dan diajukan untuk persetujuan.');
        }

        return to_route('umum.surat.keluar.show', $surat)
            ->with('sukses', 'Draft surat keluar berhasil disimpan.');
    }

    public function edit(
        Request $request,
        SuratKeluar $suratKeluar,
        AksesPersuratanService $akses,
        TemplateSuratService $templateService,
    ): Response {
        $user = $request->user();
        if (! $akses->bolehKelolaSurat($user)) {
            abort(403, 'Anda tidak memiliki wewenang menyunting surat keluar.');
        }

        if ($suratKeluar->status_kode !== 'draft') {
            abort(403, 'Hanya surat berstatus Draft yang dapat disunting.');
        }

        return Inertia::render('umum/surat/keluar-form', [
            'surat' => [
                'id' => $suratKeluar->id,
                'jenis_surat_id' => $suratKeluar->jenis_surat_id,
                'template_surat_id' => $suratKeluar->template_surat_id,
                'surat_masuk_id' => $suratKeluar->surat_masuk_id,
                'tanggal' => $suratKeluar->tanggal?->format('Y-m-d'),
                'sifat' => $suratKeluar->sifat,
                'lampiran' => $suratKeluar->lampiran,
                'tujuan' => $suratKeluar->tujuan,
                'alamat_tujuan' => $suratKeluar->alamat_tujuan,
                'perihal' => $suratKeluar->perihal,
                'isi' => $suratKeluar->isi,
                'data_placeholder' => $suratKeluar->data_placeholder ?? [],
                'tembusan' => $suratKeluar->tembusan,
                'nama_penandatangan' => $suratKeluar->nama_penandatangan,
                'jabatan_penandatangan' => $suratKeluar->jabatan_penandatangan,
                'catatan_revisi' => $suratKeluar->catatan_revisi,
            ],
            'surat_masuk_awal_id' => $suratKeluar->surat_masuk_id,
            ...$this->opsiForm($templateService),
        ]);
    }

    public function update(
        Request $request,
        SuratKeluar $suratKeluar,
        SimpanSuratKeluar $simpan,
        AjukanSuratKeluar $ajukan,
    ): RedirectResponse {
        $surat = $simpan->jalankan([...$request->all(), 'id' => $suratKeluar->id]);

        if ($request->boolean('ajukan_persetujuan')) {
            $ajukan->jalankan(['surat_keluar_id' => $surat->id]);

            return to_route('umum.surat.keluar.show', $surat)
                ->with('sukses', 'Perubahan surat keluar disimpan dan diajukan untuk persetujuan.');
        }

        return to_route('umum.surat.keluar.show', $surat)
            ->with('sukses', 'Perubahan draft surat keluar berhasil disimpan.');
    }

    public function show(
        Request $request,
        SuratKeluar $suratKeluar,
        AksesPersuratanService $akses,
        BerkasPrivatService $berkasService,
    ): Response {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        if (! $akses->bolehLihatSuratKeluar($user, $suratKeluar)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat surat keluar ini.');
        }

        $suratKeluar->load([
            'jenisSurat:id,kode,nama',
            'templateSurat:id,nama',
            'suratMasuk:id,nomor_agenda,pengirim,perihal',
            'berkasLampiran',
            'pembuat:id,name',
            'penyetuju:id,name',
            'pembatal:id,name',
            'cetakLogs.user:id,name',
        ]);

        $adalahPembuat = (int) ($suratKeluar->dibuat_oleh ?? $suratKeluar->created_by) === (int) $user->id;
        $bolehSetujui = $akses->bolehSetujuiSuratKeluar($user) && ! $adalahPembuat;

        return Inertia::render('umum/surat/keluar-show', [
            'surat' => [
                'id' => $suratKeluar->id,
                'uuid' => $suratKeluar->uuid,
                'nomor' => $suratKeluar->nomor,
                'jenis' => $suratKeluar->jenisSurat?->nama ?? '-',
                'jenis_kode' => $suratKeluar->jenisSurat?->kode ?? '-',
                'template_nama' => $suratKeluar->templateSurat?->nama,
                'surat_masuk' => $suratKeluar->suratMasuk ? [
                    'id' => $suratKeluar->suratMasuk->id,
                    'nomor_agenda' => $suratKeluar->suratMasuk->nomor_agenda,
                    'pengirim' => $suratKeluar->suratMasuk->pengirim,
                    'perihal' => $suratKeluar->suratMasuk->perihal,
                ] : null,
                'tanggal' => $suratKeluar->tanggal?->format('d/m/Y'),
                'sifat' => $suratKeluar->sifat,
                'lampiran' => $suratKeluar->lampiran,
                'tujuan' => $suratKeluar->tujuan,
                'alamat_tujuan' => $suratKeluar->alamat_tujuan,
                'perihal' => $suratKeluar->perihal,
                'isi' => $suratKeluar->isi,
                'tembusan' => $suratKeluar->tembusan,
                'nama_penandatangan' => $suratKeluar->nama_penandatangan,
                'jabatan_penandatangan' => $suratKeluar->jabatan_penandatangan,
                'status_kode' => $suratKeluar->status_kode,
                'status_label' => SuratKeluar::labelStatus($suratKeluar->status_kode),
                'pembuat' => $suratKeluar->pembuat?->name ?? '-',
                'diajukan_at' => $suratKeluar->diajukan_at?->format('d/m/Y H:i'),
                'penyetuju' => $suratKeluar->penyetuju?->name,
                'disetujui_at' => $suratKeluar->disetujui_at?->format('d/m/Y H:i'),
                'catatan_revisi' => $suratKeluar->catatan_revisi,
                'pembatal' => $suratKeluar->pembatal?->name,
                'dibatalkan_at' => $suratKeluar->dibatalkan_at?->format('d/m/Y H:i'),
                'alasan_batal' => $suratKeluar->alasan_batal,
                'lampiran_nama' => $suratKeluar->berkasLampiran?->nama_asli,
                'lampiran_url' => $suratKeluar->berkasLampiran
                    ? $berkasService->urlBertandaTangan($suratKeluar->berkasLampiran, 30, 'unduh')
                    : null,
                'url_verifikasi_publik' => $suratKeluar->status_kode === 'disetujui' || $suratKeluar->status_kode === 'dibatalkan'
                    ? route('publik.verifikasi-surat', $suratKeluar->uuid)
                    : null,
            ],
            'cetak_logs' => $suratKeluar->cetakLogs->map(fn ($c, $idx) => [
                'id' => $c->id,
                'cetak_ke' => $idx + 1,
                'pencetak' => $c->user?->name ?? '-',
                'waktu' => $c->waktu?->format('d/m/Y H:i'),
            ])->values(),
            'akses' => [
                'boleh_kelola' => $akses->bolehKelolaSurat($user),
                'boleh_setujui' => $bolehSetujui,
                'terblokir_maker_checker' => $akses->bolehSetujuiSuratKeluar($user) && $adalahPembuat,
                'boleh_batalkan' => $suratKeluar->status_kode !== 'dibatalkan'
                    && ($suratKeluar->status_kode === 'disetujui'
                        ? ($akses->bolehSetujuiSuratKeluar($user) && (! $adalahPembuat || $user->hasRole('admin')))
                        : ($akses->bolehKelolaSurat($user) || $akses->bolehSetujuiSuratKeluar($user))),
            ],
        ]);
    }

    public function ajukan(Request $request, SuratKeluar $suratKeluar, AjukanSuratKeluar $action): RedirectResponse
    {
        $action->jalankan(['surat_keluar_id' => $suratKeluar->id]);

        return back()->with('sukses', 'Surat keluar berhasil diajukan untuk persetujuan.');
    }

    public function setujui(Request $request, SuratKeluar $suratKeluar, SetujuiSuratKeluar $action): RedirectResponse
    {
        $surat = $action->jalankan(['surat_keluar_id' => $suratKeluar->id]);

        return back()->with('sukses', "Surat keluar disetujui dengan nomor {$surat->nomor}.");
    }

    public function revisi(Request $request, SuratKeluar $suratKeluar, TolakRevisiSuratKeluar $action): RedirectResponse
    {
        $action->jalankan([
            ...$request->all(),
            'surat_keluar_id' => $suratKeluar->id,
        ]);

        return back()->with('sukses', 'Surat keluar dikembalikan ke status Draft untuk diperbaiki.');
    }

    public function batalkan(Request $request, SuratKeluar $suratKeluar, BatalkanSuratKeluar $action): RedirectResponse
    {
        $action->jalankan([
            ...$request->all(),
            'surat_keluar_id' => $suratKeluar->id,
        ]);

        return back()->with('sukses', 'Surat keluar berhasil dibatalkan.');
    }

    public function pdf(
        Request $request,
        SuratKeluar $suratKeluar,
        AksesPersuratanService $akses,
        CetakSuratKeluar $action,
    ): HttpResponse {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        if (! $akses->bolehLihatSuratKeluar($user, $suratKeluar)) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak surat keluar ini.');
        }

        $hasil = $action->jalankan([
            'surat_keluar_id' => $suratKeluar->id,
            'pratinjau' => $request->boolean('pratinjau'),
        ]);

        $namaBerkas = str_replace(['/', ' '], '-', $hasil['surat']->nomor ?? 'draft-surat-keluar').'.pdf';

        if ($request->boolean('unduh')) {
            return $hasil['pdf']->download($namaBerkas);
        }

        return $hasil['pdf']->stream($namaBerkas);
    }

    /**
     * @return array<string, mixed>
     */
    private function opsiForm(TemplateSuratService $templateService): array
    {
        $jenisList = JenisSurat::where('aktif', true)->orderBy('kode')->get(['id', 'kode', 'nama']);

        $templateList = TemplateSurat::where('aktif', true)
            ->orderBy('nama')
            ->get()
            ->map(fn (TemplateSurat $t) => [
                'id' => $t->id,
                'jenis_surat_id' => $t->jenis_surat_id,
                'nama' => $t->nama,
                'isi' => $t->isi,
                'penutup_default' => $t->penutup_default,
                'placeholder_kustom' => $templateService->placeholderKustom($t->isi),
            ]);

        $suratMasukList = SuratMasuk::query()
            ->orderByDesc('tanggal_terima')
            ->limit(100)
            ->get(['id', 'nomor_agenda', 'pengirim', 'perihal'])
            ->map(fn (SuratMasuk $m) => [
                'id' => $m->id,
                'label' => "{$m->nomor_agenda} — {$m->pengirim} ({$m->perihal})",
            ]);

        return [
            'jenis_list' => $jenisList,
            'template_list' => $templateList,
            'surat_masuk_list' => $suratMasukList,
        ];
    }
}
