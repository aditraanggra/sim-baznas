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
        $id = isset($data['id']) && $data['id'] !== '' ? (int) $data['id'] : null;

        $aturanSandi = Password::min(8)->letters()->numbers();
        if (config('sisfozis.sandi_kuat_ketat', false)) {
            $aturanSandi = $aturanSandi->mixedCase()->symbols();
        }

        $tervalidasi = validator($data, [
            'id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'min:3',
                'max:64',
                'regex:/^[a-zA-Z0-9_.]+$/',
                Rule::unique('users', 'username')->ignore($id),
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => [empty($id) ? 'required' : 'nullable', 'confirmed', $aturanSandi],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
            'upz_id' => ['nullable', 'integer'],
            'peran' => ['required', 'array', 'min:1'],
            'peran.*' => [Rule::in(PeranSeeder::PERAN)],
        ])->validate();

        return DB::transaction(function () use ($tervalidasi, $id) {
            $user = $id ? User::findOrFail($id) : new User;
            $lama = $user->exists ? [
                ...$user->only(['name', 'username', 'email', 'status', 'upz_id']),
                'peran' => $user->getRoleNames()->all(),
            ] : null;

            $peran = array_values(array_unique($tervalidasi['peran']));
            $adaPeranInternal = collect($peran)->contains(fn (string $nama) => $nama !== 'upz');
            if ($adaPeranInternal) {
                $peran = array_values(array_unique([...$peran, 'amil']));
            }

            $username = ! empty($tervalidasi['username'])
                ? strtolower((string) $tervalidasi['username'])
                : ($user->username ?: $this->buatUsernameOtomatis((string) $tervalidasi['email'], $id));

            $user->fill([
                'name' => $tervalidasi['name'],
                'username' => $username,
                'email' => $tervalidasi['email'],
                'status' => $tervalidasi['status'] ?? ($user->status ?: 'aktif'),
                'upz_id' => $tervalidasi['upz_id'] ?? $user->upz_id,
            ]);

            if (! empty($tervalidasi['password'])) {
                $user->password = $tervalidasi['password'];
            }

            $user->save();
            $user->syncRoles($peran);

            $this->catatAudit(
                $user->wasRecentlyCreated ? 'buat' : 'ubah',
                User::class,
                $user->id,
                $lama,
                [
                    ...$user->only(['name', 'username', 'email', 'status', 'upz_id']),
                    'peran' => $user->getRoleNames()->all(),
                ],
            );

            return $user;
        });
    }

    private function buatUsernameOtomatis(string $email, ?int $ignoreId = null): string
    {
        $dasar = strtolower(preg_replace('/[^a-zA-Z0-9_.]/', '', strstr($email, '@', true) ?: 'user') ?? 'user');
        $dasar = substr($dasar !== '' ? $dasar : 'user', 0, 48);
        $kandidat = $dasar;
        $counter = 1;

        while (User::query()
            ->where('username', $kandidat)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $kandidat = "{$dasar}_{$counter}";
            $counter++;
        }

        return $kandidat;
    }
}
