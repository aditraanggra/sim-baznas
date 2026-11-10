<?php

namespace App\Http\Controllers\Pengaturan;

use App\Actions\Pos\NonaktifkanPos;
use App\Actions\Pos\SimpanPos;
use App\Http\Controllers\Controller;
use App\Models\PosAnggaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PosAnggaranController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('pengaturan/pos-anggaran', [
            'pos' => PosAnggaran::query()
                ->with('induk:id,kode')
                ->orderBy('lampiran')
                ->orderBy('urutan')
                ->get()
                ->map(fn (PosAnggaran $p) => [
                    'id' => $p->id,
                    'kode' => $p->kode,
                    'nama' => $p->nama,
                    'lampiran' => $p->lampiran,
                    'tingkat' => $p->tingkat,
                    'tipe_nilai' => $p->tipe_nilai,
                    'agregasi' => $p->agregasi,
                    'sifat_waktu' => $p->sifat_waktu,
                    'aktif' => $p->aktif,
                    'induk' => $p->induk?->kode,
                ]),
        ]);
    }

    public function simpan(Request $request, SimpanPos $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Pos anggaran tersimpan.');
    }

    public function nonaktifkan(Request $request, NonaktifkanPos $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Pos dinonaktifkan.');
    }
}
