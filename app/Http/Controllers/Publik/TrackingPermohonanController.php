<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Services\IdentitasPribadiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackingPermohonanController extends Controller
{
    public function index(Request $request, IdentitasPribadiService $identitas): Response
    {
        $kode = strtoupper(trim($request->string('kode_tracking')->toString()));
        $telepon4 = preg_replace('/\D+/', '', $request->string('telepon_4')->toString()) ?? '';

        $sudahDicari = $kode !== '' || $telepon4 !== '';
        $hasil = null;
        $pesanError = null;

        if ($sudahDicari) {
            if ($kode === '' || strlen($telepon4) !== 4) {
                $pesanError = 'Masukkan kode tracking dan tepat 4 digit akhir nomor telepon pemohon.';
            } else {
                $permohonan = Permohonan::query()
                    ->with(['mustahik:id,nama,telepon', 'program:id,nama', 'statusLog'])
                    ->where('kode_tracking', $kode)
                    ->first();

                $akhirTelepon = $permohonan?->mustahik?->telepon
                    ? $identitas->empatDigitAkhirTelepon($permohonan->mustahik->telepon)
                    : null;

                if (! $permohonan || $akhirTelepon === null || ! hash_equals($akhirTelepon, $telepon4)) {
                    $pesanError = 'Permohonan tidak ditemukan atau 4 digit akhir nomor telepon tidak cocok.';
                } else {
                    // Q-09 & Q-14: tanpa nama petugas internal dan hanya menampilkan 7 status publik.
                    $hasil = [
                        'kode_tracking' => $permohonan->kode_tracking,
                        'nomor' => $permohonan->nomor,
                        'tanggal' => $permohonan->tanggal?->format('d/m/Y'),
                        'pemohon_samaran' => $identitas->samarkanNamaPublik($permohonan->mustahik?->nama),
                        'program' => $permohonan->program?->nama ?? '-',
                        'status_publik' => $permohonan->status_publik,
                        'judul_status' => Permohonan::judulPublikUntuk($permohonan->status_publik),
                        'linimasa' => $permohonan->statusLog->map(fn ($log) => [
                            'status_publik' => $log->status_publik,
                            'judul' => $log->judul_publik,
                            'keterangan' => $log->keterangan_publik ?? '-',
                            'waktu' => $log->waktu?->format('d/m/Y H:i'),
                        ])->values()->all(),
                    ];
                }
            }
        }

        return Inertia::render('publik/lacak-permohonan', [
            'kode_tracking' => $kode,
            'telepon_4' => $telepon4,
            'sudah_dicari' => $sudahDicari,
            'pesan_error' => $pesanError,
            'hasil' => $hasil,
        ]);
    }
}
