<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Data Pondok Pesantren
    |--------------------------------------------------------------------------
    | Isi sesuai data nyata sebelum deployment.
    */

    'nama' => 'Pondok Pesantren An-Nadhira',
    'alamat' => env('PONDOK_ALAMAT', 'Kalibeber, Mojotengah, Wonosobo, Jawa Tengah'),
    'telepon' => '0821-0000-0000',
    'whatsapp' => env('PONDOK_WHATSAPP', '6285188578976'),
    'jam_layanan' => env('PONDOK_JAM_LAYANAN', 'Senin–Sabtu, 08.00–15.00 WIB'),
    'email' => 'info@an-nadhira.test',
    'maps_url' => '',

    /*
    |--------------------------------------------------------------------------
    | Nominal Tagihan
    |--------------------------------------------------------------------------
    | Dalam satuan rupiah (integer).
    | Ganti nilai ini sebelum uji bayar di Midtrans.
    */

    'biaya_pendaftaran' => 150000,   // Rp 150.000
    'syahriyah' => 300000,   // Rp 300.000 per bulan
    'gmail_domain' => env('PONDOK_GMAIL_DOMAIN', 'gmail.com'),
];
