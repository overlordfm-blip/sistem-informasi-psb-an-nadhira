<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Publik / Tamu
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return view('welcome');
})->name('beranda');

Route::middleware('guest')->group(function () {
    Route::get('/registrasi', [AuthController::class, 'tampilRegistrasi'])->name('registrasi');
    Route::post('/registrasi', [AuthController::class, 'prosesRegistrasi'])->name('registrasi.proses');

    Route::get('/login', [AuthController::class, 'tampilLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'prosesLogin'])->name('login.proses');
});

/*
|--------------------------------------------------------------------------
| Rute Otentikasi (Semua Pengguna Terotentikasi)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Halaman Khusus Peran Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('peran:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');
    });

    /*
    |--------------------------------------------------------------------------
    | Halaman Khusus Peran Calon Santri
    |--------------------------------------------------------------------------
    */
    Route::middleware('peran:calon_santri')->group(function () {
        Route::get('/status-pendaftaran', function () {
            return view('status-pendaftaran');
        })->name('status-pendaftaran');
    });

    /*
    |--------------------------------------------------------------------------
    | Halaman Khusus Peran Santri
    |--------------------------------------------------------------------------
    */
    Route::middleware('peran:santri')->group(function () {
        Route::get('/tagihan', function () {
            return view('tagihan');
        })->name('tagihan');
    });
});
