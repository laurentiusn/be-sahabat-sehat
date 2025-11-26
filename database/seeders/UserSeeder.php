<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'nama_lengkap'  => 'Admin',
                'tanggal_lahir' => '2000-01-01',
                'role'          => 'admin',
            ],
            [
                'nama_lengkap'  => 'Pasien 1',
                'tanggal_lahir' => '2000-01-01',
                'role'          => 'pasien',
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate([
                'nama_lengkap' => $user['nama_lengkap'],
                'tanggal_lahir' => $user['tanggal_lahir'],
            ], $user);
        }
    }
}
