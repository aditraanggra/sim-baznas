<?php

namespace App\Actions\Pengguna;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class ResetSandiPengguna extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): User
    {
        $aturanSandi = Password::min(8)->letters()->numbers();
        if (config('sisfozis.sandi_kuat_ketat', false)) {
            $aturanSandi = $aturanSandi->mixedCase()->symbols();
        }

        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:users,id'],
            'password' => ['required', 'confirmed', $aturanSandi],
            'alasan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): User {
            $user = User::findOrFail($tervalidasi['id']);

            $user->forceFill([
                'password' => $tervalidasi['password'],
            ])->save();

            $this->catatAudit(
                'reset_sandi',
                User::class,
                $user->id,
                null,
                ['email' => $user->email, 'reset_pada' => now()->toIso8601String()],
                $tervalidasi['alasan'] ?? 'Reset sandi oleh Administrator',
            );

            return $user;
        });
    }
}
