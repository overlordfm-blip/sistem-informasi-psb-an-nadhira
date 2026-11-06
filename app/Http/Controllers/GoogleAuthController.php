<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TautanGoogle;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    private const SCOPES = ['openid', 'email', 'profile'];

    public function mulaiLogin(Request $request): RedirectResponse
    {
        return $this->arahkanKeGoogle($request, 'login');
    }

    public function callbackGoogle(Request $request, TautanGoogle $tautanGoogle): RedirectResponse
    {
        $alur = $request->session()->pull('google_alur');

        return match ($alur) {
            'login' => $this->callbackLogin($request, $tautanGoogle),
            'tautkan' => Auth::check()
                ? $this->callbackTautkan($request, $tautanGoogle)
                : redirect()->route('login')->with('galat', 'Sesi login berakhir. Masuk kembali lalu tautkan Gmail.'),
            'pemulihan' => $this->callbackPemulihan($request, $tautanGoogle),
            default => redirect()->route('lupa-akun')->with('galat', 'Alur Google tidak valid. Silakan mulai kembali.'),
        };
    }

    private function callbackLogin(Request $request, TautanGoogle $tautanGoogle): RedirectResponse
    {
        if (Auth::check()) {
            $request->session()->regenerate();

            return $this->redirectBerdasarkanPeran($request->user()->peran);
        }

        try {
            $identitas = $this->identitasTerverifikasi();
            $tautan = $tautanGoogle->akunDariSub($identitas['sub']);
            if ($tautan === null) {
                return redirect()->route('login')->with('galat', 'Gmail ini belum tertaut ke akun. Gunakan nama user dan password atau tautkan Gmail setelah masuk.');
            }

            $akun = User::query()
                ->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI])
                ->find($tautan['id_user']);
            if ($akun === null) {
                return redirect()->route('login')->with('galat', 'Akun ini tidak dapat masuk melalui Gmail. Silakan hubungi admin.');
            }

            Auth::login($akun);
            $request->session()->regenerate();

            Log::info('Login Google berhasil', ['id_user' => $akun->id_user, 'waktu' => now()->toIso8601String()]);

            return $this->redirectBerdasarkanPeran($akun->peran);
        } catch (DomainException $exception) {
            return redirect()->route('login')->with('galat', $exception->getMessage());
        } catch (Throwable) {
            $this->catatPeristiwa('Login Google gagal');

            return redirect()->route('login')->with('galat', 'Masuk dengan Gmail gagal. Silakan coba kembali atau gunakan nama user dan password.');
        }
    }

    public function mulaiTautkan(Request $request, TautanGoogle $tautanGoogle): View
    {
        $tautan = $tautanGoogle->tautanPengguna((int) $request->user()->id_user);

        return view('akun.google', ['tautanGoogle' => $tautan]);
    }

    public function mulaiTautkanOAuth(Request $request): RedirectResponse
    {
        return $this->arahkanKeGoogle($request, 'tautkan');
    }

    public function callbackTautkan(Request $request, TautanGoogle $tautanGoogle): RedirectResponse
    {
        $idUser = (int) $request->user()->id_user;

        try {
            $identitas = $this->identitasTerverifikasi();
            $tautanGoogle->tautkan(
                $idUser,
                $identitas['sub'],
                $tautanGoogle->emailSamar($identitas['email'])
            );

            Log::info('Akun Google ditautkan', ['id_user' => $idUser, 'waktu' => now()->toIso8601String()]);

            return redirect()->route('akun.google')->with('sukses', 'Gmail berhasil ditautkan ke akun Anda.');
        } catch (DomainException $exception) {
            return redirect()->route('akun.google')->with('galat', $exception->getMessage());
        } catch (Throwable) {
            $this->catatPeristiwa('Penautan Google gagal', $idUser);

            return redirect()->route('akun.google')->with('galat', 'Gmail tidak dapat ditautkan. Silakan coba kembali.');
        }
    }

    public function lepasTautan(Request $request, TautanGoogle $tautanGoogle): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Password wajib diisi untuk melepas tautan Gmail.',
            'password.current_password' => 'Password tidak benar.',
        ]);

        $idUser = (int) $request->user()->id_user;
        if (! $tautanGoogle->lepasTautan($idUser)) {
            return redirect()->route('akun.google')->with('galat', 'Tidak ada Gmail yang tertaut ke akun ini.');
        }

        Log::info('Akun Google dilepas oleh pengguna', ['id_user' => $idUser, 'waktu' => now()->toIso8601String()]);

        return redirect()->route('akun.google')->with('sukses', 'Tautan Gmail berhasil dilepas.');
    }

    public function mulaiPemulihan(Request $request): RedirectResponse
    {
        return $this->arahkanKeGoogle($request, 'pemulihan');
    }

    public function callbackPemulihan(Request $request, TautanGoogle $tautanGoogle): RedirectResponse
    {
        try {
            $identitas = $this->identitasTerverifikasi();
            $tautan = $tautanGoogle->akunDariSub($identitas['sub']);

            if ($tautan === null) {
                return redirect()->route('lupa-akun')->with('galat',
                    'Gmail ini belum tertaut ke akun mana pun. Gunakan jalur WhatsApp atau hubungi admin untuk bantuan.');
            }

            $akun = User::query()->find($tautan['id_user']);
            if ($akun === null || $akun->isAdmin()) {
                return redirect()->route('lupa-akun')->with('galat', 'Akun ini tidak dapat dipulihkan melalui Gmail. Hubungi admin.');
            }

            $request->session()->put('google_pemulihan', [
                'id_user' => $akun->id_user,
                'token' => Str::random(64),
                'dibuat_pada' => now()->timestamp,
                'berlaku_sampai' => now()->addMinutes(10)->timestamp,
            ]);

            Log::info('Pemulihan akun Google dimulai', ['id_user' => $akun->id_user, 'waktu' => now()->toIso8601String()]);

            return redirect()->route('lupa-akun.gmail');
        } catch (DomainException $exception) {
            return redirect()->route('lupa-akun')->with('galat', $exception->getMessage());
        } catch (Throwable) {
            $this->catatPeristiwa('Callback pemulihan Google gagal');

            return redirect()->route('lupa-akun')->with('galat', 'Pemulihan dengan Gmail gagal. Silakan coba kembali.');
        }
    }

    public function formPemulihan(Request $request): View|RedirectResponse
    {
        $akun = $this->akunPemulihanAktif($request);
        if ($akun === null) {
            $request->session()->forget('google_pemulihan');

            return redirect()->route('lupa-akun')->with('galat', 'Sesi pemulihan Gmail tidak tersedia atau sudah kedaluwarsa.');
        }

        return view('auth.lupa-akun-gmail', [
            'namaUser' => $akun->nama_user,
            'token' => $request->session()->get('google_pemulihan.token'),
        ]);
    }

    public function ubahPasswordPemulihan(Request $request): RedirectResponse
    {
        $dataSesi = $request->session()->get('google_pemulihan');
        $akun = $this->akunPemulihanAktif($request);

        if ($akun === null || ! is_array($dataSesi) || ! is_string($dataSesi['token'] ?? null)) {
            $request->session()->forget('google_pemulihan');

            return redirect()->route('lupa-akun')->with('galat', 'Sesi pemulihan Gmail tidak tersedia atau sudah kedaluwarsa.');
        }

        $data = $request->validate([
            'token' => ['required', 'string'],
            'password_baru' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min' => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        if (! hash_equals($dataSesi['token'], $data['token'])) {
            return redirect()->route('lupa-akun')->with('galat', 'Token pemulihan tidak valid atau sudah digunakan.');
        }

        $request->session()->forget('google_pemulihan');

        try {
            DB::transaction(function () use ($akun, $data): void {
                $akunTerkunci = User::query()->lockForUpdate()->findOrFail($akun->id_user);
                if ($akunTerkunci->isAdmin()) {
                    throw new DomainException('Akun admin tidak dapat dipulihkan melalui Gmail.');
                }

                $akunTerkunci->password = Hash::make($data['password_baru']);
                $akunTerkunci->save();
            });
        } catch (DomainException $exception) {
            return redirect()->route('lupa-akun')->with('galat', $exception->getMessage());
        }

        Log::info('Password akun dipulihkan melalui Google', ['id_user' => $akun->id_user, 'waktu' => now()->toIso8601String()]);

        return redirect()->route('login')->with('sukses', 'Password berhasil diubah. Silakan masuk menggunakan nama user dan password baru.');
    }

    public function lepasTautanAdmin(Request $request, int $id, TautanGoogle $tautanGoogle): RedirectResponse
    {
        $data = $request->validate([
            'identitas_terverifikasi' => ['accepted'],
        ], [
            'identitas_terverifikasi.accepted' => 'Anda wajib mengonfirmasi bahwa identitas pengguna sudah diverifikasi.',
        ]);
        unset($data);

        $akun = User::query()
            ->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI])
            ->findOrFail($id);

        if (! $tautanGoogle->lepasTautan((int) $akun->id_user)) {
            return back()->with('galat', 'Akun ini tidak memiliki Gmail tertaut.');
        }

        Log::warning('Tautan Gmail dilepas oleh admin', [
            'admin_id' => $request->user()->id_user,
            'id_user' => $akun->id_user,
            'waktu' => now()->toIso8601String(),
        ]);

        return back()->with('sukses', 'Tautan Gmail berhasil dilepas untuk akun '.$akun->nama_user.'.');
    }

    private function arahkanKeGoogle(Request $request, string $alur): RedirectResponse
    {
        $request->session()->put('google_alur', $alur);

        return Socialite::driver('google')->scopes(self::SCOPES)->redirect();
    }

    private function redirectBerdasarkanPeran(string $peran): RedirectResponse
    {
        return match ($peran) {
            User::PERAN_CALON_SANTRI => redirect()->route('status-pendaftaran'),
            User::PERAN_SANTRI => redirect()->route('tagihan'),
            default => redirect()->route('login')->with('galat', 'Akun ini tidak dapat masuk melalui Gmail.'),
        };
    }

    /** @return array{sub: string, email: string} */
    private function identitasTerverifikasi(): array
    {
        $googleUser = Socialite::driver('google')->user();
        $klaim = $googleUser->getRaw();
        $sub = $klaim['sub'] ?? null;
        $email = $klaim['email'] ?? null;
        $emailVerified = ($klaim['email_verified'] ?? false) === true
            || ($klaim['email_verified'] ?? null) === 'true';
        $domainDiizinkan = is_string($email)
            && strrpos($email, '@') !== false
            && strtolower(substr(strrchr($email, '@'), 1)) === strtolower((string) config('pondok.gmail_domain', 'gmail.com'));

        if (! is_string($sub) || $sub === '' || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ! $emailVerified || ! $domainDiizinkan) {
            throw new DomainException('Gunakan Gmail yang valid dan sudah terverifikasi.');
        }

        return ['sub' => $sub, 'email' => $email];
    }

    private function akunPemulihanAktif(Request $request): ?User
    {
        $dataSesi = $request->session()->get('google_pemulihan');
        if (! is_array($dataSesi) || (int) ($dataSesi['berlaku_sampai'] ?? 0) <= now()->timestamp) {
            return null;
        }

        return User::query()
            ->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI])
            ->find($dataSesi['id_user'] ?? null);
    }

    private function catatPeristiwa(string $peristiwa, ?int $idUser = null): void
    {
        Log::warning($peristiwa, array_filter([
            'id_user' => $idUser,
            'waktu' => now()->toIso8601String(),
        ], static fn (mixed $nilai): bool => $nilai !== null));
    }
}
