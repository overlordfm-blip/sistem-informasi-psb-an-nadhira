<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AkunController;
use App\Http\Controllers\Admin\AkunTanpaPendaftaranController;
use App\Http\Controllers\Admin\InformasiPondokController;
use App\Http\Controllers\Admin\PembayaranAdminController;
use App\Http\Controllers\Admin\PemulihanController;
use App\Http\Controllers\Admin\PendaftarController;
use App\Http\Controllers\Admin\PengaturanTesController;
use App\Http\Controllers\Admin\PeriodePendaftaranController;
use App\Http\Controllers\Admin\TagihanAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LupaAkunController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\TagihanController;
use App\Http\Controllers\TesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Publik / Tamu
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->middleware('sinkronkan.periode')->name('beranda');

Route::get('/lupa-akun', [LupaAkunController::class, 'index'])->name('lupa-akun');
Route::get('/auth/google/login', [GoogleAuthController::class, 'mulaiLogin'])
    ->middleware(['guest', 'throttle:10,1'])->name('auth.google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callbackGoogle'])
    ->middleware('throttle:10,1')->name('auth.google.callback');
Route::get('/auth/google/pemulihan', [GoogleAuthController::class, 'mulaiPemulihan'])
    ->middleware('throttle:10,1')->name('auth.google.pemulihan');
Route::get('/lupa-akun/gmail', [GoogleAuthController::class, 'formPemulihan'])
    ->middleware('throttle:10,1')->name('lupa-akun.gmail');
Route::post('/lupa-akun/gmail', [GoogleAuthController::class, 'ubahPasswordPemulihan'])
    ->middleware('throttle:10,1')->name('lupa-akun.gmail.ubah');
Route::post('/lupa-akun/permintaan', [LupaAkunController::class, 'permintaan'])
    ->middleware('throttle:5,60')
    ->name('lupa-akun.permintaan');
Route::get('/lupa-akun/status', [LupaAkunController::class, 'status'])
    ->middleware('throttle:30,1')
    ->name('lupa-akun.status');
Route::get('/lupa-akun/lanjut', [LupaAkunController::class, 'formLanjut'])
    ->name('lupa-akun.lanjut');
Route::post('/lupa-akun/lanjut', [LupaAkunController::class, 'lanjut'])
    ->middleware('throttle:5,1')
    ->name('lupa-akun.lanjut.proses');
Route::post('/lupa-akun/password', [LupaAkunController::class, 'ubahPassword'])
    ->middleware('throttle:5,1')
    ->name('lupa-akun.password');

