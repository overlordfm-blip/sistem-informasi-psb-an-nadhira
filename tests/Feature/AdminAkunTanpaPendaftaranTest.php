<?php

namespace Tests\Feature;

use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\User;
use Tests\TestCase;

class AdminAkunTanpaPendaftaranTest extends TestCase
{
    private User $admin;

    private User $calonSantri;

    private User $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('peran', User::PERAN_ADMIN)->first()
            ?? User::create(['nama_user' => 'admin_akun_tanpa_pendaftar', 'password' => 'pass1234', 'peran' => User::PERAN_ADMIN]);
        $this->calonSantri = User::create([
            'nama_user' => 'calon_bersih_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $this->santri = User::create([
            'nama_user' => 'santri_bersih_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_SANTRI,
        ]);
    }

    public function test_admin_melihat_hanya_akun_calon_santri_tanpa_pendaftar(): void
    {
        $periode = PeriodePendaftaran::first() ?? PeriodePendaftaran::create([
            'nama_periode' => 'Periode Akun Tanpa Pendaftar',
            'tanggal_buka' => '2026-01-01',
            'tanggal_tutup' => '2026-12-31',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);
        $calonDenganPendaftar = User::create([
            'nama_user' => 'calon_punya_pendaftar_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        Pendaftar::create([
            'id_user' => $calonDenganPendaftar->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => 'Calon Sudah Mendaftar',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->admin)->get('/admin/akun-tanpa-pendaftaran')
            ->assertOk()
            ->assertSee($this->calonSantri->nama_user)
            ->assertDontSee($calonDenganPendaftar->nama_user)
            ->assertDontSee($this->santri->nama_user);
    }

    public function test_admin_dapat_menghapus_akun_calon_santri_tanpa_pendaftar(): void
    {
        $this->actingAs($this->admin)->delete('/admin/akun-tanpa-pendaftaran/'.$this->calonSantri->id_user)
            ->assertRedirect(route('admin.akun-tanpa-pendaftaran.index'))
            ->assertSessionHas('sukses');

        $this->assertDatabaseMissing('users', ['id_user' => $this->calonSantri->id_user]);
    }

    public function test_admin_tidak_dapat_menghapus_akun_yang_mempunyai_pendaftar(): void
    {
        $periode = PeriodePendaftaran::first() ?? PeriodePendaftaran::create([
            'nama_periode' => 'Periode Akun Terdaftar',
            'tanggal_buka' => '2026-01-01',
            'tanggal_tutup' => '2026-12-31',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => 'Calon Sudah Mendaftar',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->admin)->delete('/admin/akun-tanpa-pendaftaran/'.$this->calonSantri->id_user)
            ->assertSessionHas('galat', 'Akun yang sudah memiliki pendaftar tidak dapat dihapus.');

        $this->assertDatabaseHas('users', ['id_user' => $this->calonSantri->id_user]);
        $this->assertDatabaseHas('pendaftar', ['id_pendaftar' => $pendaftar->id_pendaftar]);
    }

    public function test_admin_tidak_dapat_menghapus_akun_bukan_calon_santri(): void
    {
        $this->actingAs($this->admin)->delete('/admin/akun-tanpa-pendaftaran/'.$this->santri->id_user)
            ->assertSessionHas('galat', 'Hanya akun calon santri yang dapat dihapus.');

        $this->assertDatabaseHas('users', ['id_user' => $this->santri->id_user]);
    }

    public function test_peran_lain_tidak_dapat_membuka_atau_menghapus_akun(): void
    {
        foreach ([$this->calonSantri, $this->santri] as $user) {
            $this->actingAs($user)->get('/admin/akun-tanpa-pendaftaran')->assertForbidden();
            $this->actingAs($user)->delete('/admin/akun-tanpa-pendaftaran/'.$this->calonSantri->id_user)
                ->assertForbidden();
        }
    }
}
