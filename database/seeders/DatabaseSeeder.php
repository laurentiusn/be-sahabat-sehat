<?php

namespace Database\Seeders;

use App\Http\Middleware\Pasien;
use App\Models\Tips;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            IndikatorSeeder::class,
            UserSeeder::class,
            TipsSeeder::class,
            PasienDummySeeder::class,
        ]);
    }
}
