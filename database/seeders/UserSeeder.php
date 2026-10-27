<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Akun admin utama
        User::create([
            'nama_user' => 'admin',
            'password' => env('ADMIN_PASSWORD', 'admin123'),
            'peran' => User::PERAN_ADMIN,
        ]);

        // 2. Akun calon santri contoh untuk pengujian
        User::create([
            'nama_user' => 'calon_santri1',
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        User::create([
            'nama_user' => 'calon_santri2',
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        User::create([
            'nama_user' => 'calon_santri3',
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
    }
}
