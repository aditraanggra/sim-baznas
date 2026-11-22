<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\Disposisi;
use App\Models\Permohonan;
use App\Models\PermohonanStatusLog;
use App\Models\User;
use App\Services\NotifikasiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TolakPermohonan extends Action
{
    use MencatatAudit;

    public function __construct(private readonly NotifikasiService $notifikasi) {}

    public function jalankan(array $data): Permohonan
    {
        $tervalidasi = validator($data, [
            'permohonan_id' => ['required', 'integer', 'exists:permohonan,id'],
            'alasan_tolak' => ['required', 'string', 'min:10', 'max:1000'],
        ])->validate();

        $user = Auth::user();
        if (! $user instanceof User || ! $user->hasAnyRole(['ketua', 'waka_2', 'kabag_pendis_pendag', 'pelayanan_mustahik', 'admin'])) {
            throw new AuthorizationException('Anda tidak berwenang menolak permohonan bantuan.');
        }

        return DB::transaction(function () use ($tervalidasi, $user): Permohonan {
            $permohonan = Permohonan::lockForUpdate()->findOrFail($tervalidasi['permohonan_id']);

            if (in_array($permohonan->status_kode, ['diterima_mustahik', 'ditolak'], true)) {
                throw ValidationException::withMessages([
                    'status_kode' => 'Permohonan yang sudah selesai atau ditolak tidak dapat diproses ulang.',
                ]);
            }

            $lama = $permohonan->only(['status_kode', 'status_publik', 'langkah_sekarang']);

            Disposisi::query()
                ->where('permohonan_id', $permohonan->id)
                ->where('status', 'aktif')
                ->update([
                    'status' => 'ditolak',
                    'catatan' => $tervalidasi['alasan_tolak'],
                    'waktu_tindak_lanjut' => now(),
                ]);

            $permohonan->forceFill([
                'status_kode' => 'ditolak',
                'status_publik' => Permohonan::statusPublikUntuk('ditolak'),
                'langkah_sekarang' => 0,
                'alasan_tolak' => $tervalidasi['alasan_tolak'],
            ])->save();

            PermohonanStatusLog::create([
                'permohonan_id' => $permohonan->id,
                'status_kode' => 'ditolak',
                'status_publik' => 'ditolak',
                'judul_publik' => Permohonan::judulPublikUntuk('ditolak'),
                'keterangan_internal' => $tervalidasi['alasan_tolak'],
                'keterangan_publik' => 'Permohonan belum dapat disetujui berdasarkan hasil verifikasi.',
                'user_id' => $user->id,
                'waktu' => now(),
            ]);

            $this->catatAudit('tolak', Permohonan::class, $permohonan->id, $lama, $permohonan->only(['status_kode', 'alasan_tolak']), $tervalidasi['alasan_tolak']);

            if ($permohonan->created_by && $permohonan->created_by !== $user->id) {
                $this->notifikasi->kirimKeUser($permohonan->created_by, 'permohonan_ditolak', "Permohonan {$permohonan->nomor} Ditolak", $tervalidasi['alasan_tolak'], "/distribusi/permohonan/{$permohonan->id}", Permohonan::class, $permohonan->id);
            }

            return $permohonan->fresh(['disposisi', 'statusLog']);
        });
    }
}
