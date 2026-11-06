<?php

namespace Tests\Feature;

use App\Models\InformasiPondok;
use App\Models\User;
use App\Services\TautanGoogle;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->bersihkanBatasRegistrasi();
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('tes_throttle|127.0.0.1');
        $this->bersihkanBatasRegistrasi();
        parent::tearDown();
    }

    private function bersihkanBatasRegistrasi(): void
    {
        $hashIp = hash('sha256', '127.0.0.1');
        RateLimiter::clear('registrasi:percobaan:'.$hashIp);
        RateLimiter::clear('registrasi:berhasil:'.$hashIp);
    }

    private function payloadRegistrasi(array $data = [], bool $tunggu = true): array
    {
        $form = $this->get('/registrasi');
        $payload = array_merge([
            'nama_user' => 'uji_registrasi_'.uniqid(),
            'password' => 'password123',
            'captcha_answer' => session('registrasi.captcha_jawaban'),
            'form_started_at' => $form->viewData('formStartedAt'),
            'form_nonce' => $form->viewData('formNonce'),
            'form_signature' => $form->viewData('formSignature'),
            'website' => '',
        ], $data);

        if ($tunggu) {
            $this->travel(3)->seconds();
        }

        return $payload;
    }

    public function test_halaman_registrasi_dapat_diakses(): void
    {
        $response = $this->get('/registrasi');

        $response->assertStatus(200);
        $response->assertSee('REGISTRASI AKUN');
        $response->assertSee('Gmail tidak wajib untuk membuat akun.');
        $response->assertDontSee('Lanjutkan dengan Gmail');
    }

    public function test_registrasi_berhasil_dan_tidak_otomatis_login(): void
    {
        $response = $this->post('/registrasi', $this->payloadRegistrasi([
            'nama_user' => 'santri_baru_01',
            'password' => 'passwordRahasia123',
        ]));

        $response->assertRedirect('/login');
        $response->assertSessionHas('sukses');

        // Pastikan akun tersimpan di database dengan peran calon_santri
        $this->assertDatabaseHas('users', [
            'nama_user' => 'santri_baru_01',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $akun = User::query()->where('nama_user', 'santri_baru_01')->firstOrFail();
        $this->assertNull(app(TautanGoogle::class)->tautanPengguna((int) $akun->id_user));
        $this->assertSame([], Storage::disk('local')->allFiles('google'));

        // Pastikan registrasi TIDAK otomatis login
        $this->assertFalse(Auth::check());
    }

    public function test_registrasi_nama_user_ganda_ditolak(): void
    {
        // nama_user 'admin' sudah ada dari seeder
        $response = $this->from('/registrasi')->post('/registrasi', $this->payloadRegistrasi([
            'nama_user' => 'admin',
            'password' => 'password123',
        ]));

        $response->assertRedirect('/registrasi');
        $response->assertSessionHasErrors('nama_user');
    }

    public function test_registrasi_validasi_format_nama_user_dan_panjang_password(): void
    {
        // Karakter ilegal (spasi dan tanda seru)
        $responseIlegal = $this->from('/registrasi')->post('/registrasi', $this->payloadRegistrasi([
            'nama_user' => 'user salah!',
            'password' => 'password123',
        ]));
        $responseIlegal->assertRedirect('/registrasi');
        $responseIlegal->assertSessionHasErrors('nama_user');

        // Password kurang dari 8 karakter
        $responsePendek = $this->from('/registrasi')->post('/registrasi', $this->payloadRegistrasi([
            'nama_user' => 'user_valid',
            'password' => 'pendek',
        ]));
        $responsePendek->assertRedirect('/registrasi');
        $responsePendek->assertSessionHasErrors('password');
    }

    public function test_batas_percobaan_registrasi_per_jam_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->from('/registrasi')->post('/registrasi', $this->payloadRegistrasi([
                'nama_user' => 'nama user tidak valid',
            ]))->assertSessionHasErrors('nama_user');
        }

        $this->from('/registrasi')->post('/registrasi', $this->payloadRegistrasi())
            ->assertSessionHasErrors('nama_user');
        $this->assertStringContainsString('Batas 5 percobaan registrasi per jam', session('errors')->first('nama_user'));
    }

    public function test_batas_sepuluh_registrasi_berhasil_per_hari_per_ip(): void
    {
        $this->travelTo(Carbon::parse('2026-10-29 08:00:00', 'Asia/Jakarta'));

        for ($i = 0; $i < 10; $i++) {
            $this->post('/registrasi', $this->payloadRegistrasi([
                'nama_user' => 'kuota_harian_'.$i,
            ]))->assertRedirect('/login')->assertSessionHas('sukses');

            if ($i < 9) {
                $this->travel(3601)->seconds();
            }
        }

        $this->post('/registrasi', $this->payloadRegistrasi([
            'nama_user' => 'kuota_harian_terakhir',
        ]))->assertSessionHasErrors('nama_user');
        $this->assertStringContainsString('Batas 10 registrasi berhasil per hari', session('errors')->first('nama_user'));
    }

    public function test_honeypot_menolak_registrasi(): void
    {
        $data = $this->payloadRegistrasi(['website' => 'diisi-bot']);

        $this->from('/registrasi')->post('/registrasi', $data)->assertSessionHasErrors('website');
        $this->assertDatabaseMissing('users', ['nama_user' => $data['nama_user']]);
    }

    public function test_token_waktu_menolak_pengiriman_di_bawah_tiga_detik(): void
    {
        $data = $this->payloadRegistrasi([], false);

        $this->from('/registrasi')->post('/registrasi', $data)->assertSessionHasErrors('form_token');
        $this->assertDatabaseMissing('users', ['nama_user' => $data['nama_user']]);
    }

    public function test_captcha_salah_menolak_registrasi_dan_mengganti_soal(): void
    {
        $data = $this->payloadRegistrasi();
        $soalAwal = session('registrasi.captcha_soal');
        $data['captcha_answer'] = (int) $data['captcha_answer'] + 1;

        $this->from('/registrasi')->post('/registrasi', $data)->assertSessionHasErrors('captcha_answer');
        $formBaru = $this->get('/registrasi');

        $this->assertNotSame($soalAwal, $formBaru->viewData('captchaSoal'));
        $this->assertDatabaseMissing('users', ['nama_user' => $data['nama_user']]);
    }

    public function test_captcha_benar_mengizinkan_registrasi(): void
    {
        $this->post('/registrasi', $this->payloadRegistrasi([
            'nama_user' => 'captcha_benar_01',
        ]))->assertRedirect('/login')->assertSessionHas('sukses');

        $this->assertDatabaseHas('users', ['nama_user' => 'captcha_benar_01']);
    }

    public function test_halaman_login_dapat_diakses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('MASUK');
    }

    public function test_beranda_menyembunyikan_alur_pendaftaran_hanya_untuk_santri(): void
    {
        $responseTamu = $this->get('/');

        $responseTamu->assertOk()
            ->assertSee('Alur Pendaftaran')
            ->assertSee('Lihat Alur');

        $santri = User::create([
            'nama_user' => 'santri_beranda_'.uniqid(),
            'password' => 'password123',
            'peran' => User::PERAN_SANTRI,
        ]);

        $responseSantri = $this->actingAs($santri)->get('/');

        $responseSantri->assertOk()
            ->assertDontSee('Alur Pendaftaran')
            ->assertDontSee('Lihat Alur')
            ->assertDontSee('#alur')
            ->assertDontSee('Pendaftaran dibuka')
            ->assertDontSee('Daftar Sekarang')
            ->assertSee('Lihat Tagihan');

        $responseTagihan = $this->actingAs($santri)->get('/tagihan');

        $responseTagihan->assertOk()
            ->assertSeeInOrder(['>Tagihan</a>', $santri->nama_user, 'Keluar']);
    }

    public function test_beranda_menampilkan_seluruh_informasi_dalam_karusel(): void
    {
        $admin = User::where('peran', User::PERAN_ADMIN)->firstOrFail();

        foreach (range(1, 4) as $nomor) {
            InformasiPondok::create([
                'id_user' => $admin->id_user,
                'judul' => 'Judul carousel uji '.$nomor,
                'isi' => 'Isi informasi carousel uji '.$nomor,
                'diperbarui' => now()->toDateString(),
            ]);
        }

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('aria-label="Informasi Pondok"', false)
            ->assertSee('overflow-x-auto', false)
            ->assertSee('snap-x', false);

        foreach (range(1, 4) as $nomor) {
            $response->assertSee('Judul carousel uji '.$nomor);
        }
    }

    public function test_login_berhasil_admin_diarahkan_ke_dashboard(): void
    {
        $response = $this->post('/login', [
            'nama_user' => 'admin',
            'password' => env('ADMIN_PASSWORD', 'admin123'),
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertTrue(Auth::check());
        $this->assertEquals(User::PERAN_ADMIN, Auth::user()->peran);
    }

    public function test_login_berhasil_calon_santri_diarahkan_ke_status_pendaftaran(): void
    {
        $response = $this->post('/login', [
            'nama_user' => 'calon_santri1',
            'password' => 'santri123',
        ]);

        $response->assertRedirect('/status-pendaftaran');
        $this->assertTrue(Auth::check());
        $this->assertEquals(User::PERAN_CALON_SANTRI, Auth::user()->peran);
    }

    public function test_login_berhasil_santri_diarahkan_ke_tagihan(): void
    {
        $santriUser = User::create([
            'nama_user' => 'santri_mukim',
            'password' => 'santri123',
            'peran' => User::PERAN_SANTRI,
        ]);

        $response = $this->post('/login', [
            'nama_user' => 'santri_mukim',
            'password' => 'santri123',
        ]);

        $response->assertRedirect('/tagihan');
        $this->assertTrue(Auth::check());
        $this->assertEquals(User::PERAN_SANTRI, Auth::user()->peran);
    }

    public function test_login_gagal_dengan_kredensial_salah(): void
    {
        $response = $this->from('/login')->post('/login', [
            'nama_user' => 'admin',
            'password' => 'passwordSalahTotal',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('nama_user');
        $this->assertFalse(Auth::check());
    }

    public function test_pembatasan_percobaan_login_throttle(): void
    {
        // Lakukan 5 kali percobaan gagal
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'nama_user' => 'tes_throttle',
                'password' => 'salah',
            ]);
        }

        // Percobaan ke-6 harus terblokir throttle
        $response = $this->from('/login')->post('/login', [
            'nama_user' => 'tes_throttle',
            'password' => 'salah',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('nama_user');
        $errorMessage = session('errors')->first('nama_user');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', $errorMessage);
    }

    public function test_logout_berhasil(): void
    {
        $user = User::where('nama_user', 'calon_santri1')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $response->assertSessionHas('sukses');
        $this->assertFalse(Auth::check());
    }

    public function test_akses_lintas_peran_menghasilkan_403(): void
    {
        $calonSantri = User::where('nama_user', 'calon_santri1')->first();
        $admin = User::where('nama_user', 'admin')->first();
        $santri = User::create([
            'nama_user' => 'santri_uji_peran_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_SANTRI,
        ]);

        // Calon santri coba akses dashboard admin -> 403
        $this->actingAs($calonSantri)->get('/admin/dashboard')->assertStatus(403);

        // Calon santri BOLEH akses tagihan (peran:calon_santri,santri) -> 200
        $this->actingAs($calonSantri)->get('/tagihan')->assertStatus(200);

        // Admin coba akses status pendaftaran calon santri -> 403
        $this->actingAs($admin)->get('/status-pendaftaran')->assertStatus(403);

        // Santri coba akses dashboard admin -> 403
        $this->actingAs($santri)->get('/admin/dashboard')->assertStatus(403);

        // Santri coba akses status pendaftaran calon santri -> 403
        $this->actingAs($santri)->get('/status-pendaftaran')->assertStatus(403);

        // Santri coba akses formulir pendaftaran -> 403
        $this->actingAs($santri)->get('/pendaftaran')->assertStatus(403);
    }

    public function test_tamu_belum_login_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/status-pendaftaran')->assertRedirect('/login');
        $this->get('/tagihan')->assertRedirect('/login');
    }
}
