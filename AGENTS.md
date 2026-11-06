# AGENTS.md — An-Nadhira

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
2. Registrasi hanya memerlukan **`nama_user` + `password`**. Login memakai nama user/password atau Google untuk akun calon santri/santri yang sudah tertaut. Password di-hash (bcrypt bawaan Laravel).
3. Tiga peran lewat kolom `users.peran`: `admin`, `calon_santri`, `santri`. Wali santri memakai akun santri yang sama. Tidak ada class/tabel Admin atau Wali terpisah.
4. Satu akun hanya boleh punya **satu baris** pendaftaran (`pendaftar.id_user` unik; relasi `User hasOne Pendaftar` tetap). Jika pendaftaran ditolak pada periode lama dan periode berbeda dibuka, gunakan kembali baris yang sama: perbarui data dan `id_periode`, lalu ubah status menjadi `diproses`. Penolakan pada periode yang sedang dibuka tidak boleh didaftarkan ulang pada periode itu.
5. Peran berubah dari `calon_santri` menjadi `santri` **hanya setelah pembayaran tagihan pendaftaran berhasil**, bukan saat admin menerima pendaftar. Alur rinci di `.agents/rules/02-aturan-bisnis.md`.
6. Status pembayaran hanya boleh diubah oleh **webhook Midtrans yang tanda tangannya terverifikasi**. Callback JavaScript di browser hanya untuk tampilan, bukan sumber kebenaran.
7. Kunci rahasia (`MIDTRANS_SERVER_KEY`, password admin) hanya di `.env`; jangan di kode, Git, atau JavaScript klien. Tidak menyimpan data kartu atau rekening.
8. Desain mengikuti `.agents/rules/03-desain-ui.md` dan wireframe di `docs/wireframes/`.
9. Hapus periode: hanya periode yang sudah terlewat (tanggal_tutup < hari ini), berstatus ditutup, dan tanpa pendaftar yang boleh dihapus; selain itu ditolak dengan pesan. Wajib konfirmasi dan hanya untuk admin
10. Calon santri berstatus `diproses` atau `diterima` tidak bisa mendaftar lagi. Pendaftar berstatus `ditolak` hanya dapat mendaftar ulang bila periode berbeda sedang dibuka; baris pendaftar yang sama diperbarui, bukan dibuat duplikat. Jika ditolak pada periode yang sedang dibuka, tampilkan pesan agar menunggu periode berikutnya.
11. Data tambahan tanpa kolom (catatan penolakan, berkas dokumen) disimpan sebagai berkas di storage/app/private/. Tidak boleh ada migrasi atau perubahan skema.
12. Verifikasi, login, dan pemulihan Gmail tidak mengubah skema. Login memakai `nama_user` + password atau Google untuk akun calon santri/santri yang sudah tertaut; autentikasi Google memakai `sub`, bukan email, dan admin tidak dapat masuk lewat Google. Gunakan Socialite yang kompatibel dengan Laravel 13, scope `openid email profile`, dan berkas tautan di `storage/app/private/google/` berisi email tersamar saja. Token/profil Google tidak boleh disimpan. Throttle rute Google 10/menit/IP; log hanya ID pengguna dan waktu. Detail alur wajib mengikuti `.agents/rules/02-aturan-bisnis.md`.
13. Jadwal tes online per calon santri disimpan di berkas tes privat `storage/app/private/tes/pendaftar-{id_pendaftar}.json`, bukan skema database. Daftar admin hanya menampilkan calon berstatus `diproses` yang memilih mode online dan belum terjadwal; waktu mulai yang identik tidak boleh dipakai dua calon. Sesudah dijadwalkan mode dikunci dan jadwal tampil pada halaman `/tes`. Ikuti `.agents/rules/02-aturan-bisnis.md`.

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
Prioritas saat ini: fungsi dan alur dulu, tampilan terakhir. Sampai Fase 3 dikerjakan, buat halaman polos: HTML semantik dengan satu layout bersama, tanpa warna atau font khusus. Pisahkan logika dari tampilan (controller, service, dan form request), pakai rute bernama, partial untuk pesan flash dan field formulir, dan jangan menulis gaya inline. Dengan begitu Fase 3 cukup mengganti layout dan komponen tanpa menyentuh logika.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
