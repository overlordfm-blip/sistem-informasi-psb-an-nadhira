<?php

namespace Tests\Feature\Admin;

use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\User;
use App\Support\NomorTelepon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AkunTest extends TestCase
{
    public static function formatNomorTelepon(): array
    {
        return [
            'awalan nol dan tanda baca' => ['0812 (3456)-7890', '6281234567890'],
            'awalan plus enam dua' => ['+62 812-3456.7890', '6281234567890'],
            'awalan enam dua' => ['62 812-3456-7890', '6281234567890'],
            'digit tanpa awalan' => ['8123456789', '8123456789'],
            'terlalu pendek' => ['08123', null],
            'terlalu panjang' => ['08123456789012345', null],
            'karakter selain pemisah' => ['0812/3456/7890', null],
            'kosong' => ['', null],
            'null' => [null, null],
        ];
    }

    #[DataProvider('formatNomorTelepon')]
    public function test_normalizes_phone_formats_and_rejects_invalid_values(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, NomorTelepon::normalisasi($input));
    }

    public function test_public_account_help_uses_config_and_never_looks_up_accounts(): void
    {
        config([
            'pondok.whatsapp' => '+62 812-3456-7890',
            'pondok.alamat' => 'Jalan Pondok No. 7',
            'pondok.jam_layanan' => 'Senin-Jumat, 08.00-15.00',
        ]);

        $response = $this->get('/lupa-akun');

        $response->assertOk()
            ->assertSee('Lupa akun atau password?')
            ->assertSee('Jalan Pondok No. 7')
            ->assertSee('Senin-Jumat, 08.00-15.00')
            ->assertSee('action="'.route('lupa-akun.permintaan').'"', false)
            ->assertSee('name="nama_lengkap"', false)
            ->assertSee('name="nama_wali"', false)
            ->assertSee('name="nama_user_input"', false)
            ->assertSee('Kirim Permintaan Pemulihan')
            ->assertDontSee('akun_rahasia_yang_tidak_pernah_dicari');
    }

    public function test_account_help_hides_empty_contact_details_and_has_no_whatsapp_link(): void
    {
        config([
            'pondok.whatsapp' => '',
            'pondok.alamat' => '',
            'pondok.jam_layanan' => '',
        ]);

        $this->get('/lupa-akun')
            ->assertOk()
            ->assertDontSee('Alamat:')
            ->assertDontSee('Jam layanan:')
            ->assertDontSee('https://wa.me/');
    }

    public function test_admin_searches_users_names_and_phone_numbers_in_multiple_formats(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_cari');
        $calon = $this->buatAkun(User::PERAN_CALON_SANTRI, 'calon_akun_cari');
        $santri = $this->buatAkun(User::PERAN_SANTRI, 'santri_akun_cari');
        $this->buatPendaftar($calon, '081234567890', 'Siti Aminah', 'Bapak Aminah');
        $this->buatPendaftar($santri, '081299998888', 'Ahmad Fauzi', 'Ibu Fauzi');

        $this->actingAs($admin)->get('/admin/akun?cari=calon_akun_cari')
            ->assertOk()->assertSee('calon_akun_cari')->assertDontSee('santri_akun_cari');
        $this->get('/admin/akun?cari=Siti%20Aminah')
            ->assertOk()->assertSee('Siti Aminah')->assertDontSee('Ahmad Fauzi');
        $this->get('/admin/akun?cari=Bapak%20Aminah')
            ->assertOk()->assertSee('Bapak Aminah')->assertDontSee('Ibu Fauzi');
        $this->get('/admin/akun?cari=0812-3456-7890')
            ->assertOk()->assertSee('Siti Aminah')->assertDontSee('Ahmad Fauzi');
        $this->get('/admin/akun?cari=6281234567890')
            ->assertOk()->assertSee('Siti Aminah')->assertDontSee('Ahmad Fauzi');
        $this->get('/admin/akun?cari=%2B62%20812%203456%207890')
            ->assertOk()->assertSee('Siti Aminah')->assertDontSee('Ahmad Fauzi');
        $this->get('/admin/akun?peran=santri')
            ->assertOk()->assertSee('santri_akun_cari')->assertDontSee('calon_akun_cari');
    }

    public function test_account_list_excludes_admin_and_paginates(): void
    {
        $admin = $this->buatAkun(User::PERAN_ADMIN, 'admin_list_akun');
        foreach (range(1, 16) as $indeks) {
            $this->buatAkun(User::PERAN_CALON_SANTRI, 'calon_list_'.$indeks);
        }

        $this->actingAs($admin)->get('/admin/akun')
            ->assertOk()
            ->assertDontSee('admin_list_akun')
            ->assertSee('Buka akses pemulihan')
            ->assertSee('bg-primer px-4 py-2 font-semibold text-white', false)
            ->assertSee('page=2');
    }

    public function test_only_admin_can_access_account_management(): void
    {
        foreach ([User::PERAN_CALON_SANTRI, User::PERAN_SANTRI] as $peran) {
            $akun = $this->buatAkun($peran, 'akun_dilarang_'.$peran);
            $this->actingAs($akun)->get('/admin/akun')->assertForbidden();
        }
    }

    private function buatAkun(string $peran, string $namaUser, string $password = 'PasswordLama123'): User
    {
        return User::create([
            'nama_user' => $namaUser.'_'.uniqid(),
            'password' => $password,
            'peran' => $peran,
        ]);
    }

    private function buatPendaftar(User $akun, string $nomorTelepon, string $namaLengkap, string $namaWali): Pendaftar
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Uji Akun '.uniqid(),
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
