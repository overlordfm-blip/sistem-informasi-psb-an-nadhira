# Sistem Informasi Penerimaan Santri Baru An-Nadhira

Aplikasi web untuk mendukung proses penerimaan santri baru di Pondok Pesantren An-Nadhira, Kalibeber, Mojotengah, Wonosobo. Sistem ini dikembangkan sebagai proyek kerja praktik Teknik Informatika UNSIQ.

## Tujuan

Mendukung calon santri dan administrator dalam mengelola akun, periode pendaftaran, data pendaftar, seleksi, informasi tagihan, serta status proses penerimaan melalui satu aplikasi.

## Teknologi

- PHP 8.3 atau lebih baru yang didukung oleh dependency terkunci
- Laravel 13
- MySQL
- Blade, Tailwind CSS, Vite, dan JavaScript
- Laravel Socialite untuk Google OAuth
- Midtrans Snap untuk pembayaran dalam mode Sandbox

Versi dependency yang digunakan dikunci melalui `composer.lock` dan `package-lock.json`. `vendor/` dan `node_modules/` tidak disimpan dalam repository.

## Fitur

- Registrasi dan login lokal berbasis nama pengguna dan password
- Peran admin, calon santri, dan santri
- Pengelolaan periode pendaftaran dan formulir pendaftaran
- Pemantauan status pendaftaran dan pengelolaan pendaftar oleh admin
- Pilihan dan pengelolaan tes seleksi
- Tagihan pendaftaran dan syahriyah, dengan notifikasi pembayaran Midtrans
- Penautan dan login Google untuk akun yang memenuhi ketentuan aplikasi
- Alur pemulihan akun

Fitur dan integrasi harus tetap diuji pada environment masing-masing. Keberadaan source code atau automated test tidak berarti callback Google maupun transaksi/webhook Midtrans telah diverifikasi end-to-end.

## Integrasi eksternal

### Midtrans

Konfigurasi menggunakan environment variables. Gunakan Sandbox untuk pengembangan dan pengujian; jangan memasukkan Server Key atau credential lain ke Git, dokumentasi publik, maupun JavaScript selain Client Key yang memang ditujukan untuk browser.

### Google OAuth

Konfigurasi menggunakan Laravel Socialite dan environment variables. Google OAuth berada dalam mode Testing sesuai konfigurasi proyek saat ini. Atur authorized redirect URI dan daftar pengguna uji di Google Cloud Console secara terpisah. Jangan commit Client Secret.

## Persyaratan pengembangan

- PHP 8.3+, Composer, dan extension yang dibutuhkan Laravel/dependency, termasuk PDO MySQL
- MySQL
- Node.js dan npm untuk memasang dependency frontend serta membangun aset
- Git

## Instalasi lokal

1. Clone repository dan masuk ke direktori proyek.
2. Pasang dependency PHP:

   ```bash
   composer install
   ```

3. Siapkan environment lokal. Pada Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

   Isi koneksi MySQL dan konfigurasi lokal yang diperlukan. Jangan menyalin `.env` ke repository. Buat `APP_KEY` satu kali untuk environment baru:

   ```bash
   php artisan key:generate
   ```

   Jangan mengganti key pada environment yang telah menyimpan data terenkripsi tanpa analisis dampak.

4. Pasang dependency frontend dan build aset:

   ```bash
   npm ci
   npm run build
   ```

5. Siapkan database MySQL kosong khusus development dan periksa konfigurasi sebelum menjalankan migration:

   ```bash
   php artisan migrate:status
   php artisan migrate
   ```

   Jangan jalankan migration terhadap database yang berisi data penting tanpa backup yang dapat dipulihkan dan review migration terlebih dahulu. Seeder tidak dijalankan otomatis; tinjau seeder dan data yang dibuat sebelum menjalankannya.

6. Jalankan server development:

   ```bash
   php artisan serve
   ```

## Konfigurasi environment

Gunakan `.env.example` sebagai daftar awal variabel dan isi `.env` lokal tanpa membagikannya. Variabel penting mencakup:

- `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`
- `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION=false`
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`

Jangan menyimpan credential asli, password, token, private key, atau data pribadi di repository. Untuk Google dan Midtrans, pertahankan Testing/Sandbox kecuali ada keputusan terpisah untuk mengubah mode.

## Migration dan data

Migration mendefinisikan skema aplikasi. Untuk development gunakan database lokal kosong yang memang dibuat untuk pengujian. Sebelum migration terhadap database berisi data, buat dan uji backup, periksa `php artisan migrate:status`, lalu review semua migration pending. Jangan menjalankan `migrate:fresh` atau `db:wipe` pada data yang perlu dipertahankan.

Data privat runtime, termasuk tautan Google, catatan penolakan, dan jadwal tes, disimpan pada `storage/app/private/` dan tidak boleh dimasukkan ke repository. Pastikan aturan `.gitignore` tetap mengecualikan data tersebut.

## Automated test

Jalankan seluruh suite dengan:

```bash
php artisan test
```

Gunakan environment testing yang terpisah. Jangan menjalankan tes atau seeder terhadap database produksi.

## Struktur direktori

```text
app/          Logika aplikasi, controller, model, service
bootstrap/    Bootstrap Laravel
config/       Konfigurasi aplikasi dan integrasi
database/     Migration, factory, dan seeder
lang/         Bahasa dan pesan aplikasi
public/       Entry point web dan aset hasil build
resources/    Blade, CSS, dan JavaScript sumber
routes/       Definisi route web dan console
storage/      File runtime; data privat diabaikan Git
tests/        Automated test
```

Hasil build yang saat ini tersedia di `public/build/` dipertahankan dalam repository agar dapat dipakai pada lingkungan yang tidak menyediakan Node.js. `node_modules/` tetap dikecualikan.

## Status dan batasan

Integrasi Midtrans tetap Sandbox dan Google OAuth tetap Testing. Callback eksternal dan proses pembayaran perlu diverifikasi dengan credential uji yang sah pada environment yang sesuai. Fitur upload dokumen persyaratan calon santri merupakan rencana pengembangan lanjutan, bukan bagian dari versi aplikasi saat ini.

Repository ini adalah source code proyek akademik; penggunaan dan redistribusi harus mengikuti persetujuan pemilik proyek dan kebijakan institusi.
