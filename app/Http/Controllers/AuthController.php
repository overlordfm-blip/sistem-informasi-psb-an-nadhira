<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegistrasiRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman formulir registrasi.
     */
    public function tampilRegistrasi(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBerdasarkanPeran(Auth::user()->peran);
        }

        $soalSebelumnya = $request->session()->get('registrasi.captcha_soal');
        do {
            $angkaPertama = random_int(1, 9);
            $angkaKedua = random_int(1, 9);
            $captchaSoal = "{$angkaPertama} + {$angkaKedua}";
        } while ($captchaSoal === $soalSebelumnya);

        $request->session()->put('registrasi.captcha_soal', $captchaSoal);
        $request->session()->put('registrasi.captcha_jawaban', $angkaPertama + $angkaKedua);

        $formStartedAt = now()->timestamp;
        $formNonce = Str::random(48);
        $request->session()->put('registrasi.form_nonce', $formNonce);
        $formSignature = hash_hmac(
            'sha256',
            $formStartedAt.'|'.$formNonce,
            (string) config('app.key')
        );

        return view('auth.registrasi', compact('captchaSoal', 'formStartedAt', 'formNonce', 'formSignature'));
    }

    /**
     * Memproses pendaftaran calon santri baru.
     */
    public function prosesRegistrasi(RegistrasiRequest $request): RedirectResponse
    {
        $successKey = 'registrasi:berhasil:'.hash('sha256', (string) $request->ip());
        if (RateLimiter::tooManyAttempts($successKey, 10)) {
            return back()->withErrors([
                'nama_user' => 'Batas 10 registrasi berhasil per hari dari alamat IP ini telah tercapai.',
            ])->onlyInput('nama_user');
        }

        $akun = User::create([
            'nama_user' => $request->validated('nama_user'),
            'password' => $request->validated('password'),
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        Log::info('Akun calon santri dibuat', [
            'id_user' => $akun->id_user,
            'waktu' => now()->toIso8601String(),
        ]);
        $sampaiAkhirHari = max(1, (int) now('Asia/Jakarta')->diffInSeconds(now('Asia/Jakarta')->endOfDay()));
        RateLimiter::hit($successKey, $sampaiAkhirHari);

        return redirect()->route('login')->with(
            'sukses',
            'Registrasi berhasil! Silakan masuk dengan nama user dan password Anda.'
        );
    }

    /**
     * Menampilkan halaman formulir login.
     */
    public function tampilLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectBerdasarkanPeran(Auth::user()->peran);
        }

        return view('auth.login');
    }

    /**
     * Memproses otentikasi login pengguna.
     */
    public function prosesLogin(LoginRequest $request): RedirectResponse
    {
        $throttleKey = Str::transliterate(Str::lower($request->input('nama_user')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'nama_user' => "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$seconds} detik.",
            ])->onlyInput('nama_user');
        }

        if (! Auth::attempt([
            'nama_user' => $request->validated('nama_user'),
            'password' => $request->validated('password'),
        ])) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'nama_user' => 'Nama user atau password salah.',
            ])->onlyInput('nama_user');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return $this->redirectBerdasarkanPeran(Auth::user()->peran);
    }

    /**
     * Memproses keluar dari sistem (logout).
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('sukses', 'Anda telah berhasil keluar.');
    }

    /**
     * Mengarahkan pengguna sesuai dengan perannya.
     */
    protected function redirectBerdasarkanPeran(string $peran): RedirectResponse
    {
        return match ($peran) {
            User::PERAN_ADMIN => redirect()->intended(route('admin.dashboard')),
            User::PERAN_CALON_SANTRI => redirect()->intended(route('status-pendaftaran')),
            User::PERAN_SANTRI => redirect()->intended(route('tagihan')),
            default => redirect()->intended('/'),
        };
    }
}
