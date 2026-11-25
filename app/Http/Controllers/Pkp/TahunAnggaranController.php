<?php

namespace App\Http\Controllers\Pkp;

use App\Actions\Perencanaan\SimpanTahunAnggaran;
use App\Actions\Perencanaan\UbahStatusTahunAnggaran;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TahunAnggaranController extends Controller
{
    public function store(Request $request, SimpanTahunAnggaran $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Tahun anggaran berhasil disimpan.');
    }

    public function ubahStatus(Request $request, UbahStatusTahunAnggaran $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Status tahun anggaran berhasil diperbarui.');
    }
}
