<?php

namespace App\Actions\Pengguna;

use App\Actions\Action;
use App\Models\Concerns\MencatatAudit;
use App\Models\User;
use Database\Seeders\PeranSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SimpanPengguna extends Action
{
    use MencatatAudit;

    public function jalankan(array $data): User
    {
        $data = validator($data, [
            'id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($data['id'] ?? null)],
            'password' => [empty($data['id']) ? 'required' : 'nullable', 'confirmed', Password::defaults()],
            'peran' => ['required', 'array', 'min:1'],
            'peran.*' => [Rule::in(PeranSeeder::PERAN)],
        ])->validate();

        return DB::transaction(function () use ($data) {
            $user = isset($data['id']) ? User::findOrFail($data['id']) : new User;
            $lama = $user->exists ? [...$user->only(['name', 'email']), 'peran' => $user->getRoleNames()->all()] : null;

            $user->fill(collect($data)->only(['name', 'email'])->all());
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }
            $user->save();
            $user->syncRoles($data['peran']);

            $this->catatAudit(
                $user->wasRecentlyCreated ? 'buat' : 'ubah',
                User::class,
                $user->id,
                $lama,
                [...$user->only(['name', 'email']), 'peran' => $user->getRoleNames()->all()],
            );

            return $user;
        });
    }
}