Route::middleware('guest')->group(function () {
    Route::get('/registrasi', [AuthController::class, 'tampilRegistrasi'])->name('registrasi');
    Route::post('/registrasi', [AuthController::class, 'prosesRegistrasi'])->name('registrasi.proses');

    Route::get('/login', [AuthController::class, 'tampilLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'prosesLogin'])->name('login.proses');
});

/*
|--------------------------------------------------------------------------
| Webhook Midtrans — Publik (dikecualikan dari CSRF di bootstrap/app.php)
|--------------------------------------------------------------------------
*/
Route::post('/midtrans/notification', [MidtransWebhookController::class, 'terima'])
    ->name('midtrans.notification');

/*
|--------------------------------------------------------------------------
| Rute Otentikasi (Semua Pengguna Terotentikasi)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/akun/password', [PasswordController::class, 'edit'])->name('akun.password');
    Route::post('/akun/password', [PasswordController::class, 'update'])->name('akun.password.update');
    Route::middleware('peran:calon_santri,santri')->group(function () {
        Route::get('/akun/google', [GoogleAuthController::class, 'mulaiTautkan'])
            ->middleware('throttle:10,1')->name('akun.google');
        Route::get('/akun/google/mulai', [GoogleAuthController::class, 'mulaiTautkanOAuth'])
            ->middleware('throttle:10,1')->name('akun.google.mulai');
        Route::delete('/akun/google', [GoogleAuthController::class, 'lepasTautan'])
            ->middleware('throttle:10,1')->name('akun.google.destroy.self');
    });

    /*
    |--------------------------------------------------------------------------
    | Halaman Khusus Peran Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware(['peran:admin', 'sinkronkan.periode'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/akun', [AkunController::class, 'index'])->name('akun.index');
        Route::delete('/akun/{id}/google', [GoogleAuthController::class, 'lepasTautanAdmin'])
            ->middleware('throttle:10,1')->name('akun.google.destroy');
        Route::post('/akun/{id}/pemulihan', [AkunController::class, 'bukaAksesOffline'])
            ->middleware('throttle:10,1')
            ->name('akun.pemulihan');
        Route::get('/pemulihan', [PemulihanController::class, 'index'])->name('pemulihan.index');
        Route::get('/pemulihan/{kode}', [PemulihanController::class, 'show'])->name('pemulihan.show');
        Route::post('/pemulihan/{kode}/buka', [PemulihanController::class, 'buka'])
            ->middleware('throttle:10,1')
            ->name('pemulihan.buka');
        Route::post('/pemulihan/{kode}/tolak', [PemulihanController::class, 'tolak'])
            ->middleware('throttle:10,1')
            ->name('pemulihan.tolak');

        // Data Pembayaran — hanya-baca (tidak ada POST/PUT/DELETE)
        Route::get('/pembayaran', [PembayaranAdminController::class, 'index'])->name('pembayaran.index');

        // Akun calon santri tanpa pendaftaran
        Route::get('/akun-tanpa-pendaftaran', [AkunTanpaPendaftaranController::class, 'index'])->name('akun-tanpa-pendaftaran.index');
        Route::delete('/akun-tanpa-pendaftaran/{id}', [AkunTanpaPendaftaranController::class, 'destroy'])->name('akun-tanpa-pendaftaran.destroy');

        // Kelola tagihan
        Route::get('/tagihan', [TagihanAdminController::class, 'index'])->name('tagihan.index');
        Route::get('/tagihan/syahriyah', [TagihanAdminController::class, 'createSyahriyah'])->name('tagihan.syahriyah');
        Route::post('/tagihan/syahriyah', [TagihanAdminController::class, 'storeSyahriyah'])->name('tagihan.syahriyah.store');
        Route::delete('/tagihan/{id}', [TagihanAdminController::class, 'destroy'])->name('tagihan.destroy');

        // Periode Pendaftaran
        Route::get('/periode', [PeriodePendaftaranController::class, 'index'])->name('periode.index');
        Route::get('/periode/tambah', [PeriodePendaftaranController::class, 'create'])->name('periode.create');
        Route::get('/periode/create', [PeriodePendaftaranController::class, 'create']);
        Route::post('/periode', [PeriodePendaftaranController::class, 'store'])->name('periode.store');
        Route::get('/periode/{periode}/ubah', [PeriodePendaftaranController::class, 'edit'])->name('periode.edit');
        Route::get('/periode/{periode}/edit', [PeriodePendaftaranController::class, 'edit']);
        Route::put('/periode/{periode}', [PeriodePendaftaranController::class, 'update'])->name('periode.update');
        Route::delete('/periode/{periode}', [PeriodePendaftaranController::class, 'destroy'])->name('periode.destroy');
        Route::post('/periode/{periode}/toggle-status', [PeriodePendaftaranController::class, 'toggleStatus'])->name('periode.toggle-status');
        Route::post('/periode/{periode}/buka', [PeriodePendaftaranController::class, 'buka'])->name('periode.buka');
        Route::post('/periode/{periode}/tutup', [PeriodePendaftaranController::class, 'tutup'])->name('periode.tutup');

        // Kelola Informasi Pondok
        Route::get('/informasi', [InformasiPondokController::class, 'index'])->name('informasi.index');
        Route::get('/informasi/tambah', [InformasiPondokController::class, 'create'])->name('informasi.create');
        Route::get('/informasi/create', [InformasiPondokController::class, 'create']);
        Route::post('/informasi', [InformasiPondokController::class, 'store'])->name('informasi.store');
        Route::get('/informasi/{informasi}/ubah', [InformasiPondokController::class, 'edit'])->name('informasi.edit');
        Route::get('/informasi/{informasi}/edit', [InformasiPondokController::class, 'edit']);
        Route::put('/informasi/{informasi}', [InformasiPondokController::class, 'update'])->name('informasi.update');
        Route::delete('/informasi/{informasi}', [InformasiPondokController::class, 'destroy'])->name('informasi.destroy');

        // Data Pendaftar & Verifikasi
        Route::get('/pendaftar', [PendaftarController::class, 'index'])->name('pendaftar.index');
        Route::get('/pendaftar/{id}', [PendaftarController::class, 'show'])->name('pendaftar.show');
        Route::post('/pendaftar/{id}/tes', [PendaftarController::class, 'simpanHasilTes'])->name('pendaftar.tes');
        Route::post('/pendaftar/{id}/terima', [PendaftarController::class, 'terima'])->name('pendaftar.terima');
        Route::post('/pendaftar/{id}/tolak', [PendaftarController::class, 'tolak'])->name('pendaftar.tolak');

        Route::get('/pengaturan-tes', [PengaturanTesController::class, 'index'])->name('pengaturan-tes');
        Route::post('/pengaturan-tes', [PengaturanTesController::class, 'simpan'])->name('pengaturan-tes.simpan');
        Route::post('/pengaturan-tes/jadwal-online', [PengaturanTesController::class, 'jadwalkanOnline'])
            ->name('pengaturan-tes.jadwal-online');
    });

    /*
    |--------------------------------------------------------------------------
    | Halaman Khusus Peran Calon Santri
    |--------------------------------------------------------------------------
    */
    Route::middleware(['peran:calon_santri', 'sinkronkan.periode'])->group(function () {
        Route::get('/tes', [TesController::class, 'index'])->name('tes');
        Route::post('/tes', [TesController::class, 'pilih'])->name('tes.pilih');
        Route::get('/pendaftaran', [PendaftaranController::class, 'tampilFormulir'])->name('pendaftaran');
        Route::post('/pendaftaran', [PendaftaranController::class, 'prosesPendaftaran'])->name('pendaftaran.proses');
        Route::get('/status-pendaftaran', [PendaftaranController::class, 'statusPendaftaran'])->name('status-pendaftaran');
    });

    /*
    |--------------------------------------------------------------------------
    | Tagihan — Calon Santri (punya tagihan pendaftaran) dan Santri
    |--------------------------------------------------------------------------
    */
    Route::middleware('peran:calon_santri,santri')->group(function () {
        Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan');
        Route::post('/tagihan/{id}/bayar', [TagihanController::class, 'bayar'])->name('tagihan.bayar');
        Route::get('/tagihan/{id}/status', [TagihanController::class, 'status'])
            ->middleware('throttle:60,1')
            ->name('tagihan.status');
    });
});
