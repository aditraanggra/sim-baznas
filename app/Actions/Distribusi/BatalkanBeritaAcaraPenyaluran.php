<?php

namespace App\Actions\Distribusi;

use App\Actions\Action;
use App\Models\BeritaAcaraPenyaluran;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penyaluran;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatalkanBeritaAcaraPenyaluran extends Action
{
    use MencatatAudit;

    public const PERAN_BATALKAN_FINAL = ['admin', 'kabag_pendis_pendag'];

    public function jalankan(array $data): BeritaAcaraPenyaluran
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:berita_acara_penyaluran,id'],
            'alasan' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min' => 'Alasan pembatalan minimal 10 karakter.',
        ])->validate();

        $user = Auth::user();
        if (! $user instanceof User) {
            throw new AuthorizationException('Pengguna harus login terlebih dahulu.');
        }

        return DB::transaction(function () use ($tervalidasi, $user): BeritaAcaraPenyaluran {
            $ba = BeritaAcaraPenyaluran::lockForUpdate()->findOrFail($tervalidasi['id']);

            if (! in_array($ba->status, ['draft', 'final'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Berita acara ini sudah dibatalkan atau digantikan revisi.',
                ]);
            }

            if ($ba->status === 'final' && ! $user->hasAnyRole(self::PERAN_BATALKAN_FINAL)) {
                throw new AuthorizationException('Hanya Administrator atau Kepala Bagian Pendis Pendag yang dapat membatalkan berita acara final.');
            }

            $lama = $ba->only(['status']);

            $ba->forceFill([
                'status' => 'batal',
                'alasan_batal' => $tervalidasi['alasan'],
                'dibatalkan_oleh' => $user->id,
                'dibatalkan_at' => now(),
            ])->save();

            Penyaluran::where('berita_acara_id', $ba->id)->update(['berita_acara_id' => null]);

            $this->catatAudit(
                $lama['status'] === 'final' ? 'batalkan_final' : 'batalkan_draft',
                BeritaAcaraPenyaluran::class,
                $ba->id,
                $lama,
                ['status' => 'batal'],
                $tervalidasi['alasan'],
            );

            return $ba->fresh();
        });
    }
}
