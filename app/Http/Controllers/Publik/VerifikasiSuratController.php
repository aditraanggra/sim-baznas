<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\SuratKeluar;
use Inertia\Inertia;
use Inertia\Response;

class VerifikasiSuratController extends Controller
{
    public function show(string $uuid): Response
    {
        $surat = SuratKeluar::query()
            ->with('jenisSurat:id,kode,nama')
            ->where('uuid', $uuid)
            ->whereIn('status_kode', ['disetujui', 'dibatalkan'])
            ->first();

        if (! $surat) {
            return Inertia::render('publik/verifikasi-surat', [
                'ditemukan' => false,
                'data' => null,
            ]);
        }

        $rahasia = $surat->sifat === 'rahasia';

        return Inertia::render('publik/verifikasi-surat', [
            'ditemukan' => true,
            'data' => [
                'nomor' => $surat->nomor ?? '-',
                'tanggal' => $surat->tanggal?->format('d/m/Y') ?? '-',
                'jenis_surat' => $surat->jenisSurat?->nama ?? '-',
                'sifat' => $surat->sifat,
                'status' => $surat->status_kode,
                'perihal' => $rahasia ? 'Dokumen Rahasia (Perihal Disamarkan)' : $surat->perihal,
                'tujuan' => $rahasia ? 'Pihak Terkait (Disamarkan)' : $surat->tujuan,
                'nama_penandatangan' => $surat->nama_penandatangan,
                'jabatan_penandatangan' => $surat->jabatan_penandatangan,
                'disetujui_at' => $surat->disetujui_at?->format('d/m/Y H:i'),
            ],
        ]);
    }
}
