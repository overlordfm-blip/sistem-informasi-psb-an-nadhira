# Aturan Desain Antarmuka

Wireframe desktop abu-abu ada di `docs/wireframes/` (12 halaman). Wireframe menentukan **struktur dan isi**; warna, huruf, dan logo mengikuti aturan berikut. Tampilan harus responsif (mobile-first): kolom bertumpuk di ponsel, sidebar admin menjadi menu geser.

## Warna (palet utama, wajib)
| Peran | Hex | Pemakaian |
|---|---|---|
| Hijau tua (primer) | `#1F5F3B` | navbar/sidebar, tombol utama, judul |
| Hijau (sekunder) | `#2E7D32` | hover, status berhasil, tautan |
| Emas (aksen) | `#D4AF37` | garis aksen, badge, tombol sekunder |
| Krem (latar) | `#F8F5EE` | latar halaman |
| Hitam (teks) | `#1E1E1E` | teks isi |
| Putih (permukaan) | `#FFFFFF` | kartu, tabel, input |
| Garis | `#E3DCCB` | border dan pemisah |
| Bahaya | `#B3261E` | pesan kesalahan, status gagal |
| Peringatan | `#8A6D00` teks / `#FFF3CD` latar | status menunggu |
Definisikan sebagai token Tailwind (`tailwind.config`/`@theme`), jangan menulis hex berulang di Blade.

**Aturan kontras (sudah dihitung):** teks putih di atas hijau tua = 7,6 (aman); hitam di atas krem = 15,3 (aman); hitam di atas emas = 7,9 (aman); emas di atas hijau tua = 3,6 (**hanya teks besar/tebal**); **emas di atas krem atau putih = 1,9 (dilarang sebagai teks atau ikon)**. Tombol emas selalu bertulisan hitam.

## Huruf
- Judul: **Lora** (serif), tebal. Isi dan antarmuka: **Inter**. Keduanya dari Google Fonts, dimuat dengan `font-display: swap`.
- Ukuran isi minimal 16px; tabel minimal 14px. Judul halaman 28–32px.

## Logo
`docs/assets/logo-512.png` dan `logo-128.png` (latar transparan, perisai hijau). Salin ke `public/images/`. Logo hijau akan menyatu bila diletakkan langsung di atas hijau tua: tempatkan di navbar **putih/krem**, atau di dalam lingkaran putih pada sidebar admin.

## Komponen Blade (buat sekali, pakai ulang)
`x-layout.publik`, `x-layout.pengguna`, `x-layout.admin`, `x-tombol` (varian: utama, sekunder, bahaya), `x-input`, `x-textarea`, `x-select`, `x-kartu`, `x-badge-status`, `x-tabel`, `x-paginasi`, `x-notifikasi` (flash sukses/galat), `x-tahapan` (stepper).

Badge status (warna dan teks seragam di seluruh halaman):
- Pendaftaran: `diproses` netral/emas muda, `diterima` hijau, `ditolak` merah.
- Pembayaran/transaksi: `menunggu` kuning, `berhasil` hijau, `gagal` merah, `kedaluwarsa` abu-abu.
- Tagihan: `belum_dibayar` kuning, `lunas` hijau. Periode: `dibuka` hijau, `ditutup` abu-abu.

## Daftar halaman (nama berkas wireframe)
| Rute | Halaman | Wireframe |
|---|---|---|
| `/` | Beranda: informasi pondok | `01_informasi_pondok` |
| `/login` | Masuk | `02_login` |
| `/registrasi` | Registrasi akun | `03_registrasi` |
| `/pendaftaran` | Formulir pendaftaran | `04_formulir_pendaftaran` |
| `/status-pendaftaran` | Status pendaftaran (stepper 4 tahap: registrasi, pendaftaran, verifikasi, pembayaran) | `05_status_pendaftaran` |
| `/tagihan` | Tagihan dan pembayaran + riwayat | `06_tagihan_pembayaran` |
| `/admin/dashboard` | Dashboard admin | `07_admin_dashboard` |
| `/admin/informasi` | Kelola informasi pondok | `08_admin_informasi_pondok` |
| `/admin/periode` | Periode pendaftaran | `09_admin_periode_pendaftaran` |
| `/admin/pendaftar` | Data pendaftar | `10_admin_data_pendaftar` |
| `/admin/pendaftar/{id}` | Verifikasi pendaftar | `11_admin_verifikasi_pendaftar` |
| `/admin/pembayaran` | Data pembayaran | `12_admin_data_pembayaran` |
Formulir pendaftaran **hanya** lima field: nama lengkap, tanggal lahir, alamat, nama wali, no. telepon. Jangan menambah field lain. Pop-up pembayaran adalah milik Midtrans; jangan ditiru.

## Beranda
Hero: label status periode ("Pendaftaran dibuka/ditutup"), judul "Penerimaan Santri Baru", tombol Daftar Sekarang dan Lihat Alur. "Tentang Pondok" dan "Kontak" dari `config/pondok.php`. Kartu "Informasi Pondok" dari tabel `informasi_pondok`. "Alur Pendaftaran" statis (4 langkah).

## Umum
Tombol berisi kata kerja; pesan galat di bawah kolom terkait; notifikasi sukses di atas halaman; tabel kosong menampilkan keadaan kosong; formulir menjaga input lama; semua teks Bahasa Indonesia baku dan ramah.
