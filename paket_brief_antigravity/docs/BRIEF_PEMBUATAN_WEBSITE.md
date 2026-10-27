# Brief Pembuatan Website — An-Nadhira Hub

Sistem Informasi Penerimaan Santri Baru Berbasis Web pada Pondok Pesantren An-Nadhira.
Dibangun dengan **Laravel 13** di **Google Antigravity IDE**. Semua perancangan (kebutuhan, UML, ERD, struktur tabel, wireframe) sudah final dan menjadi acuan tunggal. Dokumen ini adalah rencana kerja; aturan yang dibaca agen ada di `AGENTS.md` dan `.agents/`.

## 1. Tujuan dan ruang lingkup
Menggantikan pendaftaran dan pembayaran syahriyah yang masih manual dengan sistem web yang (a) terintegrasi untuk administrasi pendaftaran, (b) membuat pendaftaran online terstruktur, dan (c) memakai payment gateway Midtrans dengan verifikasi otomatis (Bab I).

**Di dalam lingkup:** tiga aktor (Calon Santri/User Umum, Santri/Wali Santri, Admin); informasi pondok; registrasi dan login; formulir pendaftaran; verifikasi pendaftar; tagihan dan pembayaran Midtrans (sandbox); data pembayaran; periode pendaftaran.
**Di luar lingkup:** kegiatan akademik, pengujian keamanan lanjutan, integrasi sistem eksternal selain Midtrans, notifikasi WhatsApp/email, kuitansi, laporan cetak, grafik statistik.

## 2. Isi paket
```
AGENTS.md                       aturan umum agen (dibaca otomatis)
.agents/rules/01-database.md    delapan tabel, kolom, relasi
.agents/rules/02-aturan-bisnis.md  alur, status, Midtrans, webhook
.agents/rules/03-desain-ui.md   warna, huruf, komponen, daftar halaman
.agents/workflows/bangun-fase.md   perintah /bangun-fase
docs/diagrams/                  6 activity, 4 sequence, class diagram, ERD
docs/wireframes/                12 wireframe desktop (SVG, abu-abu)
docs/assets/                    logo-512.png, logo-128.png
```

## 3. Prasyarat di komputer
- **PHP 8.3 atau lebih baru** (Laravel 13 mensyaratkan PHP 8.3+). Cek `php -v` pada PHP bawaan XAMPP; jika lebih lama, pakai PHP 8.3+ terpisah atau perbarui XAMPP.
- Composer, Node.js (LTS) dan npm, Git, MySQL (XAMPP), phpMyAdmin.
- Akun **Midtrans sandbox** (Server Key dan Client Key).
- Alat terowongan (ngrok atau Cloudflare Tunnel) agar Midtrans dapat memanggil webhook di komputer lokal.

## 4. Menyiapkan Antigravity
1. Buat folder proyek, salin `AGENTS.md`, `.agents/`, dan `docs/` ke akar folder, lalu buka folder itu di Antigravity.
2. Antigravity membaca `AGENTS.md` pada IDE versi 1.20.5 ke atas dan aturan dari `.agents/rules/`. Versi lama memakai `.agent/rules/` (tanpa "s"); bila aturan tidak terbaca, ganti nama folder. Tiap berkas aturan dibuat di bawah batas sekitar 12.000 karakter. (Perilaku ini dapat berubah antarversi; cek dokumentasi Antigravity yang terpasang.)
3. Untuk pemula disarankan mode **agent-assisted** dengan peninjauan di setiap langkah penting.
4. Jalankan fase dengan mengetik `/bangun-fase` lalu nomor fase, misalnya `/bangun-fase 1`.
5. Satu fase = satu sesi = satu commit Git. Tinjau hasil dan jalankan aplikasi sebelum lanjut.

## 5. Urutan fase
| Fase | Isi | Hasil yang harus terlihat |
|---|---|---|
| 0 | Proyek Laravel 13, `.env`, Tailwind/Vite, locale id | halaman awal Laravel tampil |
| 1 | Migrasi 8 tabel, model, relasi, seeder | `migrate:fresh --seed` sukses |
| 2 | Registrasi, login, logout, peran | tiga peran masuk ke halaman berbeda |
| 3 | Token desain, komponen, layout, beranda | beranda sesuai wireframe 01 |
| 4 | Formulir dan status pendaftaran | validasi dan aturan "tersedia?/sudah terdaftar?" |
| 5 | Admin: periode, informasi, pendaftar, terima/tolak | terima membuat tagihan |
| 6 | Midtrans Snap, webhook, perubahan peran | bayar di sandbox → santri terbentuk |
| 7 | Dashboard admin, data pembayaran, tagihan syahriyah | angka benar, tanpa tagihan ganda |
| 8 | Pengujian otomatis dan Black Box, perapian | semua skenario lulus |
| 9 | Persiapan produksi | daftar periksa deployment |

