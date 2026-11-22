<?php

namespace App\Http\Controllers;

use App\Models\Mustahik;
use App\Models\Muzakki;
use App\Models\Penerimaan;
use App\Models\Penyaluran;
use App\Models\Permohonan;
use App\Models\PosAnggaran;
use App\Models\Upz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PencarianGlobalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = trim($request->string('q')->toString());

        if (! $user || mb_strlen($q) < 2) {
            return response()->json(['hasil' => []]);
        }

        $hasil = [];

        if ($user->bolehAksiBidang('pengumpulan', 'view')) {
            Muzakki::query()
                ->where(fn ($sub) => $sub->where('nama', 'ilike', "%{$q}%")->orWhere('kode', 'ilike', "%{$q}%"))
                ->orderBy('nama')
                ->limit(5)
                ->get(['id', 'kode', 'nama'])
                ->each(function (Muzakki $m) use (&$hasil): void {
                    $hasil[] = [
                        'kategori' => 'Muzakki',
                        'judul' => $m->nama,
                        'subjudul' => $m->kode,
                        'url' => route('pengumpulan.muzakki.show', $m->id),
                    ];
                });

            Penerimaan::query()
                ->with('kwitansi:id,penerimaan_id,nomor')
                ->where(function ($sub) use ($q): void {
                    $sub->where('nomor', 'ilike', "%{$q}%")
                        ->orWhereHas('kwitansi', fn ($k) => $k->where('nomor', 'ilike', "%{$q}%"));
                })
                ->orderByDesc('tanggal')
                ->limit(5)
                ->get(['id', 'nomor', 'tanggal'])
                ->each(function (Penerimaan $p) use (&$hasil): void {
                    $hasil[] = [
                        'kategori' => 'Penerimaan',
                        'judul' => $p->kwitansi?->nomor ?? $p->nomor ?? "Penerimaan #{$p->id}",
                        'subjudul' => $p->tanggal?->format('d/m/Y') ?? '-',
                        'url' => route('pengumpulan.penerimaan.show', $p->id),
                    ];
                });
        }

        if ($user->bolehAksiBidang('distribusi', 'view')) {
            Mustahik::query()
                ->where(fn ($sub) => $sub->where('nama', 'ilike', "%{$q}%")->orWhere('kode', 'ilike', "%{$q}%"))
                ->orderBy('nama')
                ->limit(5)
                ->get(['id', 'kode', 'nama'])
                ->each(function (Mustahik $m) use (&$hasil): void {
                    $hasil[] = [
                        'kategori' => 'Mustahik',
                        'judul' => $m->nama,
                        'subjudul' => $m->kode,
                        'url' => route('distribusi.mustahik.show', $m->id),
                    ];
                });

            Permohonan::query()
                ->where(fn ($sub) => $sub->where('nomor', 'ilike', "%{$q}%")->orWhere('kode_tracking', 'ilike', "%{$q}%"))
                ->orderByDesc('tanggal')
                ->limit(5)
                ->get(['id', 'nomor', 'kode_tracking', 'status_kode'])
                ->each(function (Permohonan $p) use (&$hasil): void {
                    $hasil[] = [
                        'kategori' => 'Permohonan',
                        'judul' => $p->nomor,
                        'subjudul' => "{$p->kode_tracking} · {$p->status_kode}",
                        'url' => route('distribusi.permohonan.show', $p->id),
                    ];
                });

            Penyaluran::query()
                ->where('nomor', 'ilike', "%{$q}%")
                ->orderByDesc('tanggal')
                ->limit(5)
                ->get(['id', 'nomor', 'tanggal'])
                ->each(function (Penyaluran $py) use (&$hasil): void {
                    $hasil[] = [
                        'kategori' => 'Penyaluran',
                        'judul' => $py->nomor ?? "Penyaluran #{$py->id}",
                        'subjudul' => $py->tanggal?->format('d/m/Y') ?? '-',
                        'url' => route('distribusi.penyaluran.index', ['cari' => $py->nomor]),
                    ];
                });
        }

        if ($user->bolehAksiBidang('umum', 'view')) {
            Upz::query()
                ->when($user->adalahUpzEksternal(), fn ($query) => $query->where('id', (int) ($user->upz_id ?? 0)))
                ->where(fn ($sub) => $sub->where('nama', 'ilike', "%{$q}%")->orWhere('kode', 'ilike', "%{$q}%"))
                ->orderBy('nama')
                ->limit(5)
                ->get(['id', 'kode', 'nama'])
                ->each(function (Upz $u) use (&$hasil): void {
                    $hasil[] = [
                        'kategori' => 'UPZ',
                        'judul' => $u->nama,
                        'subjudul' => $u->kode,
                        'url' => route('umum.upz.show', $u->id),
                    ];
                });
        }

        if ($user->bolehAksiBidang('pkp', 'view')) {
            PosAnggaran::query()
                ->where(fn ($sub) => $sub->where('kode', 'ilike', "%{$q}%")->orWhere('nama', 'ilike', "%{$q}%"))
                ->orderBy('kode')
                ->limit(5)
                ->get(['id', 'kode', 'nama', 'lampiran'])
                ->each(function (PosAnggaran $pos) use (&$hasil): void {
                    $hasil[] = [
                        'kategori' => 'Pos Anggaran',
                        'judul' => "{$pos->lampiran}/{$pos->kode} - {$pos->nama}",
                        'subjudul' => 'RKAT & Realisasi PKP',
                        'url' => route('pkp.rencana'),
                    ];
                });
        }

        return response()->json(['hasil' => array_slice($hasil, 0, 20)]);
    }
}
