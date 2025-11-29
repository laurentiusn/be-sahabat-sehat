<?php

namespace Database\Seeders;

use App\Models\Indikator;
use App\Models\Tips;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $indikators = Indikator::all();

        foreach ($indikators as $indikator) {
            // 2. Cek & Buat (firstOrCreate)
            // Logic: Cari Tips berdasarkan 'id_indikator'. 
            // Jika TIDAK ADA, maka buat baru dengan 'konten' => '-'.
            // Jika SUDAH ADA, biarkan saja (jangan ditimpa).
            
            Tips::firstOrCreate(
                ['id_indikator' => $indikator->id], // Kondisi pencarian
                ['konten' => '-']                   // Data default jika dibuat baru
            );
        }
    }
}
