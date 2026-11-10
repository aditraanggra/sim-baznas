<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PeranSeeder extends Seeder
{
    public const PERAN = ['perencanaan', 'pelaporan', 'keuangan', 'pimpinan', 'admin'];

    public function run(): void
    {
        foreach (self::PERAN as $nama) {
            Role::findOrCreate($nama, 'web');
        }

        // Akun awal wajib ganti sandi (Q8 terbuka; daftar pengguna awal belum ada).
        $admin = User::updateOrCreate(
            ['email' => 'admin@pkp.local'],
            ['name' => 'Administrator', 'password' => Hash::make('password')],
        );
        $admin->syncRoles(['admin']);
    }
}
