# /bangun-fase

Kerjakan **hanya satu fase** dari daftar berikut sesuai nomor yang saya sebutkan, lalu berhenti dan laporkan. Baca dahulu `AGENTS.md`, semua berkas `.agents/rules/`, dan diagram yang relevan di `docs/diagrams/`.

| Fase | Isi | Selesai bila |
|---|---|---|
| 0 | Proyek Laravel 13, `.env`, Tailwind/Vite, locale id, Git | `php artisan serve` dan `npm run dev` jalan |
| 1 | Delapan migrasi, model, relasi, seeder | `migrate:fresh --seed` sukses; relasi diuji di tinker |
| 2 | Registrasi, login, logout, middleware peran, pengalihan | tiga peran masuk ke halaman masing-masing; akses salah → 403 |
| 3 | Token desain, komponen Blade, tiga layout, beranda | beranda sesuai wireframe 01 dan palet |
| 4 | Formulir, validasi, status pendaftaran | aturan "tersedia?", "sudah terdaftar?", dan validasi berjalan |
| 5 | Admin: periode, informasi pondok, data pendaftar, terima/tolak | terima membuat tagihan; peran belum berubah |
| 6 | Tagihan, Snap Midtrans (sandbox), webhook, perubahan peran | uji bayar sandbox → santri dibuat dan peran berubah |
| 7 | Dashboard admin, data pembayaran, perintah tagihan syahriyah | angka dashboard benar; tidak ada tagihan ganda |
| 8 | Pengujian otomatis, perapian, README, data contoh | `php artisan test` hijau; skenario Black Box terpenuhi |
| 9 | Persiapan produksi: HTTPS, `.env` produksi, optimasi, cadangan | daftar periksa deployment terpenuhi |

Setelah fase selesai, laporkan dalam format: **Yang dibuat** (daftar berkas), **Yang diuji** (langkah dan hasil), **Asumsi** (hal yang tidak ditentukan), **Pertanyaan** (bila ada), dan **Layar untuk dokumentasi** (halaman yang perlu saya screenshot untuk laporan Kerja Praktik).
