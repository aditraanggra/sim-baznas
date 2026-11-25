<?php

namespace App\Http\Controllers\Umum;

use App\Actions\Administrasi\SimpanJenisSurat;
use App\Actions\Administrasi\SimpanTemplateSurat;
use App\Actions\Administrasi\ToggleAktifJenisSurat;
use App\Actions\Administrasi\ToggleAktifTemplateSurat;
use App\Http\Controllers\Controller;
use App\Models\JenisSurat;
use App\Models\TemplateSurat;
use App\Services\Administrasi\AksesPersuratanService;
use App\Services\Administrasi\TemplateSuratService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateSuratController extends Controller
{
    public function index(Request $request, AksesPersuratanService $akses, TemplateSuratService $templateService): Response
    {
        $user = $request->user();
        $akses->pastikanBukanUpz($user);

        if (! $akses->bolehLihatSemuaNonRahasia($user)) {
            abort(403, 'Anda tidak memiliki akses ke pengaturan jenis dan template surat.');
        }

        $jenisList = JenisSurat::query()
            ->withCount('templates')
            ->orderBy('kode')
            ->get()
            ->map(fn (JenisSurat $j) => [
                'id' => $j->id,
                'kode' => $j->kode,
                'nama' => $j->nama,
                'format_nomor' => $j->format_nomor,
                'pisah_urutan' => $j->pisah_urutan,
                'keterangan' => $j->keterangan,
                'aktif' => $j->aktif,
                'templates_count' => $j->templates_count,
            ]);

        $templateList = TemplateSurat::query()
            ->with('jenisSurat:id,kode,nama')
            ->orderBy('jenis_surat_id')
            ->orderBy('nama')
            ->get()
            ->map(fn (TemplateSurat $t) => [
                'id' => $t->id,
                'jenis_surat_id' => $t->jenis_surat_id,
                'jenis_kode' => $t->jenisSurat?->kode ?? '-',
                'jenis_nama' => $t->jenisSurat?->nama ?? '-',
                'nama' => $t->nama,
                'isi' => $t->isi,
                'penutup_default' => $t->penutup_default,
                'placeholder' => $templateService->ekstrakPlaceholder($t->isi),
                'aktif' => $t->aktif,
            ]);

        return Inertia::render('umum/surat/template', [
            'jenis_list' => $jenisList,
            'template_list' => $templateList,
            'format_default' => config('penomoran.format.surat_keluar'),
            'bisa_kelola' => $akses->bolehKelolaSurat($user),
        ]);
    }

    public function simpanJenis(Request $request, SimpanJenisSurat $action): RedirectResponse
    {
        $jenis = $action->jalankan($request->all());

        return back()->with('sukses', "Jenis surat {$jenis->kode} - {$jenis->nama} berhasil disimpan.");
    }

    public function toggleJenis(Request $request, ToggleAktifJenisSurat $action): RedirectResponse
    {
        $jenis = $action->jalankan($request->all());

        return back()->with('sukses', "Status jenis surat {$jenis->nama} diperbarui.");
    }

    public function simpanTemplate(Request $request, SimpanTemplateSurat $action): RedirectResponse
    {
        $template = $action->jalankan($request->all());

        return back()->with('sukses', "Template surat \"{$template->nama}\" berhasil disimpan.");
    }

    public function toggleTemplate(Request $request, ToggleAktifTemplateSurat $action): RedirectResponse
    {
        $template = $action->jalankan($request->all());

        return back()->with('sukses', "Status template \"{$template->nama}\" diperbarui.");
    }
}
