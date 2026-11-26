<?php

namespace App\Actions\Pengaturan;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\TautanAkses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PutarTautanAkses extends Action
{
    use MencatatAudit;

    /**
     * @return array{tautan: TautanAkses, token_mentah: string}
     */
    public function jalankan(array $data): array
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('admin')) {
            throw new AuthorizationException('Hanya Administrator Sistem yang dapat memutar token tautan akses.');
        }

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:tautan_akses,id'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): array {
            $lama = TautanAkses::lockForUpdate()->findOrFail((int) $tervalidasi['id']);

            if ($lama->dicabut_at !== null) {
                throw ValidationException::withMessages([
                    'id' => 'Tautan yang sudah dicabut tidak dapat diputar. Buat tautan baru.',
                ]);
            }

            $lamaState = $lama->only(['label', 'jenis', 'token_prefix', 'dicabut_at', 'dicabut_oleh']);
            $lama->update([
                'dicabut_at' => now(),
                'dicabut_oleh' => $user->id,
            ]);

            $this->catatAudit(
                'putar_cabut',
                TautanAkses::class,
                $lama->id,
                $lamaState,
                $lama->fresh()->only(['label', 'jenis', 'token_prefix', 'dicabut_at', 'dicabut_oleh']),
                'Token diputar ke tautan baru',
            );

            $tokenMentah = bin2hex(random_bytes(32));
            $kadaluarsaBaru = $lama->kadaluarsa_at && $lama->kadaluarsa_at->isFuture() ? $lama->kadaluarsa_at : null;

            $baru = TautanAkses::create([
                'label' => $lama->label,
                'jenis' => $lama->jenis,
                'token_hash' => hash('sha256', $tokenMentah),
                'token_prefix' => substr($tokenMentah, 0, 8),
                'kadaluarsa_at' => $kadaluarsaBaru,
                'catatan' => $lama->catatan,
                'dibuat_oleh' => $user->id,
            ]);

            $this->catatAudit(
                'putar_buat',
                TautanAkses::class,
                $baru->id,
                null,
                [
                    'label' => $baru->label,
                    'jenis' => $baru->jenis,
                    'token_prefix' => $baru->token_prefix,
                    'menggantikan_id' => $lama->id,
                    'kadaluarsa_at' => $baru->kadaluarsa_at?->toIso8601String(),
                ],
            );

            return [
                'tautan' => $baru,
                'token_mentah' => $tokenMentah,
            ];
        });
    }
}
