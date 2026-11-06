<?php

namespace Tests\Feature;

use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class AdminPeriodeTest extends TestCase
{
    protected User $admin;

    protected User $calonSantri;

    protected User $santri;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-30 10:00:00', 'Asia/Jakarta');

        $this->admin = User::where('peran', User::PERAN_ADMIN)->first()
            ?? User::create([
                'nama_user' => 'admin_test',
                'password' => 'admin123',
                'peran' => User::PERAN_ADMIN,
            ]);

        $this->calonSantri = User::where('peran', User::PERAN_CALON_SANTRI)->first()
            ?? User::create([
                'nama_user' => 'calon_test',
                'password' => 'santri123',
                'peran' => User::PERAN_CALON_SANTRI,
            ]);

        $this->santri = User::create([
            'nama_user' => 'santri_aktif_test',
            'password' => 'santri123',
            'peran' => User::PERAN_SANTRI,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calon_santri_akses_periode_mendapat_403(): void
    {
        $periode = PeriodePendaftaran::first();

        $this->actingAs($this->calonSantri)->get('/admin/periode')->assertStatus(403);
        $this->actingAs($this->calonSantri)->get('/admin/periode/tambah')->assertStatus(403);
        $this->actingAs($this->calonSantri)->post('/admin/periode', [
            'nama_periode' => 'Gelombang Ilegal',
            'tanggal_buka' => '2027-01-01',
            'tanggal_tutup' => '2027-02-01',
        ])->assertStatus(403);

        if ($periode) {
            $this->actingAs($this->calonSantri)->get("/admin/periode/{$periode->id_periode}/ubah")->assertStatus(403);
            $this->actingAs($this->calonSantri)->post("/admin/periode/{$periode->id_periode}/buka")->assertStatus(403);
            $this->actingAs($this->calonSantri)->post("/admin/periode/{$periode->id_periode}/tutup")->assertStatus(403);
            $this->actingAs($this->calonSantri)->post("/admin/periode/{$periode->id_periode}/toggle-status")->assertStatus(403);
            $this->actingAs($this->calonSantri)->delete("/admin/periode/{$periode->id_periode}")->assertStatus(403);
        }
    }

    public function test_santri_akses_periode_mendapat_403(): void
    {
        $this->actingAs($this->santri)->get('/admin/periode')->assertStatus(403);
    }

    public function test_tamu_belum_login_akses_periode_diarahkan_ke_login(): void
    {
        $this->get('/admin/periode')->assertRedirect('/login');
    }

    public function test_admin_dapat_melihat_daftar_periode(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/periode');

        $response->assertStatus(200);
        $response->assertSee('Periode Pendaftaran');
        $response->assertSee('Daftar Semua Periode');
    }

    public function test_daftar_periode_admin_menampilkan_sisa_kuota(): void
    {
        config(['pondok.kuota' => 3]);
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Kuota Admin',
            'tanggal_buka' => now()->toDateString(),
            'tanggal_tutup' => now()->addMonth()->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
        $calon = User::create([
            'nama_user' => 'calon_sisa_kuota_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        Pendaftar::create([
            'id_user' => $calon->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => 'Pengguna Kuota',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->admin)->get('/admin/periode')
            ->assertOk()
            ->assertSee('Sisa Kuota:')
            ->assertSee('2 dari 3');
    }

    public function test_admin_dapat_melihat_formulir_tambah_periode(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/periode/tambah');

        $response->assertStatus(200);
        $response->assertSee('Tambah Periode Pendaftaran');
    }

    public function test_admin_dapat_menambah_periode_baru(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/periode', [
            'nama_periode' => 'Gelombang Baru 2027',
            'tanggal_buka' => '2027-01-10',
            'tanggal_tutup' => '2027-03-20',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $response->assertRedirect('/admin/periode');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('periode_pendaftaran', [
            'nama_periode' => 'Gelombang Baru 2027',
            'tanggal_buka' => '2027-01-10',
            'tanggal_tutup' => '2027-03-20',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);
    }

    public function test_tambah_periode_ditolak_jika_tanggal_buka_sudah_terlewat(): void
    {
        $tanggalBukaLampau = now('Asia/Jakarta')->subDay()->toDateString();
        $tanggalTutup = now('Asia/Jakarta')->addDays(10)->toDateString();
        $response = $this->actingAs($this->admin)->from('/admin/periode/tambah')->post('/admin/periode', [
            'nama_periode' => 'Periode Tanggal Lampau',
            'tanggal_buka' => $tanggalBukaLampau,
            'tanggal_tutup' => $tanggalTutup,
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $response->assertRedirect('/admin/periode/tambah')
            ->assertSessionHasErrors(['tanggal_buka' => 'Tanggal buka pendaftaran tidak boleh sudah terlewat.']);
        $this->assertDatabaseMissing('periode_pendaftaran', ['nama_periode' => 'Periode Tanggal Lampau']);
    }

    public function test_validasi_tambah_periode_gagal_jika_tanggal_tutup_sebelum_tanggal_buka(): void
    {
        $response = $this->actingAs($this->admin)->from('/admin/periode/tambah')->post('/admin/periode', [
            'nama_periode' => 'Gelombang Keliru',
            'tanggal_buka' => '2027-05-10',
            'tanggal_tutup' => '2027-04-10', // Lebih awal dari tanggal buka
        ]);

        $response->assertRedirect('/admin/periode/tambah');
        $response->assertSessionHasErrors('tanggal_tutup');
    }

    public function test_tambah_periode_ditolak_jika_rentang_tanggal_tumpang_tindih(): void
    {
        PeriodePendaftaran::create([
            'nama_periode' => 'Periode Acuan Overlap Tambah',
            'tanggal_buka' => '2026-11-10',
            'tanggal_tutup' => '2026-11-20',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $this->actingAs($this->admin)->from('/admin/periode/tambah')->post('/admin/periode', [
            'nama_periode' => 'Periode Overlap Tambah',
            'tanggal_buka' => '2026-11-20',
            'tanggal_tutup' => '2026-11-25',
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ])->assertRedirect('/admin/periode/tambah')->assertSessionHasErrors('tanggal_buka');

        $this->assertDatabaseMissing('periode_pendaftaran', ['nama_periode' => 'Periode Overlap Tambah']);
    }

    public function test_validasi_tambah_periode_nama_periode_dan_tanggal_wajib(): void
    {
        $response = $this->actingAs($this->admin)->from('/admin/periode/tambah')->post('/admin/periode', []);

        $response->assertRedirect('/admin/periode/tambah');
        $response->assertSessionHasErrors(['nama_periode', 'tanggal_buka', 'tanggal_tutup']);
    }

    public function test_admin_dapat_melihat_formulir_ubah_periode(): void
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Uji Ubah',
            'tanggal_buka' => '2027-02-01',
            'tanggal_tutup' => '2027-03-01',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/periode/{$periode->id_periode}/ubah");

        $response->assertStatus(200);
        $response->assertSee('Ubah Periode Pendaftaran');
        $response->assertSee('Periode Uji Ubah');
    }

    public function test_admin_dapat_mengubah_periode(): void
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Sebelum Edit',
            'tanggal_buka' => '2027-02-01',
            'tanggal_tutup' => '2027-03-01',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/periode/{$periode->id_periode}", [
            'nama_periode' => 'Periode Sesudah Edit',
            'tanggal_buka' => '2027-02-05',
            'tanggal_tutup' => '2027-03-25',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $response->assertRedirect('/admin/periode');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periode->id_periode,
            'nama_periode' => 'Periode Sesudah Edit',
            'tanggal_buka' => '2027-02-05',
            'tanggal_tutup' => '2027-03-25',
        ]);
    }

    public function test_ubah_periode_ditolak_jika_rentangnya_tumpang_tindih(): void
    {
        $periodeA = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Acuan Overlap Ubah A',
            'tanggal_buka' => '2026-11-01',
            'tanggal_tutup' => '2026-11-10',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);
        $periodeB = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Acuan Overlap Ubah B',
            'tanggal_buka' => '2026-11-11',
            'tanggal_tutup' => '2026-11-20',
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);

        $this->actingAs($this->admin)->from("/admin/periode/{$periodeB->id_periode}/ubah")
            ->put("/admin/periode/{$periodeB->id_periode}", [
                'nama_periode' => $periodeB->nama_periode,
                'tanggal_buka' => '2026-11-10',
                'tanggal_tutup' => '2026-11-20',
                'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
            ])->assertRedirect("/admin/periode/{$periodeB->id_periode}/ubah")
            ->assertSessionHasErrors('tanggal_buka');

        $this->assertSame('2026-11-11', $periodeB->fresh()->tanggal_buka->toDateString());
        $this->assertSame(PeriodePendaftaran::STATUS_DITUTUP, $periodeA->fresh()->status_periode);
    }

    public function test_admin_bisa_membuka_dan_menutup_periode(): void
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Buka Tutup',
            'tanggal_buka' => '2026-10-27',
            'tanggal_tutup' => '2026-11-21',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        // 1. Admin buka periode via rute buka
        $responseBuka = $this->actingAs($this->admin)->post("/admin/periode/{$periode->id_periode}/buka");
        $responseBuka->assertRedirect('/admin/periode');
        $responseBuka->assertSessionHas('sukses');

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periode->id_periode,
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);

        // 2. Admin tutup periode via rute tutup
        $responseTutup = $this->actingAs($this->admin)->post("/admin/periode/{$periode->id_periode}/tutup");
        $responseTutup->assertRedirect('/admin/periode');
        $responseTutup->assertSessionHas('sukses');

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periode->id_periode,
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        // 3. Admin beralih status via toggle-status
        $responseToggle = $this->actingAs($this->admin)->post("/admin/periode/{$periode->id_periode}/toggle-status");
        $responseToggle->assertRedirect('/admin/periode');
        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periode->id_periode,
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
    }

    public function test_membuka_periode_terjadwal_tidak_menutup_periode_lain(): void
    {
        $periodeA = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang A Aktif',
            'tanggal_buka' => '2026-10-01',
            'tanggal_tutup' => '2026-11-15',
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
        $periodeB = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang B Cadangan',
            'tanggal_buka' => '2026-11-16',
            'tanggal_tutup' => '2026-12-31',
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/periode/{$periodeB->id_periode}/buka");
        $response->assertRedirect('/admin/periode')->assertSessionHas('sukses');

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periodeB->id_periode,
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periodeA->id_periode,
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
        $this->assertSame([$periodeA->id_periode], PeriodePendaftaran::query()->tersedia()->pluck('id_periode')->all());
        $this->assertSame('Dibuka', $periodeA->fresh()->labelStatus());
        $this->assertSame('Dijadwalkan', $periodeB->fresh()->labelStatus());

        $this->actingAs($this->admin)->get('/admin/periode')
            ->assertOk()
            ->assertSee('Dibuka')
            ->assertSee('Dijadwalkan');
    }

    public function test_hapus_periode_terlewat_dan_tanpa_pendaftar_berhasil(): void
    {
        // Periode sudah terlewat (tanggal_tutup < hari ini di Asia/Jakarta), ditutup, dan tanpa pendaftar
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Lampau Tanpa Pendaftar',
            'tanggal_buka' => now('Asia/Jakarta')->subMonths(3)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->subMonth()->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/periode/{$periode->id_periode}");

        $response->assertRedirect('/admin/periode');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseMissing('periode_pendaftaran', [
            'id_periode' => $periode->id_periode,
        ]);
    }

    public function test_hapus_periode_terlewat_tetapi_punya_pendaftar_ditolak(): void
    {
        // Periode sudah terlewat dan ditutup, namun memiliki data pendaftar
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Lampau Ada Pendaftar',
            'tanggal_buka' => now('Asia/Jakarta')->subMonths(3)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->subMonth()->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $userPendaftar = User::create([
            'nama_user' => 'pendaftar_uji_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        Pendaftar::create([
            'id_user' => $userPendaftar->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => 'Muhammad Santri',
            'tanggal_lahir' => '2010-05-12',
            'alamat' => 'Kalibeber, Wonosobo',
            'nama_wali' => 'Bapak Muhammad',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/periode/{$periode->id_periode}");

        $response->assertRedirect('/admin/periode');
        $response->assertSessionHas('galat');
        $this->assertStringContainsString('sudah memiliki data pendaftar', session('galat'));

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periode->id_periode,
        ]);
    }

    public function test_hapus_periode_belum_terlewat_ditolak(): void
    {
        // Periode belum terlewat (tanggal_tutup masih di masa depan)
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Masa Depan',
            'tanggal_buka' => now('Asia/Jakarta')->subDays(5)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->addDays(20)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/periode/{$periode->id_periode}");

        $response->assertRedirect('/admin/periode');
        $response->assertSessionHas('galat');
        $this->assertStringContainsString('belum terlewat', session('galat'));

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periode->id_periode,
        ]);
    }

    public function test_hapus_periode_berstatus_dibuka_ditolak(): void
    {
        // Periode yang sedang dibuka tidak boleh dihapus
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Sedang Dibuka',
            'tanggal_buka' => now('Asia/Jakarta')->addDays(5)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->addDays(20)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/periode/{$periode->id_periode}");

        $response->assertRedirect('/admin/periode');
        $response->assertSessionHas('galat');
        $this->assertStringContainsString('masih berstatus dibuka', session('galat'));

        $this->assertDatabaseHas('periode_pendaftaran', [
            'id_periode' => $periode->id_periode,
        ]);
    }
}
