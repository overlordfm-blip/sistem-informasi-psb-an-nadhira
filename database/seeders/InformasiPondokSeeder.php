<?php

namespace Database\Seeders;

use App\Models\InformasiPondok;
use App\Models\User;
use Illuminate\Database\Seeder;

class InformasiPondokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('peran', User::PERAN_ADMIN)->first();

        if (! $admin) {
            return;
        }

        $today = now()->toDateString();

        InformasiPondok::create([
            'id_user' => $admin->id_user,
            'judul' => 'Profil Pondok Pesantren An-Nadhira',
            'isi' => 'Pondok Pesantren An-Nadhira berlokasi di Kalibeber, Mojotengah, Wonosobo, Jawa Tengah. Lembaga pendidikan Islam yang berfokus pada pembentukan karakter santri berakhlakul karimah, penguasaan literatur klasik (kitab kuning), dan wawasan kontemporer.',
            'diperbarui' => $today,
        ]);

        InformasiPondok::create([
            'id_user' => $admin->id_user,
            'judul' => 'Kegiatan dan Kurikulum Pembelajaran',
            'isi' => 'Kurikulum memadukan pengajian kitab kuning tradisional salafiyah, tahfidz Al-Qur\'an, pengembangan bahasa Arab dan Inggris, serta kegiatan ekstrakurikuler kepemimpinan santri.',
            'diperbarui' => $today,
        ]);

        InformasiPondok::create([
            'id_user' => $admin->id_user,
            'judul' => 'Kontak dan Informasi Pendaftaran',
            'isi' => 'Sekretariat Pendaftaran PSB: Kompleks Pondok Pesantren An-Nadhira, Kalibeber, Mojotengah, Wonosobo. Narahubung pendaftaran dan informasi santri baru dapat dihubungi melalui Telepon/WhatsApp di nomor 0821-0000-0000 atau surel info@an-nadhira.test.',
            'diperbarui' => $today,
        ]);
    }
}
