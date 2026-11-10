<?php

namespace App\Http\Controllers\Pkp;

use App\Actions\Rkat\PetakanAliasRkat;
use App\Actions\Rkat\PratinjauImporRkat;
use App\Actions\Rkat\TerapkanImporRkat;
use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Models\PosAnggaran;
use App\Models\RkatVersion;
use App\Services\Rkat\PemetaanPos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/** Wizard impor RKAT 3 langkah: unggah -> pratinjau (galat per baris, tak terpetakan) -> terapkan. */
class ImporRencanaController extends Controller
{
    public function form(): Response
    {
        return Inertia::render('pkp/rencana/impor', [
            'tahun_default' => (int) date('Y'),
            'draft' => RkatVersion::where(['status' => 'draft', 'arsip' => false])->orderBy('tahun')->get()
                ->map(fn (RkatVersion $v) => ['id' => $v->id, 'tahun' => $v->tahun, 'label' => $v->label(), 'sumber' => $v->sumber])->values(),
        ]);
    }

    public function unggah(Request $request, PratinjauImporRkat $action): RedirectResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'berkas' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ]);

        $path = $request->file('berkas')->store('impor');
        $job = $action->jalankan([
            'tahun' => $validated['tahun'],
            'path_absolut' => Storage::disk('local')->path($path),
            'path' => $path,
            'nama_berkas' => $request->file('berkas')->getClientOriginalName(),
        ]);

        return to_route('pkp.rencana.impor.pratinjau', $job);
    }

    public function pratinjau(ImportJob $job): Response
    {
        abort_unless($job->jenis === 'rkat', 404);

        $baris = $job->rows()->get();
        $tahun = (int) ($baris->first()?->data['tahun'] ?? 0);
        $peta = PemetaanPos::untuk($tahun, $baris->pluck('data.kode_pos')->all());

        return Inertia::render('pkp/rencana/impor-pratinjau', [
            'job' => [
                'id' => $job->id, 'nama_berkas' => $job->nama_berkas, 'tahun' => $tahun,
                'total_baris' => $job->total_baris, 'baris_sukses' => $job->baris_sukses, 'baris_gagal' => $job->baris_gagal,
            ],
            'rows' => $baris->map(function ($r) use ($peta) {
                $pos = $peta[$r->data['kode_pos']] ?? null;

                return [
                    'id' => $r->id, 'nomor_baris' => $r->nomor_baris, 'status' => $r->status, 'galat' => $r->galat,
                    'kode' => $r->data['kode_pos'], 'nama' => $r->data['nama'] ?? null, 'tipe_nilai' => $r->data['tipe_nilai'],
                    'nilai' => $r->data['nilai'], 'subtotal' => (bool) ($r->data['subtotal'] ?? false), 'sheet' => $r->data['sheet'] ?? null,
                    'pos' => $pos ? ['kode' => $pos->kode, 'nama' => $pos->nama, 'daun' => $pos->agregasi === 'input_langsung'] : null,
                ];
            })->values(),
            'tujuan' => RkatVersion::where(['tahun' => $tahun, 'status' => 'draft', 'arsip' => false])->get()
                ->map(fn (RkatVersion $v) => ['id' => $v->id, 'label' => $v->label(), 'sumber' => $v->sumber])->values(),
            'awal_ada' => RkatVersion::where(['tahun' => $tahun, 'jenis' => 'awal'])->exists(),
            'pos_daun' => PosAnggaran::where(['aktif' => true, 'agregasi' => 'input_langsung'])->orderBy('lampiran')->orderBy('urutan')->get()
                ->map(fn (PosAnggaran $p) => ['id' => $p->id, 'kode' => $p->kode, 'nama' => $p->nama, 'tipe_nilai' => $p->tipe_nilai])->values(),
        ]);
    }

    public function terapkan(Request $request, ImportJob $job, TerapkanImporRkat $action): RedirectResponse
    {
        $hasil = $action->jalankan(['import_job_id' => $job->id, 'rkat_version_id' => $request->input('rkat_version_id')]);

        $pesan = "Impor diterapkan: {$hasil['diterapkan']} nilai.";
        if ($hasil['tak_terpetakan']) {
            $pesan .= ' Tak terpetakan: '.implode(', ', $hasil['tak_terpetakan']).'.';
        }
        if ($hasil['peringatan_subtotal']) {
            $pesan .= ' Subtotal berkas berbeda dari hitungan sistem pada '.count($hasil['peringatan_subtotal']).' pos.';
        }

        return to_route('pkp.rencana.show', $hasil['versi'])->with('sukses', $pesan);
    }

    public function alias(Request $request, PetakanAliasRkat $action): RedirectResponse
    {
        $hasil = $action->jalankan($request->only(['tahun', 'kode_alias', 'pos_anggaran_id']));

        return back()->with('sukses', "Alias dipetakan; {$hasil['dipetakan']} nilai Draft diperbarui.");
    }
}
