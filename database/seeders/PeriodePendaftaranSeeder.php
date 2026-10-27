<?php

namespace Database\Seeders;

use App\Models\PeriodePendaftaran;
use Illuminate\Database\Seeder;

class PeriodePendaftaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang 1 TA 2026/2027',
            'tanggal_buka' => '2026-01-01',
            'tanggal_tutup' => '2026-03-31',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);
    }
}
