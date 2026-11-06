# Langkah Uji Manual End-to-End

Panduan ini memakai data contoh lokal dan Midtrans **sandbox**. Jangan masukkan kunci server, kunci klien, password admin, atau data pembayaran nyata ke dokumen, screenshot, maupun Git. Jangan memakai perintah yang menghapus basis data.

## Persiapan

1. Pastikan aplikasi dan MySQL lokal berjalan, migrasi yang tersedia sudah diterapkan, dan data contoh sudah disiapkan.
2. Pastikan periode pendaftaran tersedia dan konfigurasi Midtrans memakai kredensial sandbox dari `.env`. Untuk webhook dari sandbox, gunakan URL tunnel sementara yang diarahkan ke endpoint lokal `/midtrans/notification`.
3. Login admin menggunakan akun admin lokal. Password dibaca dari konfigurasi lokal `ADMIN_PASSWORD`; jangan menuliskan nilainya di laporan.
4. Gunakan instrumen uji sandbox yang disediakan Midtrans. Jangan memakai kartu, rekening, atau kredensial pembayaran sungguhan.

## Registrasi hingga Menjadi Santri

1. Pada `/registrasi`, buat akun contoh `uji_calon_01` dengan password lokal sementara yang memenuhi aturan minimal 8 karakter.
2. Login sebagai `uji_calon_01`, pastikan diarahkan ke status pendaftaran. Jika belum ada periode aktif, login admin dan buka periode uji, misalnya `Gelombang Uji Fase 8`.
3. Kembali ke akun calon santri dan buka `/pendaftaran`. Isi data contoh: nama `Ahmad Fulan`, tanggal lahir `2010-05-15`, alamat `Kalibeber, Wonosobo`, wali `Bapak Fulan`, telepon `081234567890`. Kirim formulir.
4. Di sesi admin, buka `/admin/pendaftar`, temukan `Ahmad Fulan`, lalu pilih **Terima**. Pastikan status menjadi diterima, satu tagihan pendaftaran muncul, dan peran pengguna masih `calon_santri`.
5. Login kembali sebagai calon santri, buka `/tagihan`, dan pilih **Bayar Sekarang** untuk tagihan pendaftaran. Selesaikan pembayaran menggunakan instrumen sandbox.
6. Tunggu webhook terverifikasi atau biarkan halaman polling status menyelaraskan transaksi. Pastikan tagihan menjadi lunas, satu baris santri aktif tercatat, dan peran berubah menjadi `santri`. Nama yang tampil pada profil pengguna harus sama dengan nama pendaftar yang terlihat admin: `Ahmad Fulan`.

## Ditolak lalu Daftar Ulang

1. Buat akun uji terpisah, misalnya `uji_tolak_01`, dan ajukan pendaftaran pada periode uji lama menggunakan nama `Siti Contoh`.
2. Login admin dan tolak pendaftar tersebut. Pastikan tidak ada tagihan pendaftaran yang dibuat.
3. Buat atau buka periode yang berbeda, misalnya `Gelombang Uji Berikutnya`. Pastikan periode lama sudah ditutup.
4. Login sebagai `uji_tolak_01`. Pada status pendaftaran, pilih tautan **Daftar kembali pada Gelombang Uji Berikutnya**. Pastikan formulir terisi awal dari data lama dan dapat diedit.
5. Ubah satu atau lebih data, kirim formulir, lalu periksa bahwa baris pendaftar yang sama berpindah ke periode baru dan kembali berstatus `diproses`; jumlah baris pendaftar akun tersebut tetap satu.
6. Pastikan periode lama dapat dihapus hanya setelah tanggal tutup lewat, statusnya ditutup, dan tidak ada pendaftar yang masih merujuk ke sana.

## Tagihan Syahriyah

1. Login admin, buka **Kelola Tagihan** lalu **Buat Tagihan Syahriyah**. Nilai nominal awal diambil dari `config('pondok.syahriyah')`; uji pula nominal minimal Rp 1.000 dan penolakan nilai pecahan atau di bawah minimum.
2. Pilih **Semua santri aktif** dan buat batch. Catat jumlah tagihan dibuat/dilewati dan alasan per nama. Pastikan santri nonaktif dan santri yang masih punya tagihan syahriyah `belum_dibayar` dilewati.
3. Jalankan pembuatan untuk **Satu santri** aktif. Pastikan hanya santri pilihan yang menerima tagihan.
4. Untuk santri yang syahriyah terakhirnya lunas pada bulan kalender berjalan, pastikan pembuatan normal dilewati. Ulangi dengan mencentang **Tetap buat meski syahriyah bulan ini sudah dibayar** dan pastikan aturan override berlaku.
5. Login sebagai santri aktif yang memiliki tagihan syahriyah. Pastikan tagihan terbaru tampil lebih dahulu, lalu bayar melalui Snap sandbox.
6. Setelah pembayaran terverifikasi, pastikan tagihan syahriyah menjadi lunas, peran tetap `santri`, dan tidak ada baris santri tambahan. Periksa riwayat pembayaran beserta tanggalnya.
7. Kembali sebagai admin ke daftar tagihan. Pastikan tanggal bayar tampil dari data pembayaran, atau `-` bila belum ada tanggal. Uji hapus hanya untuk tagihan `belum_dibayar` tanpa baris pembayaran; tagihan yang sudah punya pembayaran harus ditolak.

## Pencatatan Hasil

Isi hasil aktual pada [PENGUJIAN_BLACKBOX.md](PENGUJIAN_BLACKBOX.md) setelah setiap skenario manual. Bulan tagihan syahriyah tidak disimpan dalam skema; pencegahan duplikat bulanan bergantung pada aturan aplikasi dan tanggal pembayaran, bukan penanda bulan khusus.