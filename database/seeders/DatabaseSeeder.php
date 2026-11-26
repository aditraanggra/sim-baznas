<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PeranSeeder::class,
            BidangSeeder::class,
            WilayahSeeder::class,
            MasterDanaSeeder::class,
            MasterOperasionalSeeder::class,
            JenisUpzSeeder::class,
            PosAnggaranSeeder::class,
            PosAnggaranAlurBaruSeeder::class,
            RkatSeeder::class,
            PerencanaanJembatanSeeder::class,
            AdministrasiSeeder::class,
            KategoriDokumenSeeder::class,
        ]);
    }
}
