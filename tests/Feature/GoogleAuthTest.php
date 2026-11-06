<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TautanGoogle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    public function test_registration_without_google_creates_an_unlinked_account(): void
    {
        Storage::fake('local');
        $data = $this->payloadRegistrasi('tanpa_gmail');

        $this->post('/registrasi', $data)->assertRedirect('/login')->assertSessionHas('sukses');

        $akun = User::query()->where('nama_user', 'tanpa_gmail')->firstOrFail();
        $this->assertSame(User::PERAN_CALON_SANTRI, $akun->peran);
        $this->assertFalse(Auth::check());
        $this->assertNull(app(TautanGoogle::class)->tautanPengguna((int) $akun->id_user));
        $this->assertSame([], Storage::disk('local')->allFiles('google'));
    }

    public function test_login_page_offers_gmail_and_preserves_password_login(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Masuk dengan Gmail')
            ->assertSee(route('auth.google.login'), false)
            ->assertSee(route('login.proses'), false)
            ->assertSee('name="nama_user"', false)
            ->assertSee('name="password"', false);
    }

    public function test_linked_google_account_can_login_as_calon_santri_without_password(): void
    {
        Storage::fake('local');
        Log::spy();
        $akun = $this->buatAkun('login_google_calon', User::PERAN_CALON_SANTRI);
        app(TautanGoogle::class)->tautkan($akun->id_user, 'google-sub-login-calon', 'l***@gmail.com');
        $this->mockGoogleClaims(['sub' => 'google-sub-login-calon', 'email' => 'login@gmail.com', 'email_verified' => true]);
        $this->withSession(['google_alur' => 'login']);

        $this->get('/auth/google/callback')->assertRedirect('/status-pendaftaran');

        $this->assertAuthenticatedAs($akun);
        $this->assertNotNull(session()->getId());
        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($akun): bool {
            $serialized = json_encode([$message, $context]);

            return $message === 'Login Google berhasil'
                && $context['id_user'] === $akun->id_user
                && isset($context['waktu'])
                && ! str_contains($serialized, 'google-sub-login-calon')
                && ! str_contains($serialized, 'login@gmail.com');
        })->once();
    }

    public function test_linked_google_account_can_login_as_santri(): void
    {
        Storage::fake('local');
        $akun = $this->buatAkun('login_google_santri', User::PERAN_SANTRI);
        app(TautanGoogle::class)->tautkan($akun->id_user, 'google-sub-login-santri', 's***@gmail.com');
        $this->mockGoogleClaims(['sub' => 'google-sub-login-santri', 'email' => 'login.santri@gmail.com', 'email_verified' => true]);
        $this->withSession(['google_alur' => 'login']);

        $this->get('/auth/google/callback')->assertRedirect('/tagihan');

        $this->assertAuthenticatedAs($akun);
    }

    public function test_unlinked_google_login_is_rejected_without_authenticating(): void
    {
        Storage::fake('local');
        $this->mockGoogleClaims(['sub' => 'google-sub-login-unlinked', 'email' => 'not-linked@gmail.com', 'email_verified' => true]);
        $this->withSession(['google_alur' => 'login']);

        $this->get('/auth/google/callback')
            ->assertRedirect('/login')
            ->assertSessionHas('galat', 'Gmail ini belum tertaut ke akun. Gunakan nama user dan password atau tautkan Gmail setelah masuk.');

        $this->assertGuest();
    }

    public function test_admin_cannot_login_through_a_google_link(): void
    {
        Storage::fake('local');
        $admin = $this->buatAkun('admin_google_login', User::PERAN_ADMIN);
        app(TautanGoogle::class)->tautkan($admin->id_user, 'google-sub-login-admin', 'a***@gmail.com');
        $this->mockGoogleClaims(['sub' => 'google-sub-login-admin', 'email' => 'admin.login@gmail.com', 'email_verified' => true]);
        $this->withSession(['google_alur' => 'login']);

        $this->get('/auth/google/callback')
            ->assertRedirect('/login')
            ->assertSessionHas('galat');

        $this->assertGuest();
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        Storage::fake('local');
        $akun = $this->buatAkun('akun_email_belum_terverifikasi');
        $this->mockGoogleClaims(['sub' => 'google-unverified', 'email' => 'a@gmail.com', 'email_verified' => false]);
        $this->withSession(['google_alur' => 'tautkan']);

        $this->actingAs($akun)->get('/auth/google/callback')->assertRedirect('/akun/google')->assertSessionHas('galat');
        $this->assertNull(app(TautanGoogle::class)->tautanPengguna((int) $akun->id_user));
    }

    public function test_non_gmail_domain_is_rejected(): void
    {
        Storage::fake('local');
        $akun = $this->buatAkun('akun_domain_bukan_gmail');
        $this->mockGoogleClaims(['sub' => 'google-workspace', 'email' => 'user@example.org', 'email_verified' => true]);
        $this->withSession(['google_alur' => 'tautkan']);

        $this->actingAs($akun)->get('/auth/google/callback')->assertRedirect('/akun/google')->assertSessionHas('galat');
        $this->assertNull(app(TautanGoogle::class)->tautanPengguna((int) $akun->id_user));
    }

    public function test_existing_account_can_link_gmail_and_duplicate_link_is_refused(): void
    {
        Storage::fake('local');
        Log::spy();
        $akun = $this->buatAkun('akun_taut_gmail');
        $this->mockGoogleRedirectAndClaims(['sub' => 'google-sub-link', 'email' => 'pemilik@gmail.com', 'email_verified' => true]);

        $this->actingAs($akun)->get('/akun/google')->assertOk()
            ->assertSee('Gmail belum tertaut')
            ->assertSee(route('akun.google.mulai'), false);
        $this->get('/akun/google/mulai')->assertRedirect('https://accounts.google.test/authorize');
        $this->get('/auth/google/callback')->assertRedirect('/akun/google');
        $this->get('/akun/google')->assertOk()->assertSee('p***@gmail.com')->assertSee('Gmail tertaut');
        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($akun): bool {
            return $message === 'Akun Google ditautkan'
                && $context['id_user'] === $akun->id_user
                && isset($context['waktu'])
                && ! str_contains(json_encode($context), 'google-sub-link')
                && ! str_contains(json_encode($context), 'pemilik@gmail.com');
        })->once();

        $akunLain = $this->buatAkun('akun_taut_lain');
        $this->actingAs($akunLain)->withSession(['google_alur' => 'tautkan'])
            ->get('/auth/google/callback')->assertRedirect('/akun/google')->assertSessionHas('galat');

        $this->assertNull(app(TautanGoogle::class)->tautanPengguna((int) $akunLain->id_user));
    }

    public function test_user_can_unlink_only_with_current_password(): void
    {
        Storage::fake('local');
        $akun = $this->buatAkun('akun_lepas_gmail');
        app(TautanGoogle::class)->tautkan($akun->id_user, 'google-sub-unlink', 'u***@gmail.com');

        $this->actingAs($akun)->delete('/akun/google', ['password' => 'salah'])
            ->assertSessionHasErrors('password');
        $this->assertNotNull(app(TautanGoogle::class)->tautanPengguna((int) $akun->id_user));

        $this->delete('/akun/google', ['password' => 'PasswordLama123'])
            ->assertRedirect('/akun/google')
            ->assertSessionHas('sukses');

        $this->assertNull(app(TautanGoogle::class)->tautanPengguna((int) $akun->id_user));
        Storage::disk('local')->assertMissing('google/sub-'.hash('sha256', 'google-sub-unlink').'.json');
        Storage::disk('local')->assertMissing('google/user-'.$akun->id_user.'.json');
    }

    public function test_gmail_recovery_changes_password_once_without_logging_in(): void
    {
        Storage::fake('local');
        Log::spy();
        $akun = $this->buatAkun('akun_pulih_gmail');
        app(TautanGoogle::class)->tautkan($akun->id_user, 'google-sub-recovery', 'r***@gmail.com');
        $this->mockGoogleClaims(['sub' => 'google-sub-recovery', 'email' => 'rahasia@gmail.com', 'email_verified' => true]);
        $this->withSession(['google_alur' => 'pemulihan']);

        $this->get('/auth/google/callback')->assertRedirect('/lupa-akun/gmail');
        $token = session('google_pemulihan.token');
        $this->get('/lupa-akun/gmail')->assertOk()
            ->assertSee($akun->nama_user)
            ->assertDontSee('rahasia@gmail.com');

        $this->post('/lupa-akun/gmail', [
            'token' => $token,
            'password_baru' => 'PasswordBaru456',
            'password_baru_confirmation' => 'PasswordBaru456',
        ])->assertRedirect('/login')->assertSessionHas('sukses')->assertSessionMissing('google_pemulihan');

        $this->assertFalse(Auth::check());
        $this->assertTrue(Hash::check('PasswordBaru456', $akun->fresh()->password));
        $this->assertFalse(Hash::check('PasswordLama123', $akun->fresh()->password));
        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($akun, $token): bool {
            $serialized = json_encode([$message, $context]);

            return in_array($message, ['Pemulihan akun Google dimulai', 'Password akun dipulihkan melalui Google'], true)
                && $context['id_user'] === $akun->id_user
                && isset($context['waktu'])
                && ! str_contains($serialized, 'google-sub-recovery')
                && ! str_contains($serialized, 'rahasia@gmail.com')
                && ! str_contains($serialized, 'PasswordBaru456')
                && ! str_contains($serialized, $token);
        })->twice();
        $this->post('/lupa-akun/gmail', [
            'token' => $token,
            'password_baru' => 'PasswordBerulang456',
            'password_baru_confirmation' => 'PasswordBerulang456',
        ])->assertRedirect('/lupa-akun')->assertSessionHas('galat');
        $this->post('/login', ['nama_user' => $akun->nama_user, 'password' => 'PasswordLama123'])
            ->assertSessionHasErrors('nama_user');
        $this->post('/login', ['nama_user' => $akun->nama_user, 'password' => 'PasswordBaru456'])
            ->assertRedirect('/status-pendaftaran');
    }

    public function test_expired_gmail_recovery_token_cannot_change_password(): void
    {
        Storage::fake('local');
        $akun = $this->buatAkun('akun_pulih_kedaluwarsa');
        $this->withSession(['google_pemulihan' => [
            'id_user' => $akun->id_user,
            'token' => str_repeat('a', 64),
            'dibuat_pada' => now()->subMinutes(11)->timestamp,
            'berlaku_sampai' => now()->subSecond()->timestamp,
        ]]);

        $this->get('/lupa-akun/gmail')->assertRedirect('/lupa-akun')->assertSessionMissing('google_pemulihan');
        $this->post('/lupa-akun/gmail', [
            'token' => str_repeat('a', 64),
            'password_baru' => 'PasswordBaru456',
            'password_baru_confirmation' => 'PasswordBaru456',
        ])->assertRedirect('/lupa-akun');
        $this->assertTrue(Hash::check('PasswordLama123', $akun->fresh()->password));
    }

    public function test_unlinked_gmail_shows_offline_recovery_path(): void
    {
        Storage::fake('local');
        $this->mockGoogleClaims(['sub' => 'google-sub-not-linked', 'email' => 'belum@gmail.com', 'email_verified' => true]);
        $this->withSession(['google_alur' => 'pemulihan']);

        $this->get('/auth/google/callback')->assertRedirect('/lupa-akun')->assertSessionHas('galat');
        $this->get('/lupa-akun')->assertOk()->assertSee('Gmail ini belum tertaut ke akun mana pun')->assertSee('Pulihkan dengan Gmail');
    }

    public function test_admin_account_cannot_be_recovered_with_gmail(): void
    {
        Storage::fake('local');
        $admin = $this->buatAkun('admin_google', User::PERAN_ADMIN);
        app(TautanGoogle::class)->tautkan($admin->id_user, 'google-sub-admin', 'a***@gmail.com');
        $this->mockGoogleClaims(['sub' => 'google-sub-admin', 'email' => 'admin@gmail.com', 'email_verified' => true]);
        $this->withSession(['google_alur' => 'pemulihan']);

        $this->get('/auth/google/callback')->assertRedirect('/lupa-akun')->assertSessionHas('galat');
        $this->assertNull(session('google_pemulihan'));
    }

    public function test_admin_can_view_and_remove_a_link_after_identity_confirmation(): void
    {
        Storage::fake('local');
        Log::spy();
        $admin = $this->buatAkun('admin_google_kelola', User::PERAN_ADMIN);
        $akun = $this->buatAkun('akun_admin_lepas');
        app(TautanGoogle::class)->tautkan($akun->id_user, 'google-sub-admin-unlink', 'a***@gmail.com');

        $this->actingAs($admin)->get('/admin/akun')->assertOk()
            ->assertSee('Gmail tertaut')
            ->assertSee('Ya, a***@gmail.com')
            ->assertSee('Lepas tautan Gmail');

        $this->from('/admin/akun')->delete('/admin/akun/'.$akun->id_user.'/google')
            ->assertRedirect('/admin/akun')
            ->assertSessionHasErrors('identitas_terverifikasi');
        $this->assertNotNull(app(TautanGoogle::class)->tautanPengguna((int) $akun->id_user));

        $this->delete('/admin/akun/'.$akun->id_user.'/google', [
            'identitas_terverifikasi' => '1',
        ])->assertRedirect('/admin/akun')->assertSessionHas('sukses');

        $this->assertNull(app(TautanGoogle::class)->tautanPengguna((int) $akun->id_user));
        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($admin, $akun): bool {
            return $message === 'Tautan Gmail dilepas oleh admin'
                && $context['admin_id'] === $admin->id_user
                && $context['id_user'] === $akun->id_user
                && isset($context['waktu'])
                && ! str_contains(json_encode($context), 'gmail.com');
        })->once();
    }

    public function test_google_callback_is_throttled_to_ten_requests_per_minute(): void
    {
        Storage::fake('local');
        foreach (range(1, 10) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.77'])
                ->get('/auth/google/callback')->assertRedirect('/lupa-akun');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.77'])
            ->get('/auth/google/callback')->assertTooManyRequests();
    }

    public function test_google_redirect_uses_only_required_scopes_and_stateful_socialite_redirect(): void
    {
        Storage::fake('local');
        config([
            'services.google.client_id' => 'test-google-client',
            'services.google.client_secret' => 'test-google-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get('/auth/google/login');
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $response->assertRedirect();
        $scopes = explode(' ', $query['scope']);
        sort($scopes);
        $this->assertSame(['email', 'openid', 'profile'], $scopes);
        $this->assertNotEmpty($query['state']);
        $this->assertSame($query['state'], session('state'));
        $this->assertArrayNotHasKey('access_token', session()->all());
        $this->assertSame('login', session('google_alur'));
    }

    private function payloadRegistrasi(string $namaUser): array
    {
        $form = $this->get('/registrasi');
        $this->travel(3)->seconds();

        return [
            'nama_user' => $namaUser,
            'password' => 'PasswordBaru123',
            'captcha_answer' => session('registrasi.captcha_jawaban'),
            'form_started_at' => $form->viewData('formStartedAt'),
            'form_nonce' => $form->viewData('formNonce'),
            'form_signature' => $form->viewData('formSignature'),
            'website' => '',
        ];
    }

    private function mockGoogleClaims(array $claims): void
    {
        $googleUser = Mockery::mock();
        $googleUser->shouldReceive('getRaw')->once()->andReturn($claims);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function mockGoogleRedirectAndClaims(array $claims): void
    {
        $googleUser = Mockery::mock();
        $googleUser->shouldReceive('getRaw')->twice()->andReturn($claims);
        $provider = Mockery::mock();
        $provider->shouldReceive('scopes')->once()->with(['openid', 'email', 'profile'])->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect()->away('https://accounts.google.test/authorize'));
        $provider->shouldReceive('user')->twice()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function buatAkun(string $namaUser, string $peran = User::PERAN_CALON_SANTRI): User
    {
        return User::create([
            'nama_user' => $namaUser,
            'password' => 'PasswordLama123',
            'peran' => $peran,
        ]);
    }
}
