# AGENTS.md — An-Nadhira Hub

Sistem Informasi Penerimaan Santri Baru Berbasis Web pada Pondok Pesantren An-Nadhira (Kalibeber, Mojotengah, Wonosobo). Proyek Kerja Praktik Teknik Informatika UNSIQ. Semua perancangan sudah final dan didokumentasikan di `docs/`. **Ikuti perancangan itu; jangan mengubah struktur data, alur, atau istilah tanpa izin pemilik proyek.**

## Tumpukan teknologi (tetap)
- Laravel 13 (PHP 8.3+), MySQL (lokal lewat XAMPP), Composer, Node.js + Vite.
- Tampilan: **Blade + Tailwind CSS**. Jangan memakai Livewire, Inertia, React, atau Vue.
- Pembayaran: Midtrans Snap (sandbox) memakai paket `midtrans/midtrans-php`.
- Tanpa starter kit (Breeze/Jetstream). Autentikasi ditulis sendiri.
- Bahasa antarmuka, pesan validasi, nama tabel, kolom, dan istilah domain: **Bahasa Indonesia**. Kode (nama kelas/metode) boleh Inggris atau Indonesia, tetapi konsisten.
- Zona waktu `Asia/Jakarta`, locale `id`. Format rupiah `Rp 1.250.000`, tanggal `27 Oktober 2026`.

## Aturan inti
1. Basis data **hanya delapan tabel aplikasi**: `users`, `periode_pendaftaran`, `pendaftar`, `santri`, `tagihan`, `pembayaran`, `transaksi`, `informasi_pondok` (rincian di `.agents/rules/01-database.md`). Tidak ada kolom `created_at`/`updated_at` (`$timestamps = false`). Agar tidak muncul tabel tambahan, set di `.env`: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`.
2. Login memakai **`nama_user` + `password`** (bukan email, bukan Google). Password di-hash (bcrypt bawaan Laravel).
3. Tiga peran lewat kolom `users.peran`: `admin`, `calon_santri`, `santri`. Wali santri memakai akun santri yang sama. Tidak ada class/tabel Admin atau Wali terpisah.
4. Satu akun hanya boleh punya **satu** pendaftaran (`pendaftar.id_user` unik).
5. Peran berubah dari `calon_santri` menjadi `santri` **hanya setelah pembayaran tagihan pendaftaran berhasil**, bukan saat admin menerima pendaftar. Alur rinci di `.agents/rules/02-aturan-bisnis.md`.
6. Status pembayaran hanya boleh diubah oleh **webhook Midtrans yang tanda tangannya terverifikasi**. Callback JavaScript di browser hanya untuk tampilan, bukan sumber kebenaran.
7. Kunci rahasia (`MIDTRANS_SERVER_KEY`, password admin) hanya di `.env`; jangan di kode, Git, atau JavaScript klien. Tidak menyimpan data kartu atau rekening.
8. Desain mengikuti `.agents/rules/03-desain-ui.md` dan wireframe di `docs/wireframes/`.

## Struktur kode yang diharapkan
- `app/Models/` satu model per tabel, dengan `$table`, `$primaryKey`, `$timestamps=false`, `$fillable`, relasi, dan konstanta nilai ENUM.
- `app/Http/Controllers/` (publik/pengguna) dan `app/Http/Controllers/Admin/`.
- `app/Http/Requests/` satu Form Request per formulir.
- `app/Services/` untuk `MidtransService` dan `PembayaranService` (logika notifikasi).
- `app/Http/Middleware/PeriksaPeran.php` (alias `peran`, contoh `peran:admin`).
- `app/Console/Commands/` untuk pembuatan tagihan syahriyah bulanan.
- `resources/views/` dengan komponen Blade di `resources/views/components/`.
- `config/pondok.php` untuk data pondok dan nominal tagihan.
- `database/migrations/`, `database/seeders/`, `tests/Feature/`.

## Cara bekerja
- Kerjakan **satu fase per permintaan** (daftar fase: `docs/BRIEF_PEMBUATAN_WEBSITE.md` dan workflow `/bangun-fase`). Jangan melompat ke fase berikutnya.
- Sebelum menulis kode fase baru: baca aturan di `.agents/rules/` dan diagram terkait di `docs/diagrams/`.
- Setelah selesai: jalankan `php artisan test`, jalankan aplikasi, uji alur utama fase tersebut, lalu laporkan apa yang dibuat, apa yang diuji, dan hasil pengujian.
- Jika ada hal yang tidak ditentukan dalam dokumen, **tanyakan atau catat sebagai asumsi** di bagian "Asumsi" pada laporan fase. Jangan mengarang aturan bisnis.
- Pesan commit Git dalam Bahasa Indonesia, satu commit per fitur.
- Jangan menjalankan perintah yang menghapus data (`migrate:fresh`, `db:wipe`) tanpa konfirmasi.
