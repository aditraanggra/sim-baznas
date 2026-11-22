<?php

namespace App\Actions\Pengguna;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class KelolaDuaFaktor extends Action
{
    use MencatatAudit;

    public function __construct(private readonly TotpService $totp) {}

    public function jalankan(array $data): User
    {
        $user = User::findOrFail($data['user_id'] ?? Auth::id());
        $aksi = (string) ($data['aksi'] ?? 'aktifkan');

        if ($aksi === 'aktifkan') {
            $data = validator($data, [
                'secret' => ['required', 'string', 'min:16', 'max:64'],
                'code' => ['required', 'string'],
            ])->validate();

            if (! $this->totp->verifikasi($data['secret'], $data['code'])) {
                throw ValidationException::withMessages([
                    'code' => 'Kode autentikator 6 digit tidak valid atau sudah kedaluwarsa.',
                ]);
            }

            $lama = ['two_factor_aktif' => $user->sudahAktif2Fa()];
            $user->forceFill([
                'two_factor_secret' => $data['secret'],
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => $this->totp->buatRecoveryCodes(),
            ])->save();

            $this->catatAudit('aktifkan_2fa', User::class, $user->id, $lama, ['two_factor_aktif' => true]);

            return $user;
        }

        if ($user->wajib2Fa()) {
            throw ValidationException::withMessages([
                'two_factor' => 'Peran Administrator dan Keuangan wajib mengaktifkan 2FA.',
            ]);
        }

        $lama = ['two_factor_aktif' => $user->sudahAktif2Fa()];
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $this->catatAudit('nonaktifkan_2fa', User::class, $user->id, $lama, ['two_factor_aktif' => false]);

        return $user;
    }
}
