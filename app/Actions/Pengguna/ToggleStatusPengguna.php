<?php

namespace App\Actions\Pengguna;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ToggleStatusPengguna extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): User
    {
        $tervalidasi = validator($data, [
            'id' => ['required', 'integer', 'exists:users,id'],
            'alasan' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi): User {
            $user = User::findOrFail($tervalidasi['id']);

            if ((int) $user->id === (int) Auth::id() && $user->adalahAktif()) {
                throw ValidationException::withMessages([
                    'id' => 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang login.',
                ]);
            }

            $lama = ['status' => $user->status ?? 'aktif'];
            $statusBaru = $user->adalahAktif() ? 'nonaktif' : 'aktif';

            $user->forceFill(['status' => $statusBaru])->save();

            $this->catatAudit(
                $statusBaru === 'aktif' ? 'aktifkan' : 'nonaktifkan',
                User::class,
                $user->id,
                $lama,
                ['status' => $statusBaru],
                $tervalidasi['alasan'] ?? null,
            );

            return $user;
        });
    }
}
