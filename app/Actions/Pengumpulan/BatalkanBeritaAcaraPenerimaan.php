<?php

namespace App\Actions\Pengumpulan;

use App\Actions\Action;
use App\Models\BeritaAcaraPenerimaan;
use App\Models\Concerns\MencatatAudit;
use App\Models\Penerimaan;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BatalkanBeritaAcaraPenerimaan extends Action
{
    use MencatatAudit;

    public const PERAN_BATALKAN_FINAL = ['admin', 'kabag_pengumpulan'];

    public function jalankan(array $data): BeritaAcaraPenerimaan
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:berita_acara_penerimaan,id'],
            'alasan' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min' => 'Alasan pembatalan minimal 10 karakter.',
        ])->validate();

        $user = Auth::user();
        if (! $user instanceof User) {
            throw new AuthorizationException('Pengguna harus login terlebih dahulu.');
        }

        return DB::transaction(function () use ($tervalidasi, $user): BeritaAcaraPenerimaan {
            $ba = BeritaAcaraPenerimaan::lockForUpdate()->findOrFail($tervalidasi['id']);

            if (! in_array($ba->status, ['draft', 'final'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Berita acara ini sudah dibatalkan atau digantikan revisi.',
                ]);
            }

            if ($ba->status === 'final' && ! $user->hasAnyRole(self::PERAN_BATALKAN_FINAL)) {
                throw new AuthorizationException('Hanya Administrator atau Kepala Bagian Pengumpulan yang dapat membatalkan berita acara final.');
            }

            $lama = $ba->only(['status']);

            $ba->forceFill([
                'status' => 'batal',
                'alasan_batal' => $tervalidasi['alasan'],
                'dibatalkan_oleh' => $user->id,
                'dibatalkan_at' => now(),
            ])->save();

            Penerimaan::where('berita_acara_id', $ba->id)->update(['berita_acara_id' => null]);

            $this->catatAudit(
                $lama['status'] === 'final' ? 'batalkan_final' : 'batalkan_draft',
                BeritaAcaraPenerimaan::class,
                $ba->id,
                $lama,
                ['status' => 'batal'],
                $tervalidasi['alasan'],
            );

            return $ba->fresh();
        });
    }
}
