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
            RkatSeeder::class,
        ]);
    }
}
