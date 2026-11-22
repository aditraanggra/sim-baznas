<?php

namespace App\Services;

use App\Models\BerkasPrivat;
use App\Models\BerkasUnduhLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class BerkasPrivatService
{
    public function unggah(UploadedFile $berkas, string $kelompok, array $opsi = []): BerkasPrivat
    {
        $disk = (string) ($opsi['disk'] ?? 'private');
        $uuid = (string) Str::uuid();
        $ekstensi = $berkas->getClientOriginalExtension() ?: $berkas->guessExtension() ?: 'bin';
        $direktori = trim($kelompok, '/').'/'.now()->format('Y/m');
        $path = $berkas->storeAs($direktori, "{$uuid}.{$ekstensi}", $disk);
        $checksum = $berkas->getRealPath() ? hash_file('sha256', $berkas->getRealPath()) : null;

        return BerkasPrivat::create([
            'uuid' => $uuid,
            'kelompok' => $kelompok,
            'entitas_tipe' => $opsi['entitas_tipe'] ?? null,
            'entitas_id' => $opsi['entitas_id'] ?? null,
            'disk' => $disk,
            'path' => $path,
            'nama_asli' => $berkas->getClientOriginalName(),
            'mime' => $berkas->getMimeType() ?? $berkas->getClientMimeType() ?? 'application/octet-stream',
            'ukuran' => (int) ($berkas->getSize() ?? 0),
            'checksum_sha256' => $checksum,
            'bidang_kode' => $opsi['bidang_kode'] ?? null,
            'upz_id' => $opsi['upz_id'] ?? null,
            'izinkan_publik_bertanda_tangan' => (bool) ($opsi['izinkan_publik_bertanda_tangan'] ?? false),
        ]);
    }

    public function simpan(UploadedFile $berkas, string $kelompok, array $opsi = []): BerkasPrivat
    {
        return $this->unggah($berkas, $kelompok, $opsi);
    }

    public function urlBertandaTangan(BerkasPrivat $berkas, int $menitBerlaku = 30, string $aksi = 'unduh'): string
    {
        return URL::temporarySignedRoute(
            'berkas.privat.unduh',
            now()->addMinutes($menitBerlaku),
            ['uuid' => $berkas->uuid, 'aksi' => $aksi],
        );
    }

    public function catatUnduh(BerkasPrivat $berkas, Request $request, string $aksi = 'unduh'): BerkasUnduhLog
    {
        return BerkasUnduhLog::create([
            'berkas_privat_id' => $berkas->id,
            'user_id' => $request->user()?->id,
            'aksi' => $aksi,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'waktu' => now(),
        ]);
    }
}
