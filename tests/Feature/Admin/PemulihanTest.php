<?php

namespace Tests\Feature\Admin;

use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\User;
use App\Services\PemulihanAkun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PemulihanTest extends TestCase
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

    public function test_admin_lists_waiting_requests_and_shows_username_match_badge(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_pemulihan_daftar');
        $akunCocok = $this->buatAkun(User::PERAN_SANTRI, 'nama_user_cocok_pemulihan');
        $cocok = $this->buatPermintaan($akunCocok->nama_user);
        $tidakCocok = $this->buatPermintaan('nama_user_tidak_ada_pemulihan');

        $response = $this->actingAs($admin)->get('/admin/pemulihan');

        $response->assertOk()
            ->assertSee($cocok['kode'])
            ->assertSee($tidakCocok['kode'])
            ->assertSee($akunCocok->nama_user)
            ->assertSee('Cocok')
            ->assertSee('Tidak cocok')
            ->assertSee('Permintaan Pemulihan')
            ->assertSee('2 permintaan menunggu');
        $this->assertModelExists($akunCocok);
    }

    public function test_admin_can_search_waiting_requests_by_code(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_cari_kode');
        $terpilih = $this->buatPermintaan();
        $lainnya = $this->buatPermintaan();

        $this->actingAs($admin)->get('/admin/pemulihan?kode='.$terpilih['kode'])
            ->assertOk()
            ->assertSee($terpilih['kode'])
            ->assertDontSee($lainnya['kode']);
    }

    public function test_detail_defaults_to_matching_user_and_shows_phone_match_indicator(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_pemulihan_detail');
        $akun = $this->buatAkun(User::PERAN_CALON_SANTRI, 'user_detail_pemulihan');
        $this->buatPendaftar($akun, '+6281234567890', 'Calon Pemulihan Detail', 'Wali Pemulihan Detail');
        $permintaan = $this->buatPermintaan($akun->nama_user);

        $this->actingAs($admin)->get(route('admin.pemulihan.show', $permintaan['kode']))
            ->assertOk()
            ->assertSee('value="'.$akun->id_user.'" selected', false)
            ->assertSee('Calon Pemulihan Detail')
            ->assertSee('Wali Pemulihan Detail')
            ->assertSee('+6281234567890')
            ->assertSee('Cocok', false)
            ->assertSee('Tidak cocok', false);
    }

    public function test_admin_can_select_account_when_request_has_no_username_match(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_pilih_akun');
        $akun = $this->buatAkun(User::PERAN_SANTRI, 'santri_pilihan_manual');
        $this->buatPendaftar($akun, '081234567890', 'Santri Pilihan Manual', 'Wali Pilihan Manual');
        $permintaan = $this->buatPermintaan();

        $this->actingAs($admin)->get(route('admin.pemulihan.show', $permintaan['kode']))
            ->assertOk()
            ->assertSee("url.searchParams.set('id_user', pilihanAkun.value)", false)
            ->assertSee('value="'.$akun->id_user.'">'.$akun->nama_user.' - Santri Pilihan Manual', false);

        $response = $this->get(route('admin.pemulihan.show', [
            'kode' => $permintaan['kode'],
            'id_user' => $akun->id_user,
        ]));
        $response
            ->assertOk()
            ->assertSee('Santri Pilihan Manual')
            ->assertSee('081234567890')
            ->assertSee('Identitas pengguna sudah saya verifikasi');

        preg_match('/<button type="submit"([^>]*)>Buka Akses Pemulihan 30 Menit<\/button>/', $response->getContent(), $atributTombol);
        $this->assertArrayHasKey(1, $atributTombol);
        $this->assertDoesNotMatchRegularExpression('/(?:^|\s)disabled(?:\s|=|$)/', $atributTombol[1]);
    }

    public function test_admin_opens_access_for_verified_account_and_logs_only_ids_and_time(): void
    {
        Log::spy();
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_buka_pemulihan');
        $akun = $this->buatAkun(User::PERAN_SANTRI, 'santri_buka_pemulihan');
        $permintaan = $this->buatPermintaan('santri_buka_pemulihan');

        $this->actingAs($admin)->post(route('admin.pemulihan.buka', $permintaan['kode']), [
            'id_user' => $akun->id_user,
            'identitas_terverifikasi' => '1',
            'nomor_whatsapp' => '081234567890',
        ])->assertRedirect('/admin/pemulihan')->assertSessionHas('sukses');

        $dibuka = app(PemulihanAkun::class)->ambil($permintaan['kode']);
        $this->assertSame('dibuka', $dibuka['status']);
        $this->assertSame($akun->id_user, $dibuka['id_user']);
        $this->assertSame($admin->id_user, $dibuka['dibuka_oleh']);
        $this->assertSame('2026-11-01T10:30:00+07:00', $dibuka['berlaku_sampai']);
        Log::shouldHaveReceived('info')->once()->withArgs(function (string $message, array $context) use ($admin, $akun, $permintaan): bool {
            return $message === 'Akses pemulihan akun dibuka'
                && $context['admin_id'] === $admin->id_user
                && $context['akun_id'] === $akun->id_user
                && isset($context['waktu'])
                && ! str_contains(json_encode($context), $permintaan['kode']);
        });
    }

    public function test_admin_cannot_open_access_without_verification_or_for_admin_account(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_tidak_dituju');
        $calon = $this->buatAkun(User::PERAN_CALON_SANTRI, 'calon_tanpa_verifikasi');
        $permintaan = $this->buatPermintaan();

        $this->actingAs($admin)->from(route('admin.pemulihan.show', $permintaan['kode']))
            ->post(route('admin.pemulihan.buka', $permintaan['kode']), ['id_user' => $calon->id_user])
            ->assertRedirect(route('admin.pemulihan.show', $permintaan['kode']))
            ->assertSessionHasErrors('identitas_terverifikasi');
        $this->post(route('admin.pemulihan.buka', $permintaan['kode']), [
            'id_user' => $admin->id_user,
            'identitas_terverifikasi' => '1',
        ])->assertNotFound();
        $this->assertSame('menunggu', app(PemulihanAkun::class)->ambil($permintaan['kode'])['status']);
    }

    public function test_admin_can_reject_and_remove_a_waiting_request(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_tolak_pemulihan');
        $permintaan = $this->buatPermintaan();

        $this->actingAs($admin)->post(route('admin.pemulihan.tolak', $permintaan['kode']))
            ->assertRedirect('/admin/pemulihan')
            ->assertSessionHas('sukses');

        $this->assertNull(app(PemulihanAkun::class)->ambil($permintaan['kode']));
    }

    public function test_offline_access_displays_code_once_without_session_or_sensitive_log_data(): void
    {
        Log::spy();
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_offline_pemulihan');
        $akun = $this->buatAkun(User::PERAN_CALON_SANTRI, 'calon_offline_pemulihan');

        $response = $this->actingAs($admin)->post(route('admin.akun.pemulihan', $akun->id_user), [
            'identitas_terverifikasi' => '1',
        ]);

        $response->assertOk()->assertSee('Akses Pemulihan Dibuka');
        $permintaan = $response->viewData('permintaan');
        $this->assertSame('dibuka', $permintaan['status']);
        $this->assertSame($akun->id_user, $permintaan['id_user']);
        $this->assertNull($response->headers->get('Location'));
        $this->assertStringNotContainsString($permintaan['kode'], json_encode(session()->all()));
        Log::shouldHaveReceived('info')->once()->withArgs(function (string $message, array $context) use ($admin, $akun, $permintaan): bool {
            return $message === 'Akses pemulihan offline dibuka'
                && $context['admin_id'] === $admin->id_user
                && $context['akun_id'] === $akun->id_user
                && ! str_contains(json_encode($context), $permintaan['kode']);
        });
    }

    public function test_admin_list_lazily_deletes_waiting_requests_older_than_24_hours(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_cleanup_malas');
        Carbon::setTestNow('2026-10-31 09:59:59');
        $permintaan = $this->buatPermintaan();
        Carbon::setTestNow('2026-11-01 10:00:00');

        $this->actingAs($admin)->get('/admin/pemulihan')->assertOk()->assertDontSee($permintaan['kode']);
        $this->assertNull(app(PemulihanAkun::class)->ambil($permintaan['kode']));
    }

    public function test_cleanup_command_deletes_completed_and_expired_requests(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_cleanup_command');
        $akun = $this->buatAkun(User::PERAN_SANTRI, 'santri_cleanup_command');
        $permintaanSelesai = $this->buatPermintaan();
        app(PemulihanAkun::class)->buka($permintaanSelesai['kode'], $akun->id_user, $admin->id_user);
        app(PemulihanAkun::class)->selesaikan($permintaanSelesai['kode'], fn (): bool => true);

        $this->artisan('pemulihan:bersihkan')->assertExitCode(0);

        $this->assertNull(app(PemulihanAkun::class)->ambil($permintaanSelesai['kode']));
    }

    public function test_non_admin_roles_cannot_open_recovery_management(): void
    {
        foreach ([User::PERAN_CALON_SANTRI, User::PERAN_SANTRI] as $peran) {
            $akun = $this->buatAkun($peran, 'peran_dilarang_'.$peran);
            $this->actingAs($akun)->get('/admin/pemulihan')->assertForbidden();
        }
    }

    private function buatAkun(string $peran, string $namaUser): User
    {
        return User::create([
            'nama_user' => $namaUser.'_'.uniqid(),
            'password' => 'PasswordLama123',
            'peran' => $peran,
        ]);
    }

    private function buatPermintaan(?string $namaUser = null): array
    {
        return app(PemulihanAkun::class)->buatPermintaan([
            'nama_user_input' => $namaUser,
            'nama_lengkap' => 'Nama Pemohon Pemulihan',
            'nama_wali' => 'Wali Pemohon Pemulihan',
        ]);
    }

    private function buatPendaftar(User $akun, string $nomorTelepon, string $namaLengkap, string $namaWali): Pendaftar
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Uji Pemulihan '.uniqid(),
            'tanggal_buka' => '2026-01-01',
            'tanggal_tutup' => '2026-12-31',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        return Pendaftar::create([
            'id_user' => $akun->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => $namaLengkap,
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => $namaWali,
            'no_telepon' => $nomorTelepon,
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);
    }
}
