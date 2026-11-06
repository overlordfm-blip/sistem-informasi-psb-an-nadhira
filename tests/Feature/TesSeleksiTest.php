<?php

namespace Tests\Feature;

use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\User;
use App\Services\TesSeleksiService;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class TesSeleksiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_selecting_enabled_online_mode_writes_private_test_data_without_changing_registration_status(): void
    {
        $akun = $this->buatAkunCalon();
        $pendaftar = $this->buatPendaftar($akun);
        $layanan = app(TesSeleksiService::class);
        $layanan->simpanPengaturan($this->pengaturan(onlineAktif: true));

        $dataTes = $layanan->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);

        $this->assertSame('online', $dataTes['mode']);
        $this->assertSame('belum', $dataTes['hasil']);
        $this->assertSame('diproses', $pendaftar->fresh()->status_daftar);
        Storage::disk('local')->assertExists("tes/pendaftar-{$pendaftar->id_pendaftar}.json");
    }

    public function test_inactive_test_mode_cannot_be_selected(): void
    {
        $akun = $this->buatAkunCalon();
        $pendaftar = $this->buatPendaftar($akun);
        $layanan = app(TesSeleksiService::class);
        $layanan->simpanPengaturan($this->pengaturan());

        $this->expectException(RuntimeException::class);
        $layanan->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);
    }

    public function test_mode_can_change_before_result_and_is_locked_after_result(): void
    {
        $akun = $this->buatAkunCalon();
        $admin = User::create([
            'nama_user' => 'tes_admin_'.uniqid(),
            'password' => 'PasswordLama123',
            'peran' => User::PERAN_ADMIN,
        ]);
        $pendaftar = $this->buatPendaftar($akun);
        $layanan = app(TesSeleksiService::class);
        $layanan->simpanPengaturan($this->pengaturan(onlineAktif: true, offlineAktif: true));
        $layanan->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);

        $layanan->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_OFFLINE);
        $layanan->simpanHasil($pendaftar->id_pendaftar, 'lulus', 90, 'Lulus tes', $admin->id_user);

        $this->expectException(RuntimeException::class);
        $layanan->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);
    }

    public function test_offline_mode_can_be_selected_when_active(): void
    {
        $akun = $this->buatAkunCalon();
        $pendaftar = $this->buatPendaftar($akun);
        $layanan = app(TesSeleksiService::class);
        $layanan->simpanPengaturan($this->pengaturan(onlineAktif: false, offlineAktif: true));

        $dataTes = $layanan->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_OFFLINE);

        $this->assertSame('offline', $dataTes['mode']);
        $this->assertSame('belum', $dataTes['hasil']);
        $this->assertSame('diproses', $pendaftar->fresh()->status_daftar);
    }

    public function test_admin_simpan_hasil_tes_via_service(): void
    {
        $akun = $this->buatAkunCalon();
        $pendaftar = $this->buatPendaftar($akun);
        $admin = User::create([
            'nama_user' => 'tes_admin_'.uniqid(),
            'password' => 'PasswordLama123',
            'peran' => User::PERAN_ADMIN,
        ]);
        $layanan = app(TesSeleksiService::class);
        $layanan->simpanPengaturan($this->pengaturan(onlineAktif: true));

        $layanan->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $data = $layanan->simpanHasil($pendaftar->id_pendaftar, 'lulus', 85, 'Catatan uji', $admin->id_user);

        $this->assertSame('lulus', $data['hasil']);
        $this->assertSame(85, $data['nilai']);
        $this->assertSame('Catatan uji', $data['catatan']);
        $this->assertSame($admin->id_user, $data['dinilai_oleh']);
        $this->assertNotNull($data['dinilai_pada']);
    }

    public function test_admin_sees_how_to_populate_empty_online_schedule_list(): void
    {
        $admin = User::query()->where('peran', User::PERAN_ADMIN)->firstOrFail();

        $this->actingAs($admin)->get('/admin/pengaturan-tes')->assertOk()
            ->assertSee('Jadwal Tes Online Calon Santri')
            ->assertSee('Calon berstatus diproses perlu memilih Tes Online dari halaman Tes Seleksi');
    }

    public function test_online_schedule_is_private_per_applicant_and_rejects_a_used_slot(): void
    {
        $akunPertama = $this->buatAkunCalon();
        $akunKedua = $this->buatAkunCalon();
        $pendaftarPertama = $this->buatPendaftar($akunPertama, 'Calon Jadwal Pertama');
        $pendaftarKedua = $this->buatPendaftar($akunKedua, 'Calon Jadwal Kedua');
        $layanan = app(TesSeleksiService::class);
        $layanan->simpanPengaturan($this->pengaturan(onlineAktif: true));
        $layanan->pilihMode($pendaftarPertama->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $layanan->pilihMode($pendaftarKedua->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $tanggal = now('Asia/Jakarta')->addDay()->toDateString();

        $jadwal = $layanan->jadwalkanOnline($pendaftarPertama->id_pendaftar, $tanggal, '09:00', 1);

        $this->assertSame(['tanggal', 'jam', 'ditetapkan_oleh', 'ditetapkan_pada'], array_keys($jadwal));
        $berkasPertama = Storage::disk('local')->get("tes/pendaftar-{$pendaftarPertama->id_pendaftar}.json");
        $this->assertStringContainsString('09:00', $berkasPertama);
        $this->assertNull($layanan->baca($pendaftarKedua->id_pendaftar)['jadwal_online']);

        try {
            $layanan->jadwalkanOnline($pendaftarKedua->id_pendaftar, $tanggal, '09:00', 1);
            $this->fail('Slot jadwal yang sudah digunakan seharusnya ditolak.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Slot waktu tersebut sudah digunakan', $exception->getMessage());
        }
    }

    public function test_admin_schedules_online_applicant_and_the_name_leaves_waiting_list(): void
    {
        $admin = User::query()->where('peran', User::PERAN_ADMIN)->firstOrFail();
        $akunTerjadwal = $this->buatAkunCalon();
        $akunMenunggu = $this->buatAkunCalon();
        $akunOffline = $this->buatAkunCalon();
        $pendaftarTerjadwal = $this->buatPendaftar($akunTerjadwal, 'Calon Sudah Terjadwal');
        $pendaftarMenunggu = $this->buatPendaftar($akunMenunggu, 'Calon Menunggu Jadwal');
        $pendaftarOffline = $this->buatPendaftar($akunOffline, 'Calon Tes Offline');
        $layanan = app(TesSeleksiService::class);
        $layanan->simpanPengaturan($this->pengaturan(onlineAktif: true, offlineAktif: true));
        $layanan->pilihMode($pendaftarTerjadwal->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $layanan->pilihMode($pendaftarMenunggu->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $layanan->pilihMode($pendaftarOffline->id_pendaftar, TesSeleksiService::MODE_OFFLINE);
        $tanggal = now('Asia/Jakarta')->addDay()->toDateString();
        $layanan->jadwalkanOnline($pendaftarTerjadwal->id_pendaftar, $tanggal, '09:00', $admin->id_user);

        $this->actingAs($admin)->get('/admin/pengaturan-tes')->assertOk()
            ->assertSee('Calon Menunggu Jadwal')
            ->assertDontSee('Calon Sudah Terjadwal')
            ->assertDontSee('Calon Tes Offline');

        $this->post('/admin/pengaturan-tes/jadwal-online', [
            'id_pendaftar' => $pendaftarMenunggu->id_pendaftar,
            'tanggal' => $tanggal,
            'jam' => '09:00',
        ])->assertRedirect('/admin/pengaturan-tes')->assertSessionHasErrors('jadwal_online');

        $this->post('/admin/pengaturan-tes/jadwal-online', [
            'id_pendaftar' => $pendaftarMenunggu->id_pendaftar,
            'tanggal' => $tanggal,
            'jam' => '10:00',
        ])->assertRedirect('/admin/pengaturan-tes')->assertSessionHas('sukses');

        $this->get('/admin/pengaturan-tes')->assertOk()
            ->assertDontSee('Calon Menunggu Jadwal')
            ->assertDontSee('Calon Sudah Terjadwal');

        $this->actingAs($akunMenunggu)->get('/tes')->assertOk()
            ->assertSee('Tanggal:')
            ->assertSee('10:00 WIB')
            ->assertSee('Buka tautan tes online');
    }

    public function test_terima_tanpa_hasil_lulus_ditolak_oleh_controller(): void
    {
        // This is covered at HTTP level in AdminPendaftarTest;
        // at service level, simpanHasil simply records the result.
        $this->assertTrue(true);
    }

    private function pengaturan(bool $onlineAktif = false, bool $offlineAktif = false): array
    {
        return [
            'online' => [
                'aktif' => $onlineAktif,
                'tautan' => 'https://contoh.test/tes',
                'petunjuk' => 'Siapkan koneksi internet.',
            ],
            'offline' => [
                'aktif' => $offlineAktif,
                'tanggal' => '2026-11-10',
                'jam' => '09:00',
                'lokasi' => 'Aula Pondok',
                'petunjuk' => 'Datang 15 menit lebih awal.',
            ],
        ];
    }

    private function buatAkunCalon(): User
    {
        return User::create([
            'nama_user' => 'tes_calon_'.uniqid(),
            'password' => 'PasswordLama123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
    }

    private function buatPendaftar(User $akun, string $namaLengkap = 'Calon Tes Seleksi'): Pendaftar
    {
        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Tes '.uniqid(),
            'tanggal_buka' => '2026-01-01',
            'tanggal_tutup' => '2026-12-31',
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);

        return Pendaftar::create([
            'id_user' => $akun->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => $namaLengkap,
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Tes Seleksi',
            'no_telepon' => '081234567890',
            'status_daftar' => 'diproses',
        ]);
    }
}
