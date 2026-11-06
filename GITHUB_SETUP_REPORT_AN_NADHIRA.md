# LAPORAN PERSIAPAN REPOSITORY GITHUB AN-NADHIRA

## A. Root repository

Status: PASS

- Root yang diperiksa adalah `D:/semester 6/kp/An-Nadhira Hosting` dan berisi `artisan`, `composer.json`, `app/`, `routes/`, serta `public/`.
- Root bukan `deployment-package/`; subfolder tersebut tetap dikecualikan.
- Root sudah merupakan repository Git valid. Pemeriksaan awal menunjukkan branch `master`, lima commit sebelumnya, dan tidak ada remote terdaftar.
- Repository tidak diinisialisasi ulang dan history lama tidak dihapus atau ditulis ulang.
- Karena repository sudah memiliki history, commit baru pada tugas ini adalah commit biasa di branch `main`, bukan initial commit.

## B. File yang dimasukkan ke Git

Index yang ditinjau mempertahankan source utama aplikasi dan file pengembangan yang diperlukan:

- `app/`, `bootstrap/`, `config/`, `database/`, `lang/`, `public/`, `resources/`, `routes/`
- `tests/` dan `phpunit.xml`
- `artisan`, `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.js`
- `.env.example`, `.gitignore`, `.gitattributes`, `.editorconfig`
- `public/build/manifest.json` dan 86 file aset hasil build Vite
- Dokumentasi proyek yang sudah dikelola di `docs/`
- `AGENTS.md` dipertahankan sebagai pengecualian karena berisi batasan domain dan aturan proyek yang berguna bagi pemelihara source code.
- `.github/skills/` dipertahankan sebagai panduan kerja repository.

`storage/` hanya mempertahankan placeholder `.gitignore` yang diperlukan. Pemeriksaan menunjukkan `storage/app/private/.gitignore` merupakan satu-satunya file private-storage yang masuk index.

## C. File/folder yang dikecualikan

Aturan `.gitignore` diperbarui untuk mengecualikan:

- `.env`, `.env.*` selain `.env.example`, file IDE lokal, `vendor/`, dan `node_modules/`
- Data di `storage/app/private/` dan `storage/app/public/`, cache/session/compiled views/testing/log, dengan placeholder `.gitignore` tetap dapat dilacak
- Database SQLite lokal, dump/backup dan arsip backup
- `karantina/`, `deployment-package/`, `.agents/`, `paket_brief_antigravity/`, `CLAUDE.md`, serta laporan audit lokal yang bukan bagian source repository
- File hasil eksperimen `.txt` di root

Folder/file `.agents/`, `paket_brief_antigravity/`, dan `CLAUDE.md` yang sebelumnya sudah tracked dikeluarkan dari index tanpa menghapus salinan lokalnya. Penghapusan index tersebut tidak menghapusnya dari history lama.

## D. Pemeriksaan keamanan

Status: NEEDS REVIEW sebelum push ke GitHub.

- `.env` tidak masuk index dan tidak ditemukan di history Git sebelumnya.
- File payload dalam `storage/app/private/` tidak ditemukan di history; tiga commit yang menyentuh path tersebut hanya berkaitan dengan placeholder. Saat ini payload lokal tidak masuk index.
- `.env.example` dipertahankan. Pemindaian pola untuk private key, format token GitHub/AWS, format key Midtrans/Google, serta assignment secret berisi nilai menghasilkan nol hit pada berkas kandidat dan hasil build.
- Tidak ditemukan file database lokal atau arsip backup pada index.
- Pemindaian manual menemukan credential contoh akun yang dikodekan pada seeder dan test fixture, termasuk fallback akun admin. Nilai credential tidak dicantumkan di laporan. String credential contoh tersebut sudah ada pada history lokal sebelumnya.
- Ini bukan bukti adanya credential production, namun seeder dapat membuat akun dengan nilai default jika dijalankan tanpa konfigurasi aman. Sebelum mengunggah history ke GitHub, pemilik proyek perlu meninjau seeder, memastikan nilai contoh belum pernah digunakan sebagai credential aktif, dan merotasi kredensial bila pernah digunakan.
- Karena credential contoh sudah berada dalam history, `.gitignore` atau penghapusan file pada commit terbaru saja tidak menghilangkannya dari history. History tidak ditulis ulang tanpa persetujuan. Push ditahan sampai pemilik proyek memutuskan tindakan yang disetujui.

