<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoaSeeder::class,
            TransaksiTemplateSeeder::class,
            PeranSeeder::class,
            DataAwalSeeder::class,
            M2PerencanaanSeeder::class,
            M3PerencanaanSeeder::class,
            KonsinyasiDemoSeeder::class,
        ]);

        if (! $this->app->environment('testing')) {
            $this->call(PerencanaanDemoSeeder::class);
        }
    }
}
