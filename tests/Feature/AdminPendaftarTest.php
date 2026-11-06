<?php

namespace Tests\Feature;

use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\TesSeleksiService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPendaftarTest extends TestCase
{
    protected User $admin;

    protected User $santri;

    protected User $calonSantri;

    protected PeriodePendaftaran $periode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('peran', User::PERAN_ADMIN)->first()
            ?? User::create([
                'nama_user' => 'admin_pendaftar_test',
                'password' => 'admin123',
                'peran' => User::PERAN_ADMIN,
            ]);

        $this->santri = User::create([
            'nama_user' => 'santri_ujiverif_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_SANTRI,
        ]);

        $this->calonSantri = User::create([
            'nama_user' => 'calon_ujiverif_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        $this->periode = PeriodePendaftaran::first() ?? PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Verifikasi 2026/2027',
            'tanggal_buka' => '2026-01-01',
            'tanggal_tutup' => '2026-12-31',
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
    }

    public function test_daftar_pendaftar_tampil_untuk_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/pendaftar');

        $response->assertStatus(200);
        $response->assertSee('Data Pendaftar Santri Baru');
        $response->assertSee('Daftar Calon Santri');
    }

    public function test_pencarian_dan_filter_status_pendaftar(): void
    {
        $userA = User::create([
            'nama_user' => 'calon_umar_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        $userB = User::create([
            'nama_user' => 'calon_zaid_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        $pendaftarA = Pendaftar::create([
            'id_user' => $userA->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Umar Al-Faruq',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Umar',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $pendaftarB = Pendaftar::create([
            'id_user' => $userB->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Zaid bin Tsabit',
            'tanggal_lahir' => '2010-02-02',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Zaid',
            'no_telepon' => '081299998888',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);

        // 1. Uji Pencarian berdasarkan nama
        $resCariA = $this->actingAs($this->admin)->get('/admin/pendaftar?cari=Umar');
        $resCariA->assertStatus(200);
        $resCariA->assertSee('Umar Al-Faruq');
        $resCariA->assertDontSee('Zaid bin Tsabit');

        $resCariB = $this->actingAs($this->admin)->get('/admin/pendaftar?cari=Zaid');
        $resCariB->assertStatus(200);
        $resCariB->assertSee('Zaid bin Tsabit');
        $resCariB->assertDontSee('Umar Al-Faruq');

        // 2. Uji Filter status
        $resFilterDiproses = $this->actingAs($this->admin)->get('/admin/pendaftar?status=diproses');
        $resFilterDiproses->assertStatus(200);
        $resFilterDiproses->assertSee('Umar Al-Faruq');
        $resFilterDiproses->assertDontSee('Zaid bin Tsabit');

        $resFilterDiterima = $this->actingAs($this->admin)->get('/admin/pendaftar?status=diterima');
        $resFilterDiterima->assertStatus(200);
        $resFilterDiterima->assertSee('Zaid bin Tsabit');
        $resFilterDiterima->assertDontSee('Umar Al-Faruq');
    }

    public function test_detail_pendaftar_tampil_dengan_lengkap(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Uji Detail',
            'tanggal_lahir' => '2011-05-20',
            'alamat' => 'Kalibeber Kulon',
            'nama_wali' => 'Bapak Uji',
            'no_telepon' => '081233445566',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/pendaftar/{$pendaftar->id_pendaftar}");

        $response->assertStatus(200);
        $response->assertSee('Detail Verifikasi Pendaftar');
        $response->assertSee('Santri Uji Detail');
        $response->assertSee('Kalibeber Kulon');
        $response->assertSee('Bapak Uji');
        $response->assertSee('081233445566');
        $response->assertSee('Terima Pendaftar');
        $response->assertSee('Tolak Pendaftar');
    }

    public function test_terima_membuat_tepat_satu_tagihan_pendaftaran_bernominal_sesuai_konfigurasi(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Siap Diterima',
            'tanggal_lahir' => '2010-08-15',
            'alamat' => 'Mojotengah',
            'nama_wali' => 'Bapak Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->beriHasilTesLulus($pendaftar);
        $biayaPendaftaran = config('pondok.biaya_pendaftaran', 150000);

        $response = $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima");

        $response->assertRedirect('/admin/pendaftar');
        $response->assertSessionHas('sukses');

        // Status pendaftar berubah menjadi diterima
        $this->assertDatabaseHas('pendaftar', [
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);

        // Tagihan pendaftaran otomatis dibuat dengan status belum_dibayar
        $this->assertDatabaseHas('tagihan', [
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_PENDAFTARAN,
            'nominal' => $biayaPendaftaran,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        // Tepat satu tagihan pendaftaran dibuat
        $jumlahTagihan = Tagihan::where('id_pendaftar', $pendaftar->id_pendaftar)
            ->where('jenis_tagihan', Tagihan::JENIS_PENDAFTARAN)
            ->count();
        $this->assertEquals(1, $jumlahTagihan);
    }

    public function test_peran_pengguna_tetap_calon_santri_dan_tabel_santri_tetap_kosong_setelah_terima(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Uji Peran',
            'tanggal_lahir' => '2010-09-09',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->beriHasilTesLulus($pendaftar);

        $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima");

        $this->assertSame(Pendaftar::STATUS_DITERIMA, $pendaftar->fresh()->status_daftar);
        $this->assertEquals(User::PERAN_CALON_SANTRI, $this->calonSantri->fresh()->peran);

        // Tabel santri HARUS tetap kosong untuk pendaftar ini
        $this->assertDatabaseMissing('santri', [
            'id_pendaftar' => $pendaftar->id_pendaftar,
        ]);
    }

    public function test_terima_dua_kali_tidak_membuat_tagihan_ganda(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Terima Ganda',
            'tanggal_lahir' => '2010-03-03',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->beriHasilTesLulus($pendaftar);

        // Terima pertama kali
        $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima")
            ->assertRedirect('/admin/pendaftar')
            ->assertSessionHas('sukses');

        // Terima kedua kali
        $resKedua = $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima");
        $resKedua->assertRedirect('/admin/pendaftar');
        $resKedua->assertSessionHas('galat');

        // Tagihan tetap berjumlah tepat 1
        $jumlahTagihan = Tagihan::where('id_pendaftar', $pendaftar->id_pendaftar)
            ->where('jenis_tagihan', Tagihan::JENIS_PENDAFTARAN)
            ->count();
        $this->assertEquals(1, $jumlahTagihan);
    }

    public function test_tolak_tidak_membuat_tagihan(): void
    {
        Storage::fake('local');
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Ditolak',
            'tanggal_lahir' => '2010-07-07',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/tolak", [
            'catatan' => 'Berkas belum lengkap.',
        ]);

        $response->assertRedirect('/admin/pendaftar');
        $response->assertSessionHas('sukses');

        // Status pendaftar berubah menjadi ditolak
        $this->assertDatabaseHas('pendaftar', [
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        // Tagihan sama sekali tidak dibuat
        $this->assertDatabaseMissing('tagihan', [
            'id_pendaftar' => $pendaftar->id_pendaftar,
        ]);
        Storage::disk('local')->assertExists("catatan/pendaftar-{$pendaftar->id_pendaftar}.txt");
    }

    public function test_terima_tanpa_hasil_lulus_ditolak_tanpa_mengubah_status_atau_membuat_tagihan(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Calon Belum Lulus Tes',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Calon',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima")
            ->assertRedirect(route('admin.pendaftar.show', $pendaftar->id_pendaftar))
            ->assertSessionHas('galat', 'Pendaftar hanya dapat diterima setelah lulus Tes Seleksi.');

        $this->assertSame(Pendaftar::STATUS_DIPROSES, $pendaftar->fresh()->status_daftar);
        $this->assertDatabaseMissing('tagihan', ['id_pendaftar' => $pendaftar->id_pendaftar]);
    }

    public function test_tolak_tanpa_catatan_ditolak_validasi(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Tanpa Catatan',
            'tanggal_lahir' => '2010-07-07',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->admin)
            ->from("/admin/pendaftar/{$pendaftar->id_pendaftar}")
            ->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/tolak")
            ->assertRedirect("/admin/pendaftar/{$pendaftar->id_pendaftar}")
            ->assertSessionHasErrors('catatan');

        $this->assertSame(Pendaftar::STATUS_DIPROSES, $pendaftar->fresh()->status_daftar);
    }

    public function test_catatan_penolakan_tampil_aman_hanya_untuk_pemilik_dan_admin(): void
    {
        Storage::fake('local');
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Dengan Catatan',
            'tanggal_lahir' => '2010-07-07',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);
        $catatan = "Berkas perlu dilengkapi.\n<script>alert(1)</script>";

        $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/tolak", [
            'catatan' => $catatan,
        ])->assertRedirect('/admin/pendaftar');

        $path = "catatan/pendaftar-{$pendaftar->id_pendaftar}.txt";
        Storage::disk('local')->assertExists($path);
        $this->assertSame($catatan, Storage::disk('local')->get($path));

        $this->actingAs($this->admin)->get("/admin/pendaftar/{$pendaftar->id_pendaftar}")
            ->assertOk()
            ->assertSee('Berkas perlu dilengkapi.')
            ->assertSee('alert(1)')
            ->assertDontSee('<script>', false);

        $this->actingAs($this->calonSantri)->get('/status-pendaftaran')
            ->assertOk()
            ->assertSee('Catatan dari admin:')
            ->assertSee('Berkas perlu dilengkapi.')
            ->assertSee('alert(1)')
            ->assertDontSee('<script>', false);

        $penggunaLain = User::create([
            'nama_user' => 'calon_tanpa_catatan_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $this->actingAs($penggunaLain)->get('/status-pendaftaran')
            ->assertOk()
            ->assertDontSee('Berkas perlu dilengkapi.')
            ->assertDontSee('alert(1)');
    }

    public function test_kegagalan_menyimpan_catatan_membatalkan_penolakan(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Gagal Tulis',
            'tanggal_lahir' => '2010-07-07',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')
            ->once()
            ->with("catatan/pendaftar-{$pendaftar->id_pendaftar}.txt", 'Catatan gagal disimpan.')
            ->andReturn(false);
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/tolak", [
            'catatan' => 'Catatan gagal disimpan.',
        ])->assertRedirect("/admin/pendaftar/{$pendaftar->id_pendaftar}")
            ->assertSessionHas('galat', 'Catatan penolakan gagal disimpan. Status pendaftar tidak diubah.');

        $this->assertSame(Pendaftar::STATUS_DIPROSES, $pendaftar->fresh()->status_daftar);
    }

    public function test_terima_atau_tolak_pada_status_bukan_diproses_ditolak(): void
    {
        // 1. Pendaftar yang sudah DITERIMA
        $pendaftarDiterima = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Sudah Diterima',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);

        // Coba tolak pendaftar yang sudah diterima -> ditolak
        $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftarDiterima->id_pendaftar}/tolak", [
            'catatan' => 'Status sudah diterima.',
        ])
            ->assertRedirect('/admin/pendaftar')
            ->assertSessionHas('galat');
        $this->assertEquals(Pendaftar::STATUS_DITERIMA, $pendaftarDiterima->fresh()->status_daftar);

        // 2. Pendaftar yang sudah DITOLAK
        $userLain = User::create([
            'nama_user' => 'calon_tolak_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        $pendaftarDitolak = Pendaftar::create([
            'id_user' => $userLain->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Sudah Ditolak',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        // Coba terima pendaftar yang sudah ditolak -> ditolak
        $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftarDitolak->id_pendaftar}/terima")
            ->assertRedirect('/admin/pendaftar')
            ->assertSessionHas('galat');
        $this->assertEquals(Pendaftar::STATUS_DITOLAK, $pendaftarDitolak->fresh()->status_daftar);
    }

    public function test_calon_santri_dan_santri_mendapat_403_pada_keempat_rute(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Otorisasi',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        // 1. Calon santri coba akses 4 rute admin
        $this->actingAs($this->calonSantri)->get('/admin/pendaftar')->assertStatus(403);
        $this->actingAs($this->calonSantri)->get("/admin/pendaftar/{$pendaftar->id_pendaftar}")->assertStatus(403);
        $this->actingAs($this->calonSantri)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima")->assertStatus(403);
        $this->actingAs($this->calonSantri)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/tolak")->assertStatus(403);

        // 2. Santri coba akses 4 rute admin
        $this->actingAs($this->santri)->get('/admin/pendaftar')->assertStatus(403);
        $this->actingAs($this->santri)->get("/admin/pendaftar/{$pendaftar->id_pendaftar}")->assertStatus(403);
        $this->actingAs($this->santri)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima")->assertStatus(403);
        $this->actingAs($this->santri)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/tolak")->assertStatus(403);
    }

    public function test_tamu_belum_login_akses_pendaftar_diarahkan_ke_login(): void
    {
        $this->get('/admin/pendaftar')->assertRedirect('/login');
    }

    public function test_admin_menyimpan_hasil_tes_via_http(): void
    {
        Storage::fake('local');
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Hasil Tes',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $tesSeleksi = app(TesSeleksiService::class);
        $tesSeleksi->simpanPengaturan([
            'online' => ['aktif' => true, 'tautan' => 'https://contoh.test/tes', 'petunjuk' => ''],
            'offline' => ['aktif' => false, 'tanggal' => '', 'jam' => '', 'lokasi' => '', 'petunjuk' => ''],
        ]);
        $tesSeleksi->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);

        $response = $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/tes", [
            'hasil' => 'lulus',
            'nilai' => 85,
            'catatan_tes' => 'Nilai tes baik.',
        ]);

        $response->assertRedirect("/admin/pendaftar/{$pendaftar->id_pendaftar}");
        $response->assertSessionHas('sukses');

        $dataTes = $tesSeleksi->baca($pendaftar->id_pendaftar);
        $this->assertSame('lulus', $dataTes['hasil']);
        $this->assertSame(85, $dataTes['nilai']);
        $this->assertSame('Nilai tes baik.', $dataTes['catatan']);
        $this->assertSame($this->admin->id_user, $dataTes['dinilai_oleh']);
    }

    public function test_admin_tidak_boleh_terima_tanpa_hasil_tes_lulus(): void
    {
        Storage::fake('local');
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Tanpa Lulus',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima");
        $response->assertRedirect(route('admin.pendaftar.show', $pendaftar->id_pendaftar));
        $response->assertSessionHas('galat', 'Pendaftar hanya dapat diterima setelah lulus Tes Seleksi.');
        $this->assertSame(Pendaftar::STATUS_DIPROSES, $pendaftar->fresh()->status_daftar);
        $this->assertDatabaseMissing('tagihan', ['id_pendaftar' => $pendaftar->id_pendaftar]);
    }

    public function test_halaman_index_tampilkan_kolom_tes_dan_filter_tes(): void
    {
        Storage::fake('local');
        $tesSeleksi = app(TesSeleksiService::class);
        $tesSeleksi->simpanPengaturan([
            'online' => ['aktif' => true, 'tautan' => 'https://contoh.test/tes', 'petunjuk' => ''],
            'offline' => ['aktif' => true, 'tanggal' => '2026-12-01', 'jam' => '09:00', 'lokasi' => 'Aula', 'petunjuk' => ''],
        ]);

        $userLulus = User::create([
            'nama_user' => 'calon_tes_lulus_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $pendaftarLulus = Pendaftar::create([
            'id_user' => $userLulus->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Calon Lulus Tes',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);
        $tesSeleksi->pilihMode($pendaftarLulus->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $tesSeleksi->simpanHasil($pendaftarLulus->id_pendaftar, TesSeleksiService::HASIL_LULUS, 80, null, $this->admin->id_user);

        $userBelum = User::create([
            'nama_user' => 'calon_tes_belum_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $pendaftarBelum = Pendaftar::create([
            'id_user' => $userBelum->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Calon Belum Memilih',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/pendaftar');
        $response->assertStatus(200);
        $response->assertSee('Calon Lulus Tes');
        $response->assertSee('Calon Belum Memilih');

        $resFilterLulus = $this->actingAs($this->admin)->get('/admin/pendaftar?tes=lulus');
        $resFilterLulus->assertStatus(200);
        $resFilterLulus->assertSee('Calon Lulus Tes');
        $resFilterLulus->assertDontSee('Calon Belum Memilih');

        $resFilterBelum = $this->actingAs($this->admin)->get('/admin/pendaftar?tes=belum_memilih');
        $resFilterBelum->assertStatus(200);
        $resFilterBelum->assertSee('Calon Belum Memilih');
        $resFilterBelum->assertDontSee('Calon Lulus Tes');
    }

    public function test_halaman_detail_tampilkan_kartu_tes_seleksi(): void
    {
        Storage::fake('local');
        $tesSeleksi = app(TesSeleksiService::class);
        $tesSeleksi->simpanPengaturan([
            'online' => ['aktif' => true, 'tautan' => 'https://contoh.test/tes', 'petunjuk' => ''],
            'offline' => ['aktif' => false, 'tanggal' => '', 'jam' => '', 'lokasi' => '', 'petunjuk' => ''],
        ]);

        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Santri Kartu Tes',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);
        $tesSeleksi->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $tesSeleksi->simpanHasil($pendaftar->id_pendaftar, TesSeleksiService::HASIL_LULUS, 90, 'Catatan tes', $this->admin->id_user);

        $response = $this->actingAs($this->admin)->get("/admin/pendaftar/{$pendaftar->id_pendaftar}");
        $response->assertStatus(200);
        $response->assertSee('Tes Seleksi');
        $response->assertSee('online');
        $response->assertSee('Lulus');
        $response->assertSee('90');
        $response->assertSee('Catatan tes');
        $response->assertSee('Simpan Hasil Tes');
    }

    public function test_pengguna_lain_tidak_bisa_mengakses_tes_orang_lain(): void
    {
        $penggunaLain = User::create([
            'nama_user' => 'calon_lain_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $pendaftarLain = Pendaftar::create([
            'id_user' => $penggunaLain->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => 'Calon Lain',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->calonSantri)->get("/admin/pendaftar/{$pendaftarLain->id_pendaftar}")->assertStatus(403);
    }

    private function beriHasilTesLulus(Pendaftar $pendaftar): void
    {
        Storage::fake('local');
        $tesSeleksi = app(TesSeleksiService::class);
        $tesSeleksi->simpanPengaturan([
            'online' => ['aktif' => true, 'tautan' => 'https://contoh.test/tes', 'petunjuk' => ''],
            'offline' => ['aktif' => false, 'tanggal' => '', 'jam' => '', 'lokasi' => '', 'petunjuk' => ''],
        ]);
        $tesSeleksi->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $tesSeleksi->simpanHasil($pendaftar->id_pendaftar, TesSeleksiService::HASIL_LULUS, 90, null, $this->admin->id_user);
    }
}
