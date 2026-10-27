# Aturan Bisnis dan Alur Sistem

Sumber: use case, enam activity diagram, empat sequence diagram, dan class diagram di `docs/diagrams/`. Jika kode bertentangan dengan dokumen ini, dokumen ini yang benar.

## Peran dan hak akses
| Peran | Boleh | Setelah login diarahkan ke |
|---|---|---|
| (tamu) | beranda/informasi pondok, login, registrasi | — |
| calon_santri | formulir pendaftaran, status pendaftaran, tagihan, bayar | `/status-pendaftaran` |
| santri (termasuk wali) | tagihan, bayar, status pembayaran | `/tagihan` |
| admin | semua halaman `/admin/*` | `/admin/dashboard` |
Middleware `peran:<nilai>` menolak akses lain dengan 403. Pengguna hanya boleh melihat tagihan/pembayaran **miliknya sendiri** (lewat `users → pendaftar → tagihan`).

## Registrasi dan login
- Registrasi: input `nama_user` (wajib, unik, maks 50, huruf/angka/titik/garis bawah) dan `password` (wajib, minimal 8 karakter). Akun disimpan dengan `peran = calon_santri`, lalu diarahkan ke halaman login dengan pesan sukses. Registrasi tidak otomatis login.
- Login: `Auth::attempt(['nama_user' => ..., 'password' => ...])`. Gagal: pesan umum "Nama user atau password salah" (jangan beri tahu mana yang salah). Batasi percobaan login (throttle). Logout lewat POST + CSRF.

## Pendaftaran (peran calon_santri)
1. Menu Pendaftaran memeriksa **periode tersedia**: ada periode dengan `status_periode = 'dibuka'`. (Admin yang membuka/menutup; boleh hanya satu periode dibuka dalam satu waktu.)
2. Jika tidak tersedia: tampilkan "Pendaftaran sedang ditutup" dan tautan kembali ke beranda.
3. Jika pengguna sudah punya `pendaftar`: tampilkan halaman **status pendaftaran**, bukan formulir (tidak ada pendaftaran ganda, termasuk yang ditolak).
4. Jika belum: tampilkan formulir dengan kotak info periode (`nama_periode`, `tanggal_tutup`).
5. Field dan validasi (pesan di bawah kolom, input lama dipertahankan):
   - `nama_lengkap` wajib, maks 100; `tanggal_lahir` wajib, tanggal valid, sebelum hari ini; `alamat` wajib; `nama_wali` wajib, maks 100; `no_telepon` wajib, hanya angka (boleh awalan +62 atau 0), 10–15 digit.
6. Valid: simpan `pendaftar` dengan `status_daftar = 'diproses'`, `id_user` = pengguna login, `id_periode` = periode dibuka; arahkan ke status pendaftaran dengan pesan "Pendaftaran berhasil". Tidak valid: tampilkan pesan kesalahan agar data diperbaiki.

## Verifikasi pendaftar (admin)
- Daftar pendaftar: cari berdasarkan nama, filter status, paginasi. Halaman detail menampilkan data pendaftar.
- **Terima** (hanya jika `status_daftar = 'diproses'`), dalam satu transaksi database:
  1. `status_daftar = 'diterima'`;
  2. buat `tagihan` jenis `pendaftaran`, `nominal = config('pondok.biaya_pendaftaran')`, `status_tagihan = 'belum_dibayar'` (ini yang disebut "membuka pembayaran");
  3. **jangan** mengubah peran dan **jangan** membuat santri.
- **Tolak**: `status_daftar = 'ditolak'` saja; tanpa tagihan.

