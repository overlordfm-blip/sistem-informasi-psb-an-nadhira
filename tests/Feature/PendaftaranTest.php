<?php

namespace Tests\Feature;

use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\PendaftaranService;
use App\Services\TesSeleksiService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PendaftaranTest extends TestCase
{
    protected User $admin;

    protected User $santri;

    protected User $calonSantri;

    protected PeriodePendaftaran $periodeAktif;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('peran', User::PERAN_ADMIN)->first()
            ?? User::create([
                'nama_user' => 'admin_pendaftaran_test',
                'password' => 'admin123',
                'peran' => User::PERAN_ADMIN,
            ]);

        $this->santri = User::create([
            'nama_user' => 'santri_pendaftaran_test_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_SANTRI,
        ]);

        $this->calonSantri = User::create([
            'nama_user' => 'calon_pendaftaran_test_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        // Pastikan hanya ada 1 periode aktif berstatus dibuka untuk pengujian
        PeriodePendaftaran::where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
            ->update(['status_periode' => PeriodePendaftaran::STATUS_DITUTUP]);

        $this->periodeAktif = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Uji 2026/2027',
            'tanggal_buka' => now('Asia/Jakarta')->subDays(5)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->addDays(20)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
    }

    public function test_periode_ditutup_menampilkan_pesan_dan_tautan_beranda(): void
    {
        // Tutup semua periode
        PeriodePendaftaran::where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
            ->update(['status_periode' => PeriodePendaftaran::STATUS_DITUTUP]);

        $response = $this->actingAs($this->calonSantri)->get('/pendaftaran');

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran sedang ditutup');
        $response->assertSee(__('pendaftaran.menunggu_gelombang_berikutnya'));
        $response->assertSee(route('beranda'));
    }

    public function test_future_open_period_is_not_available_for_form_quota_or_registration(): void
    {
        $this->periodeAktif->update(['status_periode' => PeriodePendaftaran::STATUS_DITUTUP]);
        $periodeMasaDepan = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Masa Depan Tersedia',
            'tanggal_buka' => now('Asia/Jakarta')->addDay()->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->addDays(19)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);

        $this->actingAs($this->calonSantri)->get('/pendaftaran')
            ->assertOk()
            ->assertSee('Pendaftaran sedang ditutup');
        $this->assertSame(0, app(PendaftaranService::class)->sisaKuota($periodeMasaDepan));

        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Calon Masa Depan',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Masa Depan',
            'no_telepon' => '081234567890',
        ])->assertRedirect('/pendaftaran')->assertSessionHas('galat');

        $this->assertDatabaseMissing('pendaftar', ['id_user' => $this->calonSantri->id_user]);
    }

    public function test_halaman_pendaftaran_sedang_ditutup_memuat_kalimat_silakan_menunggu_gelombang_pendaftaran_berikutnya(): void
    {
        PeriodePendaftaran::where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
            ->update(['status_periode' => PeriodePendaftaran::STATUS_DITUTUP]);

        $response = $this->actingAs($this->calonSantri)->get('/pendaftaran');

        $response->assertStatus(200);
        $response->assertSee('Pendaftaran sedang ditutup');
        $response->assertSee('Silakan menunggu gelombang pendaftaran berikutnya');
    }

    public function test_calon_santri_sudah_terdaftar_dialihkan_ke_status_pendaftaran(): void
    {
        // Buat data pendaftar untuk calon santri
        Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Santri Terdaftar',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $response = $this->actingAs($this->calonSantri)->get('/pendaftaran');

        $response->assertRedirect('/status-pendaftaran');
        $response->assertSessionHas('info', __('pendaftaran.sudah_mendaftar'));
    }

    public function test_calon_santri_sudah_terdaftar_melihat_pesan_flash_menunggu_gelombang_berikutnya(): void
    {
        Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Santri Flash Info',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        // Akses /pendaftaran dan ikuti pengalihan ke /status-pendaftaran
        $response = $this->actingAs($this->calonSantri)->get('/pendaftaran');
        $response->assertRedirect('/status-pendaftaran');

        $responseRedirect = $this->actingAs($this->calonSantri)->get('/status-pendaftaran');
        $responseRedirect->assertStatus(200);
        $responseRedirect->assertSee(__('pendaftaran.sudah_mendaftar'));
    }

    public function test_status_pendaftaran_ditolak_menampilkan_pesan_tetap_gelombang_berikutnya(): void
    {
        Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Santri Ditolak',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        $response = $this->actingAs($this->calonSantri)->get('/status-pendaftaran');

        $response->assertStatus(200);
        $response->assertSee(__('pendaftaran.ditolak'));
    }

    public function test_data_valid_tersimpan_diproses_dengan_id_periode_benar(): void
    {
        $response = $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Ahmad Fauzi',
            'tanggal_lahir' => '2011-04-15',
            'alamat' => 'Jl. Kalibeber KM 3, Wonosobo',
            'nama_wali' => 'Bapak Fauzi',
            'no_telepon' => '081234567890',
        ]);

        $response->assertRedirect('/status-pendaftaran');
        $response->assertSessionHas('sukses', 'Pendaftaran berhasil');

        $this->assertDatabaseHas('pendaftar', [
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Ahmad Fauzi',
            'tanggal_lahir' => '2011-04-15',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);
    }

    public function test_validasi_gagal_nama_kosong(): void
    {
        $response = $this->actingAs($this->calonSantri)->from('/pendaftaran')->post('/pendaftaran', [
            'nama_lengkap' => '',
            'tanggal_lahir' => '2011-04-15',
            'alamat' => 'Alamat Lengkap',
            'nama_wali' => 'Nama Wali',
            'no_telepon' => '081234567890',
        ]);

        $response->assertRedirect('/pendaftaran');
        $response->assertSessionHasErrors('nama_lengkap');
    }

    public function test_validasi_gagal_tanggal_lahir_hari_ini_atau_masa_depan(): void
    {
        // Tanggal masa depan
        $responseMasaDepan = $this->actingAs($this->calonSantri)->from('/pendaftaran')->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Baru',
            'tanggal_lahir' => now('Asia/Jakarta')->addDays(5)->toDateString(),
            'alamat' => 'Alamat Lengkap',
            'nama_wali' => 'Nama Wali',
            'no_telepon' => '081234567890',
        ]);
        $responseMasaDepan->assertRedirect('/pendaftaran');
        $responseMasaDepan->assertSessionHasErrors('tanggal_lahir');

        // Tanggal hari ini (wajib before:today)
        $responseHariIni = $this->actingAs($this->calonSantri)->from('/pendaftaran')->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Baru',
            'tanggal_lahir' => now('Asia/Jakarta')->toDateString(),
            'alamat' => 'Alamat Lengkap',
            'nama_wali' => 'Nama Wali',
            'no_telepon' => '081234567890',
        ]);
        $responseHariIni->assertRedirect('/pendaftaran');
        $responseHariIni->assertSessionHasErrors('tanggal_lahir');
    }

    public function test_validasi_gagal_telepon_berisi_huruf(): void
    {
        $response = $this->actingAs($this->calonSantri)->from('/pendaftaran')->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Baru',
            'tanggal_lahir' => '2011-04-15',
            'alamat' => 'Alamat Lengkap',
            'nama_wali' => 'Nama Wali',
            'no_telepon' => '081234abcde5',
        ]);

        $response->assertRedirect('/pendaftaran');
        $response->assertSessionHasErrors('no_telepon');
    }

    public function test_validasi_gagal_telepon_9_digit(): void
    {
        // 9 digit (081234567)
        $response = $this->actingAs($this->calonSantri)->from('/pendaftaran')->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Baru',
            'tanggal_lahir' => '2011-04-15',
            'alamat' => 'Alamat Lengkap',
            'nama_wali' => 'Nama Wali',
            'no_telepon' => '081234567',
        ]);

        $response->assertRedirect('/pendaftaran');
        $response->assertSessionHasErrors('no_telepon');
    }

    public function test_validasi_gagal_telepon_16_digit(): void
    {
        // 16 digit (0812345678901234)
        $response = $this->actingAs($this->calonSantri)->from('/pendaftaran')->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Baru',
            'tanggal_lahir' => '2011-04-15',
            'alamat' => 'Alamat Lengkap',
            'nama_wali' => 'Nama Wali',
            'no_telepon' => '0812345678901234',
        ]);

        $response->assertRedirect('/pendaftaran');
        $response->assertSessionHasErrors('no_telepon');
    }

    public function test_input_lama_dipertahankan_saat_validasi_gagal(): void
    {
        $response = $this->actingAs($this->calonSantri)->from('/pendaftaran')->post('/pendaftaran', [
            'nama_lengkap' => '', // Memicu galat validasi
            'tanggal_lahir' => '2011-04-15',
            'alamat' => 'Jl. Pahlawan No. 45 Wonosobo',
            'nama_wali' => 'Bapak Hartono',
            'no_telepon' => '081234567890',
        ]);

        $response->assertRedirect('/pendaftaran');
        $response->assertSessionHasErrors('nama_lengkap');
        $response->assertSessionHas('_old_input.alamat', 'Jl. Pahlawan No. 45 Wonosobo');
        $response->assertSessionHas('_old_input.nama_wali', 'Bapak Hartono');
        $response->assertSessionHas('_old_input.no_telepon', '081234567890');
        $this->assertEquals('Jl. Pahlawan No. 45 Wonosobo', session()->getOldInput('alamat'));
        $this->assertEquals('Bapak Hartono', session()->getOldInput('nama_wali'));
        $this->assertEquals('081234567890', session()->getOldInput('no_telepon'));
    }

    public function test_post_kedua_ditolak_tidak_ada_pendaftaran_ganda(): void
    {
        // Pendaftaran pertama sukses
        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Tunggal',
            'tanggal_lahir' => '2010-02-20',
            'alamat' => 'Mojotengah, Wonosobo',
            'nama_wali' => 'Ibu Santri',
            'no_telepon' => '081298765432',
        ])->assertRedirect('/status-pendaftaran');

        // Pendaftaran kedua dengan akun yang sama
        $responseKedua = $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Duplikat',
            'tanggal_lahir' => '2010-02-20',
            'alamat' => 'Mojotengah, Wonosobo',
            'nama_wali' => 'Ibu Santri',
            'no_telepon' => '081298765432',
        ]);

        $responseKedua->assertRedirect('/status-pendaftaran');
        $responseKedua->assertSessionHas('galat');

        // Pastikan hanya ada 1 pendaftar untuk user ini
        $jumlahPendaftar = Pendaftar::where('id_user', $this->calonSantri->id_user)->count();
        $this->assertEquals(1, $jumlahPendaftar);
    }

    public function test_post_saat_periode_ditutup_ditolak(): void
    {
        // Tutup semua periode
        PeriodePendaftaran::where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
            ->update(['status_periode' => PeriodePendaftaran::STATUS_DITUTUP]);

        $response = $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Calon Santri Tertolak',
            'tanggal_lahir' => '2010-06-10',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Murid',
            'no_telepon' => '081234567890',
        ]);

        $response->assertRedirect('/pendaftaran');
        $response->assertSessionHas('galat');

        $this->assertDatabaseMissing('pendaftar', [
            'id_user' => $this->calonSantri->id_user,
        ]);
    }

    public function test_kuota_penuh_menolak_formulir_get_dan_post(): void
    {
        config(['pondok.kuota' => 1]);
        $userTerdaftar = User::create([
            'nama_user' => 'sudah_isi_kuota_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        Pendaftar::create([
            'id_user' => $userTerdaftar->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Pendaftar Pengisi Kuota',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->calonSantri)->get('/pendaftaran')
            ->assertOk()
            ->assertSee('Kuota pendaftaran periode ini telah penuh')
            ->assertDontSee('Kirim Pendaftaran');

        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Calon Tertahan Kuota',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567893',
        ])->assertRedirect('/status-pendaftaran')
            ->assertSessionHas('galat', 'Kuota pendaftaran periode ini telah penuh');

        $this->assertDatabaseMissing('pendaftar', ['id_user' => $this->calonSantri->id_user]);
    }

    public function test_dua_pendaftar_berurutan_tidak_melampaui_kuota_satu(): void
    {
        config(['pondok.kuota' => 1]);
        $calonKedua = User::create([
            'nama_user' => 'calon_balapan_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $data = [
            'nama_lengkap' => 'Calon Kuota Balapan',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567893',
        ];

        $this->actingAs($this->calonSantri)->post('/pendaftaran', $data)
            ->assertRedirect('/status-pendaftaran')
            ->assertSessionHas('sukses', 'Pendaftaran berhasil');
        $this->actingAs($calonKedua)->post('/pendaftaran', $data)
            ->assertRedirect('/status-pendaftaran')
            ->assertSessionHas('galat', 'Kuota pendaftaran periode ini telah penuh');

        $this->assertSame(1, Pendaftar::where('id_periode', $this->periodeAktif->id_periode)->count());
    }

    public function test_pendaftar_ulang_dihitung_dalam_kuota_periode_baru(): void
    {
        config(['pondok.kuota' => 1]);
        $periodeLama = PeriodePendaftaran::create([
            'nama_periode' => 'Periode Lama Kuota',
            'tanggal_buka' => now()->subMonths(3)->toDateString(),
            'tanggal_tutup' => now()->subMonth()->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);
        $pendaftarDitolak = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $periodeLama->id_periode,
            'nama_lengkap' => 'Ditolak Periode Lama',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Alamat Lama',
            'nama_wali' => 'Wali Lama',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);
        $userPengisi = User::create([
            'nama_user' => 'pengisi_kuota_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        Pendaftar::create([
            'id_user' => $userPengisi->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Pengisi Kuota Baru',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Alamat',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567894',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Data Daftar Ulang',
            'tanggal_lahir' => '2010-02-02',
            'alamat' => 'Alamat Baru',
            'nama_wali' => 'Wali Baru',
            'no_telepon' => '081234567895',
        ])->assertSessionHas('galat', 'Kuota pendaftaran periode ini telah penuh');

        $this->assertSame(Pendaftar::STATUS_DITOLAK, $pendaftarDitolak->fresh()->status_daftar);
        $this->assertSame($periodeLama->id_periode, $pendaftarDitolak->fresh()->id_periode);
    }

    public function test_admin_dan_santri_mendapat_403_saat_akses_pendaftaran(): void
    {
        // Admin
        $this->actingAs($this->admin)->get('/pendaftaran')->assertStatus(403);
        $this->actingAs($this->admin)->post('/pendaftaran', [
            'nama_lengkap' => 'Admin Daftar',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Admin',
            'no_telepon' => '081234567890',
        ])->assertStatus(403);
        $this->actingAs($this->admin)->get('/status-pendaftaran')->assertStatus(403);

        // Santri
        $this->actingAs($this->santri)->get('/pendaftaran')->assertStatus(403);
        $this->actingAs($this->santri)->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Daftar',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
        ])->assertStatus(403);
        $this->actingAs($this->santri)->get('/status-pendaftaran')->assertStatus(403);
    }

    public function test_status_pendaftaran_hanya_menampilkan_data_sendiri(): void
    {
        $calonA = $this->calonSantri;
        $calonB = User::create([
            'nama_user' => 'calon_b_'.uniqid(),
            'password' => 'santri123',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        Pendaftar::create([
            'id_user' => $calonA->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Santri Khusus User A',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Alamat Rahasia A',
            'nama_wali' => 'Wali A',
            'no_telepon' => '081211111111',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        Pendaftar::create([
            'id_user' => $calonB->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Santri Khusus User B',
            'tanggal_lahir' => '2010-02-02',
            'alamat' => 'Alamat Rahasia B',
            'nama_wali' => 'Wali B',
            'no_telepon' => '081222222222',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        // Calon A hanya melihat data dirinya
        $responseA = $this->actingAs($calonA)->get('/status-pendaftaran');
        $responseA->assertStatus(200);
        $responseA->assertSee('Santri Khusus User A');
        $responseA->assertSee('Alamat Rahasia A');
        $responseA->assertDontSee('Santri Khusus User B');
        $responseA->assertDontSee('Alamat Rahasia B');

        // Calon B hanya melihat data dirinya
        $responseB = $this->actingAs($calonB)->get('/status-pendaftaran');
        $responseB->assertStatus(200);
        $responseB->assertSee('Santri Khusus User B');
        $responseB->assertSee('Alamat Rahasia B');
        $responseB->assertDontSee('Santri Khusus User A');
        $responseB->assertDontSee('Alamat Rahasia A');
    }

    public function test_status_pendaftaran_menampilkan_tautan_ke_tagihan_bila_diterima(): void
    {
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Santri Diterima Bersyarat',
            'tanggal_lahir' => '2010-03-15',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Santri',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);

        Tagihan::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_PENDAFTARAN,
            'nominal' => 1250000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        $response = $this->actingAs($this->calonSantri)->get('/status-pendaftaran');

        $response->assertStatus(200);
        $response->assertSee('DITERIMA');
        $response->assertSee('Belum Dibayar');
        $response->assertSee('Lihat Tagihan');
        $response->assertSee(route('tagihan'));
    }

    // -----------------------------------------------------------------------
    // Aturan Baru: Kelayakan Calon Santri & Pendaftaran Ulang Pasca Penolakan
    // -----------------------------------------------------------------------

    public function test_daftar_ulang_menghapus_catatan_penolakan(): void
    {
        Storage::fake('local');

        // 1. Buat periode lama yang sudah ditutup
        $periodeLama = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Lama 2025',
            'tanggal_buka' => now('Asia/Jakarta')->subMonths(3)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->subMonth()->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        // 2. Buat pendaftar berstatus ditolak di periode lama
        $pendaftarLama = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $periodeLama->id_periode,
            'nama_lengkap' => 'Nama Santri Lama',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Alamat Lama Wonosobo',
            'nama_wali' => 'Wali Lama',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        $idPendaftarAwal = $pendaftarLama->id_pendaftar;
        $pathCatatan = "catatan/pendaftar-{$idPendaftarAwal}.txt";
        Storage::disk('local')->put($pathCatatan, 'Catatan dari penolakan sebelumnya.');
        $tesSeleksi = app(TesSeleksiService::class);
        $tesSeleksi->simpanPengaturan([
            'online' => ['aktif' => true, 'tautan' => 'https://contoh.test', 'petunjuk' => ''],
            'offline' => ['aktif' => false, 'tanggal' => '', 'jam' => '', 'lokasi' => '', 'petunjuk' => ''],
        ]);
        $tesSeleksi->pilihMode($idPendaftarAwal, TesSeleksiService::MODE_ONLINE);
        $totalPendaftarAwal = Pendaftar::count();

        // 3. Calon santri membuka formulir /pendaftaran saat ada periode baru dibuka
        $responseForm = $this->actingAs($this->calonSantri)->get('/pendaftaran');
        $responseForm->assertStatus(200);
        $responseForm->assertSee('Formulir Pendaftaran');
        // Pastikan nilai awal formulir terisi dari data lama
        $responseForm->assertSee('Nama Santri Lama');
        $responseForm->assertSee('Wali Lama');

        // 4. Kirim pembaruan pendaftaran dengan data baru
        $responsePost = $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Nama Santri Diperbarui',
            'tanggal_lahir' => '2010-05-20',
            'alamat' => 'Alamat Baru Wonosobo',
            'nama_wali' => 'Wali Baru',
            'no_telepon' => '081299998888',
        ]);

        $responsePost->assertRedirect('/status-pendaftaran');
        $responsePost->assertSessionHas('sukses', 'Pendaftaran berhasil');

        // 5. Verifikasi jumlah baris pendaftar TIDAK bertambah
        $this->assertEquals($totalPendaftarAwal, Pendaftar::count());

        // 6. Verifikasi baris pendaftar yang sama diperbarui (id_pendaftar tetap sama)
        $pendaftarTerbaru = Pendaftar::where('id_user', $this->calonSantri->id_user)->first();
        $this->assertNotNull($pendaftarTerbaru);
        $this->assertEquals($idPendaftarAwal, $pendaftarTerbaru->id_pendaftar);
        $this->assertEquals($this->periodeAktif->id_periode, $pendaftarTerbaru->id_periode);
        $this->assertEquals(Pendaftar::STATUS_DIPROSES, $pendaftarTerbaru->status_daftar);
        $this->assertEquals('Nama Santri Diperbarui', $pendaftarTerbaru->nama_lengkap);
        $this->assertEquals('081299998888', $pendaftarTerbaru->no_telepon);
        Storage::disk('local')->assertMissing($pathCatatan);
        Storage::disk('local')->assertMissing("tes/pendaftar-{$idPendaftarAwal}.json");

        // Tidak boleh ada tagihan yang terbuat
        $this->assertEquals(0, Tagihan::where('id_pendaftar', $pendaftarTerbaru->id_pendaftar)->count());
    }

    public function test_ditolak_di_periode_yang_sedang_dibuka_tidak_bisa_mendaftar(): void
    {
        // Pendaftar berstatus ditolak pada periode yang sedang dibuka
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Santri Ditolak Gelombang Ini',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        // Akses GET /pendaftaran diarahkan ke status pendaftaran dengan pesan khusus
        $responseGet = $this->actingAs($this->calonSantri)->get('/pendaftaran');
        $responseGet->assertRedirect('/status-pendaftaran');
        $responseGet->assertSessionHas('info', 'Pendaftaran Anda pada periode ini tidak diterima. Silakan menunggu periode berikutnya.');

        // Kirim POST /pendaftaran ditolak
        $responsePost = $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Coba Daftar Lagi',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
        ]);

        $responsePost->assertRedirect('/status-pendaftaran');
        $responsePost->assertSessionHas('galat', 'Pendaftaran Anda pada periode ini tidak diterima. Silakan menunggu periode berikutnya.');

        // Status pendaftar tetap DITOLAK
        $this->assertEquals(Pendaftar::STATUS_DITOLAK, $pendaftar->fresh()->status_daftar);
    }

    public function test_pendaftar_diproses_dan_diterima_tidak_bisa_mendaftar(): void
    {
        // 1. Uji status diproses
        $pendaftarDiproses = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $this->periodeAktif->id_periode,
            'nama_lengkap' => 'Santri Diproses',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        $this->actingAs($this->calonSantri)->get('/pendaftaran')
            ->assertRedirect('/status-pendaftaran')
            ->assertSessionHas('info', __('pendaftaran.sudah_mendaftar'));

        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Coba Lagi',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
        ])->assertRedirect('/status-pendaftaran')
            ->assertSessionHas('galat');

        // 2. Uji status diterima
        $pendaftarDiproses->update(['status_daftar' => Pendaftar::STATUS_DITERIMA]);

        $this->actingAs($this->calonSantri)->get('/pendaftaran')
            ->assertRedirect('/status-pendaftaran')
            ->assertSessionHas('info', __('pendaftaran.sudah_mendaftar'));

        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Coba Lagi Diterima',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
        ])->assertRedirect('/status-pendaftaran')
            ->assertSessionHas('galat');
    }

    public function test_tautan_daftar_kembali_tampil_pada_status_pendaftaran_bila_ditolak_dan_ada_periode_berbeda(): void
    {
        $periodeLama = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang 1 2026',
            'tanggal_buka' => now('Asia/Jakarta')->subMonths(2)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->subMonth()->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        // Pendaftar ditolak di periode lama
        Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $periodeLama->id_periode,
            'nama_lengkap' => 'Santri Ditolak Gel 1',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        // Periode dibuka saat ini adalah $this->periodeAktif ('Gelombang Uji 2026/2027')
        $response = $this->actingAs($this->calonSantri)->get('/status-pendaftaran');
        $response->assertStatus(200);
        $response->assertSee('Daftar kembali pada '.$this->periodeAktif->nama_periode);
        $response->assertSee(route('pendaftaran'));
    }

    public function test_terima_pendaftar_yang_mendaftar_ulang_membuat_tepat_satu_tagihan(): void
    {
        $periodeLama = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Awal',
            'tanggal_buka' => now('Asia/Jakarta')->subMonths(3)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->subMonths(2)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        // Awalnya ditolak di periode lama
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $periodeLama->id_periode,
            'nama_lengkap' => 'Santri Re-register',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        // Mendaftar ulang di periode baru
        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Re-register Baru',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo Baru',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
        ]);

        $this->assertEquals(Pendaftar::STATUS_DIPROSES, $pendaftar->fresh()->status_daftar);
        $this->assertEquals($this->periodeAktif->id_periode, $pendaftar->fresh()->id_periode);

        // Tes seleksi harus lulus sebelum dapat diterima
        app(TesSeleksiService::class)->simpanPengaturan([
            'online' => ['aktif' => true, 'tautan' => 'https://contoh.test/tes', 'petunjuk' => ''],
            'offline' => ['aktif' => false, 'tanggal' => '', 'jam' => '', 'lokasi' => '', 'petunjuk' => ''],
        ]);
        app(TesSeleksiService::class)->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        app(TesSeleksiService::class)->simpanHasil(
            $pendaftar->id_pendaftar,
            TesSeleksiService::HASIL_LULUS,
            90,
            null,
            $this->admin->id_user
        );

        // Admin menerima pendaftar ini
        $responseTerima = $this->actingAs($this->admin)->post("/admin/pendaftar/{$pendaftar->id_pendaftar}/terima");
        $responseTerima->assertRedirect();

        $this->assertEquals(Pendaftar::STATUS_DITERIMA, $pendaftar->fresh()->status_daftar);

        // Harus membuat tepat satu tagihan pendaftaran
        $tagihanCount = Tagihan::where('id_pendaftar', $pendaftar->id_pendaftar)->count();
        $this->assertEquals(1, $tagihanCount);

        $tagihan = Tagihan::where('id_pendaftar', $pendaftar->id_pendaftar)->first();
        $this->assertEquals(Tagihan::JENIS_PENDAFTARAN, $tagihan->jenis_tagihan);
        $this->assertEquals(config('pondok.biaya_pendaftaran'), (int) $tagihan->nominal);
        $this->assertEquals(Tagihan::STATUS_BELUM_DIBAYAR, $tagihan->status_tagihan);
    }

    public function test_periode_lama_bisa_dihapus_setelah_pendaftarnya_pindah(): void
    {
        $periodeLama = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Usang 2024',
            'tanggal_buka' => now('Asia/Jakarta')->subMonths(6)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->subMonths(5)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        // Daftarkan calon santri ke periode lama dengan status ditolak
        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $periodeLama->id_periode,
            'nama_lengkap' => 'Santri Eks Periode Lama',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        // Sebelum pindah: periode lama punya pendaftar sehingga ditolak saat dihapus
        $this->actingAs($this->admin)->delete("/admin/periode/{$periodeLama->id_periode}")
            ->assertRedirect('/admin/periode')
            ->assertSessionHas('galat');
        $this->assertDatabaseHas('periode_pendaftaran', ['id_periode' => $periodeLama->id_periode]);

        // Calon santri mendaftar ulang di periode baru ($this->periodeAktif)
        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Pindah Periode',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
        ])->assertRedirect('/status-pendaftaran');

        // Pastikan periode lama kini 0 pendaftar karena pendaftar sudah pindah id_periode
        $this->assertEquals(0, $periodeLama->pendaftar()->count());

        // Sekarang periode lama berhasil dihapus oleh admin
        $responseHapus = $this->actingAs($this->admin)->delete("/admin/periode/{$periodeLama->id_periode}");
        $responseHapus->assertRedirect('/admin/periode');
        $responseHapus->assertSessionHas('sukses');

        $this->assertDatabaseMissing('periode_pendaftaran', [
            'id_periode' => $periodeLama->id_periode,
        ]);
    }

    public function test_daftar_ulang_menghapus_berkas_tes_lama(): void
    {
        Storage::fake('local');
        $periodeLama = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Lama',
            'tanggal_buka' => now('Asia/Jakarta')->subMonths(3)->toDateString(),
            'tanggal_tutup' => now('Asia/Jakarta')->subMonths(2)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
        ]);

        $pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $periodeLama->id_periode,
            'nama_lengkap' => 'Santri Tes Daftar Ulang',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        // Simpan berkas tes lama untuk pendaftar ini
        $tesSeleksi = app(TesSeleksiService::class);
        $tesSeleksi->simpanPengaturan([
            'online' => ['aktif' => true, 'tautan' => 'https://contoh.test/tes', 'petunjuk' => ''],
            'offline' => ['aktif' => false, 'tanggal' => '', 'jam' => '', 'lokasi' => '', 'petunjuk' => ''],
        ]);
        $tesSeleksi->pilihMode($pendaftar->id_pendaftar, TesSeleksiService::MODE_ONLINE);
        $tesSeleksi->simpanHasil($pendaftar->id_pendaftar, TesSeleksiService::HASIL_LULUS, 90, 'Lulus lama', $this->admin->id_user);

        $pathTes = "tes/pendaftar-{$pendaftar->id_pendaftar}.json";
        Storage::disk('local')->assertExists($pathTes);
        $this->assertTrue(Storage::disk('local')->exists($pathTes), 'Berkas tes harus ada sebelum daftar ulang.');

        // Mendaftar ulang di periode aktif
        $this->actingAs($this->calonSantri)->post('/pendaftaran', [
            'nama_lengkap' => 'Santri Tes Daftar Ulang Baru',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo Baru',
            'nama_wali' => 'Wali Baru',
            'no_telepon' => '081234567890',
        ])->assertRedirect('/status-pendaftaran');

        // Berkas tes harus dihapus setelah daftar ulang
        Storage::disk('local')->assertMissing($pathTes);
        $this->assertFalse(Storage::disk('local')->exists($pathTes), 'Berkas tes harus dihapus saat pendaftar daftar ulang.');
        $this->assertEquals(Pendaftar::STATUS_DIPROSES, $pendaftar->fresh()->status_daftar);
    }
}
