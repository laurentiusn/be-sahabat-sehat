<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = \Faker\Factory::create('id_ID');

        return [
            'nama_lengkap'  => $faker->name(),
            // Tanggal lahir random, umur antara 20 - 60 tahun
            'tanggal_lahir' => $faker->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'tempat_lahir'  => $faker->city(),
            'jenis_kelamin' => $faker->randomElement(['laki-laki', 'perempuan']),
            'alamat'        => $faker->address(),
            // NIK 16 digit angka unique
            'nik'           => $faker->unique()->numerify('16##############'),
            // No HP unique
            'no_hp'         => $faker->unique()->phoneNumber(),
            // Kita set default ke pasien karena tujuannya buat list pasien
            'role'          => 'pasien', 
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    // public function unverified(): static
    // {
    //     return $this->state(fn (array $attributes) => [
    //         'email_verified_at' => null,
    //     ]);
    // }
}
