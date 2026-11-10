<?php

namespace App\Http\Controllers\Pkp;

use App\Http\Controllers\Controller;
use App\Models\RkatVersion;
use App\Services\Rkat\BandingkanRkat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BandingkanController extends Controller
{
    /** FR-R-07: bandingkan dua versi atau dua tahun (versi Disahkan, atau terbaru bila belum ada). */
    public function index(Request $request): Response
    {
        $sisi = ['a' => $this->sisi($request, 'a'), 'b' => $this->sisi($request, 'b')];
        $dipilih = $sisi['a'] !== null && $sisi['b'] !== null;

        $hasil = $dipilih && $sisi['a']['versi'] && $sisi['b']['versi']
            ? BandingkanRkat::bandingkan($sisi['a']['versi'], $sisi['b']['versi'])
            : null;

        $meta = fn (?array $s) => $s === null ? null : [
            'galat' => $s['galat'],
            'bukan_disahkan' => $s['bukan_disahkan'],
            'versi' => $s['versi'] ? $this->ringkasVersi($s['versi']) : null,
        ];

        return Inertia::render('pkp/rencana/bandingkan', [
            'mode' => $request->filled('tahun_a') || $request->filled('tahun_b') ? 'tahun' : 'versi',
            'daftar_versi' => RkatVersion::where('arsip', false)->orderByDesc('tahun')->orderBy('jenis')->orderBy('nomor_perubahan')->get()
                ->map(fn (RkatVersion $v) => $this->ringkasVersi($v))->values(),
            'a' => $meta($sisi['a']),
            'b' => $meta($sisi['b']),
            'hasil' => $hasil,
        ]);
    }

    /** @return array{versi: ?RkatVersion, bukan_disahkan: bool, galat: ?string}|null null bila sisi tidak dipilih */
    private function sisi(Request $request, string $s): ?array
    {
        $idVersi = $request->query("versi_$s");
        $tahun = $request->query("tahun_$s");

        if (filled($idVersi)) {
            $versi = RkatVersion::find((int) $idVersi);

            return ['versi' => $versi, 'bukan_disahkan' => false, 'galat' => $versi ? null : "Versi $idVersi tidak ditemukan."];
        }

        if (filled($tahun)) {
            $list = RkatVersion::where('tahun', (int) $tahun)->where('arsip', false)->orderByDesc('id')->get();
            $versi = $list->firstWhere('status', 'disahkan') ?? $list->first();

            return [
                'versi' => $versi,
                'bukan_disahkan' => $versi !== null && $versi->status !== 'disahkan',
                'galat' => $versi ? null : "Tahun $tahun belum punya versi RKAT.",
            ];
        }

        return null;
    }

    private function ringkasVersi(RkatVersion $v): array
    {
        return ['id' => $v->id, 'tahun' => $v->tahun, 'label' => $v->label(), 'status' => $v->status];
    }
}
