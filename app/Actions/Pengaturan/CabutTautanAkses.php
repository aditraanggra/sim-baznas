<?php

namespace App\Actions\Pengaturan;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\TautanAkses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CabutTautanAkses extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): TautanAkses
    {
        $user = Auth::user();
        if (! $user || ! $user->hasRole('admin')) {
            throw new AuthorizationException('Hanya Administrator Sistem yang dapat mencabut tautan akses khusus.');
        }

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:tautan_akses,id'],
            'alasan' => ['nullable', 'string', 'max:500'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $user): TautanAkses {
            $tautan = TautanAkses::lockForUpdate()->findOrFail((int) $tervalidasi['id']);

            if ($tautan->dicabut_at !== null) {
                throw ValidationException::withMessages([
                    'id' => 'Tautan akses ini sudah dicabut sebelumnya.',
                ]);
            }

            $lama = $tautan->only(['label', 'jenis', 'token_prefix', 'dicabut_at', 'dicabut_oleh']);

            $tautan->update([
                'dicabut_at' => now(),
                'dicabut_oleh' => $user->id,
            ]);

            $this->catatAudit(
                'cabut',
                TautanAkses::class,
                $tautan->id,
                $lama,
                $tautan->fresh()->only(['label', 'jenis', 'token_prefix', 'dicabut_at', 'dicabut_oleh']),
                $tervalidasi['alasan'] ?? 'Dicabut oleh Administrator',
            );

            return $tautan->fresh();
        });
    }
}
