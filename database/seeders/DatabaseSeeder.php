<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@cobit.test'], [
            'name' => 'Administrator', 'npm' => 'ADMIN001', 'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN, 'prodi' => 'SI', 'angkatan' => 24, 'points' => 0,
        ]);

        foreach (range(1, 6) as $index) {
            User::updateOrCreate(['email' => "mahasiswa{$index}@cobit.test"], [
                'name' => "Mahasiswa {$index}", 'npm' => '241000'.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'password' => Hash::make('password'), 'role' => User::ROLE_MAHASISWA,
                'prodi' => 'SI', 'angkatan' => 24, 'points' => 0,
            ]);
        }

        $this->call(BankSoalSeeder::class);
    }
}
