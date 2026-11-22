<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PeranSeeder extends Seeder
{
    public const PERAN_SISFOZIS = [
        'pelayanan_muzakki',
        'pelayanan_mustahik',
        'admin_arsip',
        'perencanaan',
        'keuangan',
        'kabag_sau',
        'kabag_pengumpulan',
        'kabag_pendis_pendag',
        'kabag_pkp',
        'staf_pendistribusian',
        'staf_pendayagunaan',
        'amil',
        'waka_1',
        'waka_2',
        'waka_3',
        'waka_4',
        'ketua',
        'upz',
        'admin',
    ];

    public const PERAN_LEGACY = ['pelaporan', 'pimpinan'];

    public const PERAN = [...self::PERAN_SISFOZIS, ...self::PERAN_LEGACY];

    public function run(): void
    {
        foreach (self::PERAN as $nama) {
            Role::findOrCreate($nama, 'web');
        }

        // Akun awal wajib ganti sandi dan aktifkan 2FA.
        $admin = User::updateOrCreate(
            ['email' => 'admin@pkp.local'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'status' => 'aktif',
            ],
        );
        $admin->syncRoles(['admin', 'amil']);
    }
}
