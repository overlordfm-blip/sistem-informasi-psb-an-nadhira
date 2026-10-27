<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        RateLimiter::clear('tes_throttle|127.0.0.1');
        parent::tearDown();
    }

    public function test_halaman_registrasi_dapat_diakses(): void
    {
        $response = $this->get('/registrasi');

        $response->assertStatus(200);
        $response->assertSee('REGISTRASI AKUN');
    }

    public function test_registrasi_berhasil_dan_tidak_otomatis_login(): void
    {
        $response = $this->post('/registrasi', [
            'nama_user' => 'santri_baru_01',
            'password' => 'passwordRahasia123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('sukses');

        // Pastikan akun tersimpan di database dengan peran calon_santri
        $this->assertDatabaseHas('users', [
            'nama_user' => 'santri_baru_01',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        // Pastikan registrasi TIDAK otomatis login
        $this->assertFalse(Auth::check());
    }

    public function test_registrasi_nama_user_ganda_ditolak(): void
    {
        // nama_user 'admin' sudah ada dari seeder
        $response = $this->from('/registrasi')->post('/registrasi', [
            'nama_user' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/registrasi');
        $response->assertSessionHasErrors('nama_user');
    }

    public function test_registrasi_validasi_format_nama_user_dan_panjang_password(): void
    {
        // Karakter ilegal (spasi dan tanda seru)
        $responseIlegal = $this->from('/registrasi')->post('/registrasi', [
            'nama_user' => 'user salah!',
            'password' => 'password123',
        ]);
        $responseIlegal->assertRedirect('/registrasi');
        $responseIlegal->assertSessionHasErrors('nama_user');

        // Password kurang dari 8 karakter
        $responsePendek = $this->from('/registrasi')->post('/registrasi', [
            'nama_user' => 'user_valid',
            'password' => 'pendek',
        ]);
        $responsePendek->assertRedirect('/registrasi');
        $responsePendek->assertSessionHasErrors('password');
    }

    public function test_halaman_login_dapat_diakses(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('MASUK');
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
            'nama_user' => 'santri_uji_peran',
            'password' => 'santri123',
            'peran' => User::PERAN_SANTRI,
        ]);

        // Calon santri coba akses dashboard admin -> 403
        $this->actingAs($calonSantri)->get('/admin/dashboard')->assertStatus(403);

        // Calon santri coba akses tagihan santri -> 403
        $this->actingAs($calonSantri)->get('/tagihan')->assertStatus(403);

        // Admin coba akses status pendaftaran calon santri -> 403
        $this->actingAs($admin)->get('/status-pendaftaran')->assertStatus(403);

        // Santri coba akses dashboard admin -> 403
        $this->actingAs($santri)->get('/admin/dashboard')->assertStatus(403);

        // Santri coba akses status pendaftaran calon santri -> 403
        $this->actingAs($santri)->get('/status-pendaftaran')->assertStatus(403);
    }

    public function test_tamu_belum_login_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
        $this->get('/status-pendaftaran')->assertRedirect('/login');
        $this->get('/tagihan')->assertRedirect('/login');
    }
}
