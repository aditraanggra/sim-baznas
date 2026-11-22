<?php

namespace App\Http\Controllers\Pengumpulan;

use App\Actions\Pengumpulan\SimpanMuzakki;
use App\Http\Controllers\Controller;
use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Muzakki;
use App\Models\Penerimaan;
use App\Services\IdentitasPribadiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MuzakkiController extends Controller
{
    public function index(Request $request, IdentitasPribadiService $identitas): Response
    {
        $cari = trim($request->string('cari')->toString());
        $jenis = $request->string('jenis')->toString();
        $kecamatanId = $request->integer('kecamatan_id');

        $nikHashCari = preg_match('/^\d{16}$/', preg_replace('/\D+/', '', $cari) ?? '')
            ? $identitas->hashPencarian($cari)
            : null;

        $daftar = Muzakki::query()
            ->with(['kecamatan:id,nama', 'desa:id,nama'])
            ->when($jenis !== '' && in_array($jenis, ['perorangan', 'lembaga'], true), fn ($q) => $q->where('jenis', $jenis))
            ->when($kecamatanId > 0, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->when($cari !== '', function ($q) use ($cari, $nikHashCari): void {
                $q->where(function ($sub) use ($cari, $nikHashCari): void {
                    $sub->where('nama', 'ilike', "%{$cari}%")
                        ->orWhere('kode', 'ilike', "%{$cari}%")
                        ->orWhere('telepon', 'ilike', "%{$cari}%");
                    if ($nikHashCari !== null) {
                        $sub->orWhere('nik_hash', $nikHashCari);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('pengumpulan/muzakki/index', [
            'muzakki' => $daftar->through(fn (Muzakki $m) => [
                'id' => $m->id,
                'kode' => $m->kode,
                'jenis' => $m->jenis,
                'nama' => $m->nama,
                'nik_masked' => $m->nik_masked,
                'npwp' => $m->npwp,
                'telepon' => $m->telepon,
                'email' => $m->email,
                'alamat' => $m->alamat,
                'kecamatan_id' => $m->kecamatan_id,
                'desa_id' => $m->desa_id,
                'wilayah' => trim(($m->desa?->nama ? $m->desa->nama.', ' : '').($m->kecamatan?->nama ?? '')),
                'aktif' => $m->aktif,
            ]),
            'kecamatan_list' => Kecamatan::where('aktif', true)->orderBy('nama')->get(['id', 'nama']),
            'desa_list' => Desa::where('aktif', true)->orderBy('nama')->get(['id', 'kecamatan_id', 'nama']),
            'filter' => [
                'cari' => $cari,
                'jenis' => $jenis,
                'kecamatan_id' => $kecamatanId ?: null,
            ],
            'bisa_kelola' => $request->user()->bolehAksiBidang('pengumpulan', 'create'),
        ]);
    }

    public function cekDuplikat(Request $request, SimpanMuzakki $action): JsonResponse
    {
        $duplikat = $action->cekKemungkinanDuplikat(
            $request->string('nik')->toString(),
            $request->string('telepon')->toString(),
            $request->integer('ignore_id') ?: null,
        );

        return response()->json(['duplikat' => $duplikat]);
    }

    public function store(Request $request, SimpanMuzakki $action): RedirectResponse
    {
        $duplikat = $action->cekKemungkinanDuplikat(
            $request->input('nik'),
            $request->input('telepon'),
            $request->integer('id') ?: null,
        );

        $muzakki = $action->jalankan($request->all());

        $redirect = back()->with('sukses', "Data muzakki {$muzakki->nama} ({$muzakki->kode}) tersimpan.");
        if ($duplikat !== []) {
            $redirect->with('peringatan', 'Ditemukan muzakki lain dengan telepon yang sama. Periksa data untuk mencegah duplikasi.');
        }

        return $redirect;
    }

    public function show(Muzakki $muzakki): Response
    {
        $muzakki->load(['kecamatan:id,nama', 'desa:id,nama']);

        $setoran = Penerimaan::query()
            ->where('muzakki_id', $muzakki->id)
            ->with(['metodeBayar:id,nama', 'upz:id,nama', 'kwitansi:id,penerimaan_id,nomor', 'items.sumberDana:id,nama'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Penerimaan $p) => [
                'id' => $p->id,
                'nomor' => $p->kwitansi?->nomor ?? $p->nomor ?? '(Draft)',
                'tanggal' => $p->tanggal?->format('d/m/Y'),
                'status' => $p->status,
                'metode' => $p->metodeBayar?->nama,
                'upz' => $p->upz?->nama,
                'sumber_dana' => $p->items->map(fn ($i) => $i->sumberDana?->nama)->filter()->implode(', '),
                'total' => $p->totalRupiah(),
            ]);

        return Inertia::render('pengumpulan/muzakki/show', [
            'muzakki' => [
                'id' => $muzakki->id,
                'kode' => $muzakki->kode,
                'jenis' => $muzakki->jenis,
                'nama' => $muzakki->nama,
                'nik_masked' => $muzakki->nik_masked,
                'npwp' => $muzakki->npwp,
                'telepon' => $muzakki->telepon,
                'email' => $muzakki->email,
                'alamat' => $muzakki->alamat,
                'kecamatan' => $muzakki->kecamatan?->nama,
                'desa' => $muzakki->desa?->nama,
                'aktif' => $muzakki->aktif,
            ],
            'setoran' => $setoran,
            'total_setoran_final' => $setoran->where('status', 'final')->sum('total'),
        ]);
    }
}
