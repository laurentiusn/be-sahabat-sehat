<?php

namespace Database\Seeders;

use App\Models\Indikator;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IndikatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timestamp = Carbon::now();

        // --- DATA GULA DARAH PUASA (GD) ---
        $gulaDarah = [
            [
                'type' => 'gd',
                'status' => 'NORMAL',
                'range' => json_encode(['min' => 70, 'max' => 99]),
                'color_hex' => '#28a745', // Hijau
            ],
            [
                'type' => 'gd',
                'status' => 'WASPADA',
                'range' => json_encode(['min' => 100, 'max' => 125]),
                'color_hex' => '#ffc107', // Kuning/Oranye
            ],
            [
                'type' => 'gd',
                'status' => 'TINGGI',
                'range' => json_encode(['min' => 126, 'max' => null]), // >= 126
                'color_hex' => '#dc3545', // Merah
            ],
        ];

        // --- DATA INDEKS MASSA TUBUH (IMT) ---
        $imt = [
            [
                'type' => 'imt',
                'status' => 'UNDERWEIGHT',
                'range' => json_encode(['min' => null, 'max' => 18.4]), // < 18.5
                'color_hex' => '#dc3545', // Merah (Kurang gizi biasanya merah)
            ],
            [
                'type' => 'imt',
                'status' => 'NORMOWEIGHT',
                'range' => json_encode(['min' => 18.5, 'max' => 22.9]),
                'color_hex' => '#28a745', // Hijau
            ],
            [
                'type' => 'imt',
                'status' => 'OVERWEIGHT',
                // Catatan: Gambar menyebutkan >= 23.0, tapi Obesity mulai 25.0.
                // Maka range ini kita set 23.0 - 24.9 agar tidak tumpang tindih.
                'range' => json_encode(['min' => 23.0, 'max' => 24.9]),
                'color_hex' => '#ffc107', // Kuning
            ],
            [
                'type' => 'imt',
                'status' => 'OBESITY',
                'range' => json_encode(['min' => 25.0, 'max' => null]), // >= 25.0
                'color_hex' => '#dc3545', // Merah
            ],
        ];

        // --- DATA TEKANAN DARAH (TD) ---
        // Catatan: TD memiliki Sistolik dan Diastolik.
        // Logika JNC VII:
        // Normal: Sys < 120 DAN Dia < 80
        // Pre: Sys 120-139 ATAU Dia 80-89
        $tekananDarah = [
            [
                'type' => 'td',
                'status' => 'Normal',
                'range' => json_encode([
                    'sys_min' => null, 'sys_max' => 119,
                    'dia_min' => null, 'dia_max' => 79,
                    'operator' => 'AND' // Keduanya harus terpenuhi
                ]),
                'color_hex' => '#28a745', // Hijau
            ],
            [
                'type' => 'td',
                'status' => 'Pre-hipertensi',
                'range' => json_encode([
                    'sys_min' => 120, 'sys_max' => 139,
                    'dia_min' => 80,  'dia_max' => 89,
                    'operator' => 'OR' // Salah satu terpenuhi masuk kategori ini
                ]),
                'color_hex' => '#ffc107', // Kuning
            ],
            [
                'type' => 'td',
                'status' => 'Stadium 1',
                'range' => json_encode([
                    'sys_min' => 140, 'sys_max' => 159,
                    'dia_min' => 90,  'dia_max' => 99,
                    'operator' => 'OR'
                ]),
                'color_hex' => '#fd7e14', // Oranye Tua
            ],
            [
                'type' => 'td',
                'status' => 'Stadium 2',
                'range' => json_encode([
                    'sys_min' => 160, 'sys_max' => null,
                    'dia_min' => 100, 'dia_max' => null,
                    'operator' => 'OR'
                ]),
                'color_hex' => '#dc3545', // Merah
            ],
        ];

        // Menggabungkan semua data
         $all = array_merge($gulaDarah, $imt, $tekananDarah);

        // Gunakan create() sehingga HasUuids akan generate id otomatis
        foreach ($all as $row) {
            Indikator::create($row);
        }

        // DB::table('indikator')->insert($data);
    }
}