**Perintah awal Fase 0 (tempel di Agent Manager):**
> Baca AGENTS.md dan seluruh berkas di .agents/rules/. Jalankan /bangun-fase 0: buat proyek Laravel 13 di folder ini tanpa starter kit, hubungkan ke MySQL lokal, set locale id dan zona waktu Asia/Jakarta, pasang Tailwind CSS lewat Vite, tulis `.env.example` dengan SESSION_DRIVER=file, CACHE_STORE=file, QUEUE_CONNECTION=sync dan kunci Midtrans kosong. Inisialisasi Git. Lalu laporkan sesuai format workflow.

**Fase 6** memerlukan Server Key dan Client Key sandbox di `.env`, serta URL notifikasi pada dasbor Midtrans sandbox (Settings → Configuration → Payment Notification URL) menuju `https://<alamat-tunnel>/midtrans/notification`.

## 6. Keputusan yang masih harus Anda tetapkan
| No | Hal | Asumsi saat ini |
|---|---|---|
| 1 | Nominal biaya pendaftaran | nilai contoh di `config/pondok.php`, ganti sebelum uji bayar |
| 2 | Nominal dan cara membuat tagihan syahriyah | perintah Artisan bulanan untuk santri aktif (tidak ada menu admin) |
| 3 | Batas percobaan transaksi per pembayaran | tidak dibatasi (hanya satu yang boleh berhasil) |
| 4 | Kebijakan password | minimal 8 karakter |
| 5 | Pendaftar yang ditolak | tidak bisa mendaftar ulang dengan akun yang sama |
| 6 | Data pondok (alamat, telepon, peta) | diisi di `config/pondok.php` |
| 7 | Hosting dan domain produksi | belum ditentukan; Midtrans produksi butuh akun terverifikasi |
| 8 | Penjadwal Laravel (cron) di hosting | perlu didukung jika syahriyah otomatis dipakai |

## 7. Skenario pengujian Black Box (untuk Bab IV dan demo)
| Kode | Skenario | Hasil yang diharapkan |
|---|---|---|
| U-01 | Registrasi dengan nama user baru dan password 8 karakter | akun tersimpan, menuju login |
| U-02 | Registrasi dengan nama user yang sudah ada | pesan galat, akun tidak dibuat |
| U-03 | Login benar sebagai admin / calon santri / santri | masuk ke halaman sesuai peran |
| U-04 | Login dengan password salah | pesan umum, tidak masuk |
| U-05 | Calon santri membuka formulir saat periode ditutup | pesan "pendaftaran ditutup" |
| U-06 | Isi formulir lengkap dan valid | pendaftar tersimpan berstatus diproses |
| U-07 | Isi formulir dengan telepon tidak valid | pesan galat di kolom, input dipertahankan |
| U-08 | Mendaftar kedua kali dengan akun yang sama | ditampilkan status, tidak ada data ganda |
| U-09 | Admin menerima pendaftar | status diterima, tagihan dibuat, peran belum berubah |
| U-10 | Admin menolak pendaftar | status ditolak, tanpa tagihan |
| U-11 | Bayar tagihan di sandbox (berhasil) | pembayaran berhasil, tagihan lunas, santri dibuat, peran berubah |
| U-12 | Pembayaran kedaluwarsa atau gagal | status sesuai, tagihan tetap belum dibayar, bisa bayar ulang |
| U-13 | Webhook dengan signature salah | ditolak 403, data tidak berubah |
| U-14 | Notifikasi berhasil dikirim dua kali | tidak ada data ganda (idempoten) |
| U-15 | Calon santri membuka `/admin/dashboard` | 403 |
| U-16 | Pengguna melihat tagihan milik orang lain lewat URL | 403/404 |
| U-17 | Admin tambah, ubah, hapus informasi pondok | tampil benar di beranda |
| U-18 | Admin buka lalu tutup periode | status berubah, formulir mengikuti |

## 8. Daftar periksa deployment (Fase 9)
`APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` baru; HTTPS aktif; kredensial basis data khusus; kunci Midtrans produksi dan URL notifikasi produksi; `php artisan config:cache route:cache view:cache`; izin tulis `storage` dan `bootstrap/cache`; cron untuk scheduler; cadangan MySQL berkala; hapus akun contoh dan ganti password admin; serah terima dibuktikan dengan Berita Acara.

## 9. Untuk laporan dan demo
- Ambil **screenshot tiap fase** untuk subbab implementasi; catat tanggal pengerjaan di logbook.
- Daftar perangkat lunak Bab IV (4.1.3) menyebut Visual Studio Code. Perbarui menjadi Google Antigravity IDE dan tambahkan Composer, Node.js, serta Git; pertimbangkan menyebut Blade dan Tailwind CSS sebagai pustaka tampilan.
- Nilai program berasal dari pembimbing (35%), penguji lewat demo (30%), dan instansi (40%). Penguji akan menanyakan cara kerja kode, jadi minta agen menjelaskan setiap fase dan pastikan Anda dapat menjelaskan alur login, pendaftaran, dan webhook pembayaran.
- Konsultasikan dengan dosen pembimbing tentang pencantuman penggunaan alat bantu AI dalam pengembangan, sesuai kebijakan kampus.
