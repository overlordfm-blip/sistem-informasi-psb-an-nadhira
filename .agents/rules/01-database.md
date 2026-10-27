# Aturan Basis Data (MySQL)

Sumber: ERD dan Tabel 4.5–4.12 pada Bab IV laporan. Buat **tepat delapan tabel aplikasi** ini, urutan migrasi seperti di bawah (urutan sesuai ketergantungan kunci tamu). Tanpa `timestamps()`. Gunakan `restrictOnDelete()` pada semua kunci tamu.

Nama kolom kunci utama bukan `id`, sehingga di setiap model: `protected $primaryKey = 'id_xxx'; public $timestamps = false;` dan di migrasi `$table->id('id_xxx')`. Kunci tamu: `$table->foreignId('id_user')->constrained('users', 'id_user')->restrictOnDelete();`

## 1. users
| Kolom | Tipe | Aturan |
|---|---|---|
| id_user | BIGINT UNSIGNED | PK, auto increment |
| nama_user | VARCHAR(50) | UNIQUE |
| password | VARCHAR(255) | hash |
| peran | ENUM('admin','calon_santri','santri') | default `calon_santri` |

## 2. periode_pendaftaran
| Kolom | Tipe | Aturan |
|---|---|---|
| id_periode | BIGINT UNSIGNED | PK |
| nama_periode | VARCHAR(50) | |
| tanggal_buka | DATE | |
| tanggal_tutup | DATE | |
| status_periode | ENUM('dibuka','ditutup') | default `ditutup` |

## 3. pendaftar
| Kolom | Tipe | Aturan |
|---|---|---|
| id_pendaftar | BIGINT UNSIGNED | PK |
| id_user | BIGINT UNSIGNED | FK users.id_user, **UNIQUE** |
| id_periode | BIGINT UNSIGNED | FK periode_pendaftaran.id_periode |
| nama_lengkap | VARCHAR(100) | |
| tanggal_lahir | DATE | |
| alamat | TEXT | |
| nama_wali | VARCHAR(100) | |
| no_telepon | VARCHAR(15) | |
| status_daftar | ENUM('diproses','diterima','ditolak') | default `diproses` |

## 4. santri
| Kolom | Tipe | Aturan |
|---|---|---|
| id_santri | BIGINT UNSIGNED | PK |
| id_pendaftar | BIGINT UNSIGNED | FK pendaftar.id_pendaftar, **UNIQUE** |
| tanggal_masuk | DATE | |
| status_santri | ENUM('aktif','nonaktif') | default `aktif` |

## 5. tagihan
| Kolom | Tipe | Aturan |
|---|---|---|
| id_tagihan | BIGINT UNSIGNED | PK |
| id_pendaftar | BIGINT UNSIGNED | FK pendaftar.id_pendaftar |
| jenis_tagihan | ENUM('pendaftaran','syahriyah') | |
| nominal | DECIMAL(12,2) | cast `decimal:2` |
| status_tagihan | ENUM('belum_dibayar','lunas') | default `belum_dibayar` |

## 6. pembayaran
| Kolom | Tipe | Aturan |
|---|---|---|
| id_pembayaran | BIGINT UNSIGNED | PK |
| id_tagihan | BIGINT UNSIGNED | FK tagihan.id_tagihan, **UNIQUE** |
| tanggal_bayar | DATE | NULL sampai pembayaran berhasil |
| jumlah_bayar | DECIMAL(12,2) | diisi nominal tagihan saat dibuat, dikonfirmasi dari `gross_amount` notifikasi |
| status_bayar | ENUM('menunggu','berhasil','gagal','kedaluwarsa') | default `menunggu` |

## 7. transaksi
| Kolom | Tipe | Aturan |
|---|---|---|
| id_transaksi | BIGINT UNSIGNED | PK |
| id_pembayaran | BIGINT UNSIGNED | FK pembayaran.id_pembayaran |
| order_id | VARCHAR(50) | **UNIQUE**, dikirim ke Midtrans |
| token | VARCHAR(100) | Snap token |
| metode | VARCHAR(30) | NULL sampai diketahui dari notifikasi (`payment_type`) |
| status_transaksi | ENUM('menunggu','berhasil','gagal','kedaluwarsa') | default `menunggu` |

## 8. informasi_pondok
| Kolom | Tipe | Aturan |
|---|---|---|
| id_informasi | BIGINT UNSIGNED | PK |
| id_user | BIGINT UNSIGNED | FK users.id_user (admin pengelola) |
| judul | VARCHAR(150) | |
| isi | TEXT | |
| diperbarui | DATE | diisi tanggal hari ini setiap simpan/ubah |

## Relasi (kardinalitas wajib sama)
- PeriodePendaftaran 1 — 0..* Pendaftar
- User 1 — 0..1 Pendaftar (admin tidak punya pendaftar)
- Pendaftar 1 — 0..1 Santri
- Pendaftar 1 — 0..* Tagihan
- Tagihan 1 — 0..1 Pembayaran
- Pembayaran 1 — 1..* Transaksi (percobaan pembayaran tidak dibatasi; hanya satu yang boleh berstatus `berhasil`)
- User 1 — 0..* InformasiPondok

## Seeder (hanya untuk pengembangan)
- Satu akun admin: `nama_user` = `admin`, password dari `.env` (`ADMIN_PASSWORD`).
- Satu periode contoh berstatus `ditutup`.
- Tiga data informasi pondok contoh (profil, kegiatan, kontak).
- Beberapa akun calon santri contoh untuk uji (jangan dibawa ke produksi).

## Larangan
Jangan menambah tabel/kolom di luar daftar ini tanpa izin. Perubahan skema harus lewat migrasi baru, bukan mengubah migrasi lama setelah dijalankan.
