<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Kwitansi;
use Inertia\Inertia;
use Inertia\Response;

class VerifikasiKwitansiController extends Controller
{
    public function show(string $token): Response
    {
        $kwitansi = Kwitansi::query()
            ->with(['penerimaan.items.sumberDana.jenisDana', 'penerimaan.metodeBayar'])
            ->where('token_verifikasi', $token)
            ->first();

        if (! $kwitansi || ! $kwitansi->penerimaan) {
            return Inertia::render('publik/verifikasi-kwitansi', [
                'ditemukan' => false,
                'data' => null,
            ]);
        }

        $penerimaan = $kwitansi->penerimaan;

        return Inertia::render('publik/verifikasi-kwitansi', [
            'ditemukan' => true,
            'data' => [
                'nomor' => $kwitansi->nomor,
                'tanggal_terbit' => $kwitansi->tanggal_terbit?->format('d/m/Y'),
                'status' => $penerimaan->status,
                'metode' => $penerimaan->metodeBayar?->nama ?? '-',
                'total_rupiah' => $penerimaan->totalRupiah(),
                'rincian' => $penerimaan->items->map(fn ($item) => [
                    'jenis_dana' => $item->sumberDana?->jenisDana?->nama ?? '-',
                    'sumber_dana' => $item->sumberDana?->nama ?? '-',
                    'jumlah' => (int) $item->jumlah,
                ])->values()->all(),
            ],
        ]);
    }
}
