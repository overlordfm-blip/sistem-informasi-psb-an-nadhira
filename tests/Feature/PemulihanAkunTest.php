<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PemulihanAkun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PemulihanAkunTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Carbon::setTestNow('2026-11-01 10:00:00', 'Asia/Jakarta');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_request_is_saved_privately_with_a_session_code_and_generic_response(): void
    {
        $this->post('/lupa-akun/permintaan', [
            'nama_lengkap' => 'Nama Rahasia Pengaju',
            'nama_wali' => 'Wali Rahasia Pengaju',
            'nama_user_input' => 'akun_yang_mungkin_ada',
        ])->assertRedirect('/lupa-akun/status')->assertSessionHas('sukses');

        $kode = session('pemulihan.kode');
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/', $kode);
        $berkas = Storage::disk('local')->files('pemulihan');
        $this->assertCount(1, $berkas);
        $data = json_decode(Storage::disk('local')->get($berkas[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($kode, $data['kode']);
        $this->assertSame('menunggu', $data['status']);
        $this->assertNull($data['id_user']);
        $this->assertSame('Nama Rahasia Pengaju', $data['nama_lengkap']);
        $this->assertStringNotContainsString('akun_yang_mungkin_ada', session('sukses'));
        $this->get('/lupa-akun/status')->assertOk()->assertSee('Status Permintaan')->assertDontSee('cocok dengan akun');
    }

    public function test_request_whatsapp_link_contains_encoded_code_and_form_values(): void
    {
        config([
            'pondok.whatsapp' => '6285188578976',
            'pondok.alamat' => 'Kalibeber, Mojotengah, Wonosobo, Jawa Tengah',
            'pondok.jam_layanan' => 'Senin–Sabtu, 08.00–15.00 WIB',
        ]);

        $this->get('/lupa-akun')
            ->assertOk()
            ->assertSee('Kalibeber, Mojotengah, Wonosobo, Jawa Tengah')
            ->assertSee('Senin–Sabtu, 08.00–15.00 WIB');

        $this->post('/lupa-akun/permintaan', [
            'nama_lengkap' => 'Siti Aminah',
            'nama_wali' => 'Bapak Aminah',
            'nama_user_input' => 'siti_aminah',
        ])->assertRedirect('/lupa-akun/status');

        $kode = session('pemulihan.kode');
        $response = $this->get('/lupa-akun/status');
        $response->assertOk()
            ->assertSee('https://wa.me/6285188578976?text=', false)
            ->assertSee(rawurlencode("Kode: {$kode}"), false)
            ->assertSee(rawurlencode('Nama lengkap: Siti Aminah'), false)
            ->assertSee(rawurlencode('Nama wali: Bapak Aminah'), false);
    }

    public function test_invalid_manual_code_is_rejected_without_setting_session_code(): void
    {
        $this->from('/lupa-akun/lanjut')->post('/lupa-akun/lanjut', ['kode' => 'AAAA0000'])
            ->assertRedirect('/lupa-akun/lanjut')
            ->assertSessionHasErrors('kode')
            ->assertSessionMissing('pemulihan.kode');
    }

    public function test_manual_code_form_can_be_opened_repeatedly(): void
    {
        foreach (range(1, 6) as $indeks) {
            $this->get('/lupa-akun/lanjut')->assertOk()->assertSee('Masukkan Kode Pemulihan');
        }
    }

    public function test_valid_manual_code_restores_session_access(): void
    {
        $permintaan = $this->buatPermintaan();

        $this->post('/lupa-akun/lanjut', ['kode' => $permintaan['kode']])
            ->assertRedirect('/lupa-akun/status');

        $this->assertSame($permintaan['kode'], session('pemulihan.kode'));
        $this->get('/lupa-akun/status')->assertOk()->assertSee($permintaan['kode']);
    }

    public function test_status_endpoint_returns_a_lightweight_status_for_session_code(): void
    {
        $this->post('/lupa-akun/permintaan', [
            'nama_lengkap' => 'Nama Uji Status',
            'nama_wali' => 'Wali Uji Status',
        ]);

        $this->getJson('/lupa-akun/status')
            ->assertOk()
            ->assertExactJson(['status' => 'menunggu']);
    }

    public function test_status_polling_reports_admin_opened_access(): void
    {
        $akun = $this->buatAkunSantri();
        $admin = $this->buatAkunAdmin();
        $this->post('/lupa-akun/permintaan', [
            'nama_lengkap' => 'Nama Uji Polling',
            'nama_wali' => 'Wali Uji Polling',
        ]);
        $kode = session('pemulihan.kode');

        app(PemulihanAkun::class)->buka($kode, $akun->id_user, $admin->id_user);

        $this->getJson('/lupa-akun/status')->assertOk()->assertExactJson(['status' => 'dibuka']);
    }

    public function test_status_polling_is_limited_to_thirty_requests_per_minute(): void
    {
        $this->post('/lupa-akun/permintaan', [
            'nama_lengkap' => 'Nama Uji Throttle Status',
            'nama_wali' => 'Wali Uji Throttle Status',
        ]);

        foreach (range(1, 30) as $indeks) {
            $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.42'])
                ->getJson('/lupa-akun/status')
                ->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.42'])
            ->getJson('/lupa-akun/status')
            ->assertTooManyRequests();
    }

    public function test_password_form_is_hidden_until_admin_opens_the_access(): void
    {
        $akun = $this->buatAkunSantri();
        $permintaan = $this->buatPermintaan();
        session(['pemulihan.kode' => $permintaan['kode']]);

        $this->get('/lupa-akun/status')
            ->assertOk()
            ->assertSee('Menunggu')
            ->assertDontSee('name="password_baru"', false);

        app(PemulihanAkun::class)->buka($permintaan['kode'], $akun->id_user, 987);

        $this->get('/lupa-akun/status')
            ->assertOk()
            ->assertSee('Dibuka')
            ->assertSee('name="password_baru"', false);
    }

    public function test_password_reset_changes_hash_once_without_logging_in_automatically(): void
    {
        Log::spy();
        $akun = $this->buatAkunSantri();
        $admin = $this->buatAkunAdmin();
        $permintaan = $this->buatPermintaan();
        session(['pemulihan.kode' => $permintaan['kode']]);
        app(PemulihanAkun::class)->buka($permintaan['kode'], $akun->id_user, $admin->id_user);

        $this->post('/lupa-akun/password', [
            'password_baru' => 'PasswordBaru456',
            'password_baru_confirmation' => 'PasswordBaru456',
        ])->assertRedirect('/login')->assertSessionHas('sukses')->assertSessionMissing('pemulihan.kode');

        $this->assertFalse(Auth::check());
        $this->assertTrue(Hash::check('PasswordBaru456', $akun->fresh()->password));
        $this->assertFalse(Hash::check('PasswordLama123', $akun->fresh()->password));
        $this->assertSame('selesai', app(PemulihanAkun::class)->ambil($permintaan['kode'])['status']);
        $this->assertStringNotContainsString('PasswordBaru456', json_encode(session()->all()));
        Log::shouldNotHaveReceived('info');

        $this->post('/lupa-akun/password', [
            'password_baru' => 'PasswordLain789',
            'password_baru_confirmation' => 'PasswordLain789',
        ])->assertRedirect('/lupa-akun/lanjut');
        $this->assertTrue(Hash::check('PasswordBaru456', $akun->fresh()->password));

        $this->post('/login', [
            'nama_user' => $akun->nama_user,
            'password' => 'PasswordBaru456',
        ])->assertRedirect('/tagihan');
    }

    public function test_opened_access_expires_after_thirty_minutes(): void
    {
        $akun = $this->buatAkunSantri();
        $admin = $this->buatAkunAdmin();
        $permintaan = $this->buatPermintaan();
        session(['pemulihan.kode' => $permintaan['kode']]);
        app(PemulihanAkun::class)->buka($permintaan['kode'], $akun->id_user, $admin->id_user);
        Carbon::setTestNow('2026-11-01 10:30:00');

        $this->get('/lupa-akun/status')
            ->assertOk()
            ->assertSee('Kedaluwarsa')
            ->assertDontSee('name="password_baru"', false);
        $this->post('/lupa-akun/password', [
            'password_baru' => 'PasswordBaru456',
            'password_baru_confirmation' => 'PasswordBaru456',
        ])->assertRedirect('/lupa-akun/status')->assertSessionHasErrors('kode');
        $this->assertTrue(Hash::check('PasswordLama123', $akun->fresh()->password));
    }

    public function test_request_response_does_not_reveal_whether_username_exists(): void
    {
        $akun = $this->buatAkunSantri();

        $this->post('/lupa-akun/permintaan', [
            'nama_lengkap' => 'Pemohon Dengan Akun',
            'nama_wali' => 'Wali Pemohon',
            'nama_user_input' => $akun->nama_user,
        ])->assertRedirect('/lupa-akun/status')->assertSessionHas('sukses', 'Permintaan pemulihan diterima. Admin akan memverifikasi identitas Anda.');

        $this->post('/lupa-akun/permintaan', [
            'nama_lengkap' => 'Pemohon Tanpa Akun',
            'nama_wali' => 'Wali Pemohon',
            'nama_user_input' => 'nama_user_tidak_ada',
        ])->assertRedirect('/lupa-akun/status')->assertSessionHas('sukses', 'Permintaan pemulihan diterima. Admin akan memverifikasi identitas Anda.');
    }

    public function test_requests_are_limited_to_five_per_hour_per_ip(): void
    {
        foreach (range(1, 5) as $indeks) {
            $this->post('/lupa-akun/permintaan', [
                'nama_lengkap' => 'Pemohon Throttle '.$indeks,
                'nama_wali' => 'Wali Throttle',
            ])->assertRedirect('/lupa-akun/status');
        }

        $this->post('/lupa-akun/permintaan', [
            'nama_lengkap' => 'Pemohon Throttle Terakhir',
            'nama_wali' => 'Wali Throttle',
        ])->assertTooManyRequests();
    }

    private function buatPermintaan(): array
    {
        return app(PemulihanAkun::class)->buatPermintaan([
            'nama_user_input' => null,
            'nama_lengkap' => 'Nama Pemohon Pemulihan',
            'nama_wali' => 'Wali Pemohon Pemulihan',
        ]);
    }

    private function buatAkunSantri(): User
    {
        return User::create([
            'nama_user' => 'pemulihan_santri_'.uniqid(),
            'password' => 'PasswordLama123',
            'peran' => User::PERAN_SANTRI,
        ]);
    }

    private function buatAkunAdmin(): User
    {
        return User::create([
            'nama_user' => 'pemulihan_admin_'.uniqid(),
            'password' => 'PasswordLama123',
            'peran' => User::PERAN_ADMIN,
        ]);
    }
}
