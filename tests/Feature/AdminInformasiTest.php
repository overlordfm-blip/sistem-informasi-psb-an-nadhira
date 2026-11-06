<?php

namespace Tests\Feature;

use App\Models\InformasiPondok;
use App\Models\User;
use Tests\TestCase;

class AdminInformasiTest extends TestCase
{
    protected User $admin;

    protected User $calonSantri;

    protected User $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('peran', User::PERAN_ADMIN)->first()
            ?? User::create([
                'nama_user' => 'admin_test_info',
                'password' => 'admin123',
                'peran' => User::PERAN_ADMIN,
            ]);

        $this->calonSantri = User::where('peran', User::PERAN_CALON_SANTRI)->first()
            ?? User::create([
                'nama_user' => 'calon_test_info',
                'password' => 'santri123',
                'peran' => User::PERAN_CALON_SANTRI,
            ]);

        $this->santri = User::create([
            'nama_user' => 'santri_info_test',
            'password' => 'santri123',
            'peran' => User::PERAN_SANTRI,
        ]);
    }

    public function test_calon_santri_akses_informasi_mendapat_403(): void
    {
        $info = InformasiPondok::first();

        $this->actingAs($this->calonSantri)->get('/admin/informasi')->assertStatus(403);
        $this->actingAs($this->calonSantri)->get('/admin/informasi/tambah')->assertStatus(403);
        $this->actingAs($this->calonSantri)->post('/admin/informasi', [
            'judul' => 'Judul Ilegal',
            'isi' => 'Konten dilarang',
        ])->assertStatus(403);

        if ($info) {
            $this->actingAs($this->calonSantri)->get("/admin/informasi/{$info->id_informasi}/ubah")->assertStatus(403);
            $this->actingAs($this->calonSantri)->put("/admin/informasi/{$info->id_informasi}", [
                'judul' => 'Ubah Ilegal',
                'isi' => 'Konten ilegal',
            ])->assertStatus(403);
            $this->actingAs($this->calonSantri)->delete("/admin/informasi/{$info->id_informasi}")->assertStatus(403);
        }
    }

    public function test_santri_akses_informasi_mendapat_403(): void
    {
        $this->actingAs($this->santri)->get('/admin/informasi')->assertStatus(403);
    }

    public function test_tamu_belum_login_akses_informasi_diarahkan_ke_login(): void
    {
        $this->get('/admin/informasi')->assertRedirect('/login');
    }

    public function test_admin_dapat_melihat_daftar_informasi(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/informasi');

        $response->assertStatus(200);
        $response->assertSee('Kelola Informasi Pondok');
        $response->assertSee('Daftar Informasi Pondok');
    }

    public function test_admin_dapat_melihat_formulir_tambah_informasi(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/informasi/tambah');

        $response->assertStatus(200);
        $response->assertSee('Tambah Informasi Pondok');
    }

    public function test_admin_dapat_menambah_informasi_baru_dengan_id_user_admin_dan_diperbarui_hari_ini(): void
    {
        $today = now()->toDateString();

        $response = $this->actingAs($this->admin)->post('/admin/informasi', [
            'judul' => 'Jadwal Tes Masuk Gelombang 1',
            'isi' => 'Tes potensi akademik dan baca Al-Qur\'an akan diselenggarakan di aula utama pondok.',
        ]);

        $response->assertRedirect('/admin/informasi');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('informasi_pondok', [
            'judul' => 'Jadwal Tes Masuk Gelombang 1',
            'id_user' => $this->admin->id_user,
            'diperbarui' => $today,
        ]);
    }

    public function test_validasi_tambah_informasi_judul_dan_isi_wajib(): void
    {
        $response = $this->actingAs($this->admin)->from('/admin/informasi/tambah')->post('/admin/informasi', []);

        $response->assertRedirect('/admin/informasi/tambah');
        $response->assertSessionHasErrors(['judul', 'isi']);
    }

    public function test_validasi_tambah_informasi_panjang_judul_maksimal_150(): void
    {
        $judulPanjang = str_repeat('A', 151);

        $response = $this->actingAs($this->admin)->from('/admin/informasi/tambah')->post('/admin/informasi', [
            'judul' => $judulPanjang,
            'isi' => 'Konten dengan judul terlalu panjang',
        ]);

        $response->assertRedirect('/admin/informasi/tambah');
        $response->assertSessionHasErrors('judul');
    }

    public function test_admin_dapat_melihat_formulir_ubah_informasi(): void
    {
        $info = InformasiPondok::create([
            'id_user' => $this->admin->id_user,
            'judul' => 'Informasi Uji Form Ubah',
            'isi' => 'Isi awal informasi',
            'diperbarui' => '2026-01-01',
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/informasi/{$info->id_informasi}/ubah");

        $response->assertStatus(200);
        $response->assertSee('Ubah Informasi Pondok');
        $response->assertSee('Informasi Uji Form Ubah');
    }

    public function test_admin_dapat_mengubah_informasi_dan_tanggal_diperbarui_menjadi_hari_ini(): void
    {
        $today = now()->toDateString();

        $info = InformasiPondok::create([
            'id_user' => $this->admin->id_user,
            'judul' => 'Judul Sebelum Direvisi',
            'isi' => 'Konten lama sebelum direvisi.',
            'diperbarui' => '2025-12-01',
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/informasi/{$info->id_informasi}", [
            'judul' => 'Judul Berhasil Direvisi',
            'isi' => 'Konten baru yang sudah diperbarui secara lengkap.',
        ]);

        $response->assertRedirect('/admin/informasi');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseHas('informasi_pondok', [
            'id_informasi' => $info->id_informasi,
            'id_user' => $this->admin->id_user,
            'judul' => 'Judul Berhasil Direvisi',
            'diperbarui' => $today,
        ]);
    }

    public function test_admin_dapat_menghapus_informasi(): void
    {
        $info = InformasiPondok::create([
            'id_user' => $this->admin->id_user,
            'judul' => 'Informasi Segera Dihapus',
            'isi' => 'Konten sementara yang akan dihapus.',
            'diperbarui' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/informasi/{$info->id_informasi}");

        $response->assertRedirect('/admin/informasi');
        $response->assertSessionHas('sukses');

        $this->assertDatabaseMissing('informasi_pondok', [
            'id_informasi' => $info->id_informasi,
        ]);
    }
}
