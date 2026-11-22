<?php

namespace App\Http\Controllers;

use App\Models\BerkasPrivat;
use App\Services\BerkasPrivatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BerkasPrivatController extends Controller
{
    public function unduh(Request $request, string $uuid, BerkasPrivatService $service): Response
    {
        $berkas = BerkasPrivat::where('uuid', $uuid)->firstOrFail();
        $aksi = in_array($request->query('aksi'), ['unduh', 'lihat'], true) ? (string) $request->query('aksi') : 'unduh';

        if (! $berkas->izinkan_publik_bertanda_tangan) {
            $user = $request->user();
            abort_unless($user !== null, 403, 'Silakan login untuk mengakses berkas ini.');

            if ($user->adalahUpzEksternal()) {
                abort_unless(
                    $berkas->upz_id !== null && $berkas->upz_id === $user->upz_id,
                    403,
                    'Anda hanya dapat mengakses berkas UPZ Anda sendiri.',
                );
            } elseif ($berkas->bidang_kode !== null) {
                abort_unless(
                    $user->bolehAksiBidang($berkas->bidang_kode, 'view'),
                    403,
                    'Anda tidak memiliki akses pada bidang berkas ini.',
                );
            }
        }

        abort_unless(Storage::disk($berkas->disk)->exists($berkas->path), 404, 'Berkas fisik tidak ditemukan.');

        $service->catatUnduh($berkas, $request, $aksi);

        if ($aksi === 'lihat') {
            return Storage::disk($berkas->disk)->response($berkas->path, $berkas->nama_asli, [
                'Content-Type' => $berkas->mime,
            ]);
        }

        return Storage::disk($berkas->disk)->download($berkas->path, $berkas->nama_asli);
    }
}