## E. Hasil validasi

| Pemeriksaan | Hasil |
|---|---|
| `composer validate --no-check-publish --strict` | PASS — `composer.json` valid |
| `php artisan --version` | PASS — Laravel Framework 13.38.0 |
| `php artisan route:list --except-vendor` | PASS — 73 route terdaftar |
| `php artisan test` | PASS — 238 test, 1413 assertions; PHPUnit guard menargetkan database `_test` terpisah |
| `npm run build` | PASS — Vite build berhasil, manifest tersedia dan 86 aset dibuat |
| `public/build` | PASS — manifest tidak diabaikan Git dan file aset siap dimasukkan |
| `.env.example` | PASS — tidak berisi nilai secret terisi yang terdeteksi |
| Index Git | PASS — tidak ada `.env`, vendor, node_modules, data privat, backup, karantina, atau deployment package |
| Placeholder storage | PASS — placeholder diperlukan tetap dapat dilacak |

Tidak ada migration atau operasi database produksi yang dijalankan. Test suite memakai konfigurasi database berakhiran `_test` dan `RefreshDatabase`; nama tersebut berbeda dari database lokal aplikasi.

## F. Branch dan commit

- Branch awal adalah `master` dengan lima commit sebelumnya.
- Branch lokal disiapkan sebagai `main` tanpa membuang commit lama.
- Perubahan persiapan dicatat sebagai commit biasa setelah history yang sudah ada; bukan initial commit.
- Tidak ada commit yang dapat disebut initial karena repository sudah memiliki history.

## G. Status GitHub repository

Status: NOT CREATED

- Tidak ada remote `origin` sebelum tugas ini.
- GitHub CLI (`gh`) tidak terpasang, sehingga `gh auth status`, pemeriksaan akun, pengecekan konflik nama, dan pembuatan repository Private tidak dapat dilakukan.
- Tidak ada token atau credential GitHub yang diminta atau ditampilkan.
- Repository remote tidak dibuat, tidak ada push, dan tidak ada force push.

## H. URL repository

Belum tersedia karena repository GitHub Private belum dibuat.

## I. Status push

Push belum dilakukan. Selain autentikasi/CLI yang belum tersedia, push juga ditahan oleh temuan credential contoh yang sudah terdapat dalam history lama. Jangan membuat repository remote sebelum pemilik proyek menyetujui langkah penanganan credential/history.

## J. Temuan dan tindak lanjut

1. Tinjau dan amankan seeder akun contoh sebelum publikasi; pastikan akun/password contoh tidak pernah dipakai di luar database pengujian dan rotasi bila perlu.
2. Karena string contoh telah ada dalam history, minta keputusan pemilik proyek sebelum mempertimbangkan pembersihan history. Jangan melakukan rewrite atau force push tanpa persetujuan dan koordinasi semua kolaborator.
3. Pasang GitHub CLI dan autentikasi secara lokal melalui mekanisme resmi jika pemilik proyek menyetujui pembuatan remote. Jangan mengirim token lewat chat.
4. Setelah risiko di atas diselesaikan, buat repository Private bernama `sistem-informasi-psb-an-nadhira`, periksa repository tujuan, tambahkan remote, lalu push branch `main` tanpa force push.
5. Sebelum upload, ulangi pemeriksaan staged files dan pastikan tidak ada data pribadi di Git history yang akan dipush.

## Kesimpulan

Repository lokal sudah disiapkan dengan README, aturan ignore yang menjaga source/build namun mengecualikan secret dan data lokal, serta validasi yang lulus. Source dan test dipertahankan. Persiapan local commit dapat dilakukan tanpa mengubah history lama, tetapi repository GitHub Private belum dibuat dan push tidak dilakukan karena autentikasi CLI tidak tersedia dan terdapat temuan credential contoh dalam history yang memerlukan keputusan pemilik proyek.