## Pembayaran (calon_santri dengan tagihan, dan santri)
Tombol **Bayar Sekarang** hanya aktif jika `status_tagihan = 'belum_dibayar'`.
1. Buat `pembayaran` untuk tagihan itu bila belum ada (`status_bayar = 'menunggu'`, `jumlah_bayar = nominal`). Satu tagihan hanya satu pembayaran.
2. Buat `transaksi` baru: `order_id` unik (contoh `ANH-{id_tagihan}-{timestamp}`, maks 50 karakter), `status_transaksi = 'menunggu'`. Jika sudah ada transaksi `menunggu` yang tokennya masih berlaku, boleh dipakai ulang.
3. Panggil Midtrans Snap (`Snap::getSnapToken`) dengan `transaction_details` (`order_id`, `gross_amount` bilangan bulat rupiah), `customer_details` (nama dari `nama_lengkap`, telepon dari `no_telepon`), dan `item_details`. Simpan `token`.
4. Kembalikan token ke browser; browser memanggil `snap.pay(token)`. Callback `onSuccess/onPending/onClose` hanya memuat ulang halaman status.
5. Midtrans memanggil webhook `POST /midtrans/notification` (**dikecualikan dari CSRF**, di `bootstrap/app.php` lewat `validateCsrfTokens(except: [...])`).

### Penanganan webhook (PembayaranService)
- Verifikasi `signature_key` = `sha512(order_id . status_code . gross_amount . SERVER_KEY)`; jika tidak cocok balas 403 dan jangan ubah data.
- Cari `transaksi` dari `order_id`; tidak ada → balas 404. Idempoten: jika transaksi sudah `berhasil`, abaikan notifikasi berikutnya (jangan menurunkan status).
- Petakan `transaction_status`: `settlement` atau (`capture` dengan `fraud_status = accept`) → **berhasil**; `pending` → menunggu; `deny`, `cancel`, `failure` → **gagal**; `expire` → **kedaluwarsa**. Simpan `payment_type` ke `transaksi.metode`.
- Semua perubahan di dalam satu `DB::transaction`:
  - **berhasil**: `transaksi` berhasil; `pembayaran` berhasil, `tanggal_bayar` = hari ini, `jumlah_bayar` = `gross_amount`; `tagihan` `lunas`. **Jika `jenis_tagihan = 'pendaftaran'`**: buat `santri` (`id_pendaftar`, `tanggal_masuk` hari ini, `status_santri = 'aktif'`) dan ubah `users.peran` menjadi `santri`. Inilah satu-satunya tempat peran berubah.
  - **gagal/kedaluwarsa**: `transaksi` dan `pembayaran` mengikuti status itu; `tagihan` tetap `belum_dibayar`. Saat pengguna menekan Bayar lagi, buat `transaksi` baru dan kembalikan `pembayaran` ke `menunggu`.
- Balas HTTP 200 setelah diproses.

## Tagihan syahriyah (santri)
Belum ada use case admin untuk membuat tagihan bulanan. **Asumsi sementara (konfirmasi dengan pemilik proyek sebelum dikerjakan):** perintah Artisan `tagihan:syahriyah` membuat satu tagihan `syahriyah` per bulan untuk setiap santri `aktif` (nominal `config('pondok.syahriyah')`), tidak membuat duplikat pada bulan yang sama, dan bisa dijadwalkan.

## Admin
- **Periode:** daftar periode; formulir tambah/ubah (nama, tanggal buka, tanggal tutup); tombol **Buka**/**Tutup** mengubah `status_periode`. Saat membuka satu periode, tutup periode lain.
- **Informasi pondok:** tambah, ubah, hapus; `id_user` = admin yang menyimpan, `diperbarui` = hari ini.
- **Dashboard:** jumlah total pendaftar, diproses, diterima, ditolak; lima pendaftar terbaru; lima pembayaran terbaru.
- **Data pembayaran:** hanya melihat (tabel + filter status/jenis tagihan). **Tidak ada** tombol verifikasi pembayaran; verifikasi otomatis lewat webhook.

## Keamanan (kebutuhan non-fungsional)
Password hash; CSRF aktif (kecuali webhook); validasi di server; Eloquent/query binding (tanpa SQL mentah dari input); otorisasi per peran dan per pemilik data; hanya HTTPS di produksi; halaman responsif (nyaman dibuka di ponsel); respons halaman wajar (<3 detik pada koneksi normal) dengan paginasi dan eager loading.
