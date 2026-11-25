<?php

namespace App\Http\Controllers\Pkp;

use App\Actions\Perencanaan\SimpanPemetaanPos;
use App\Http\Controllers\Controller;
use App\Models\Asnaf;
use App\Models\Bidang;
use App\Models\JenisDana;
use App\Models\PosAnggaran;
use App\Models\PosAnggaranPemetaan;
use App\Models\Program;
use App\Models\SumberDana;
use App\Models\TahunAnggaran;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PemetaanKegiatanController extends Controller
{
    public function index(Request $request): Response
    {
        $bidangId = $request->input('bidang_id');
        $sumber = $request->input('sumber');
        $user = $request->user();

        $daftarKegiatan = PosAnggaranPemetaan::query()
            ->with(['posAnggaran:id,kode,nama,lampiran,tipe_nilai,urutan', 'bidang:id,kode,nama', 'jenisDana:id,kode,nama', 'sumberDana:id,kode,nama', 'asnaf:id,kode,nama', 'program:id,kode,nama'])
            ->when($bidangId, fn ($q) => $q->where('bidang_id', $bidangId))
            ->when($sumber, fn ($q) => $q->where('sumber_transaksi', $sumber))
            ->get()
            ->sortBy(fn (PosAnggaranPemetaan $item) => [$item->posAnggaran?->lampiran ?? 'ZZ', $item->posAnggaran?->urutan ?? 9999])
            ->values();

        return Inertia::render('pkp/kegiatan/index', [
            'bidang' => config('sisfozis.bidang.pkp'),
            'kegiatan' => $daftarKegiatan,
            'tahun_anggaran' => TahunAnggaran::orderByDesc('tahun')->get(),
            'daftar_bidang' => Bidang::orderBy('urutan')->get(['id', 'nama', 'kode']),
            'daftar_jenis_dana' => JenisDana::where('aktif', true)->orderBy('id')->get(['id', 'nama', 'kode']),
            'daftar_sumber_dana' => SumberDana::where('aktif', true)->orderBy('id')->get(['id', 'nama', 'kode', 'jenis_dana_id']),
            'daftar_asnaf' => Asnaf::where('aktif', true)->orderBy('urutan')->get(['id', 'nama', 'kode']),
            'daftar_program' => Program::where('aktif', true)->orderBy('id')->get(['id', 'nama', 'kode']),
            'daftar_pos' => PosAnggaran::where('aktif', true)
                ->where('agregasi', 'input_langsung')
                ->orderBy('lampiran')
                ->orderBy('urutan')
                ->get(['id', 'kode', 'nama', 'lampiran', 'tipe_nilai']),
            'filter' => [
                'bidang_id' => $bidangId ? (string) $bidangId : '',
                'sumber' => $sumber ? (string) $sumber : '',
            ],
            'akses' => [
                'boleh_kelola_kegiatan' => $user?->bolehAksiBidang('pkp', 'update') ?? false,
                'boleh_buat_tahun' => $user?->bolehAksiBidang('pkp', 'create') ?? false,
                'boleh_status_tahun' => $user?->bolehAksiBidang('pkp', 'approve') ?? false,
            ],
        ]);
    }

    public function store(Request $request, SimpanPemetaanPos $action): RedirectResponse
    {
        $action->jalankan($request->all());

        return back()->with('sukses', 'Pemetaan kegiatan pos anggaran berhasil disimpan.');
    }
}
