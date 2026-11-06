<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\Tagihan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardPembayaranTest extends TestCase
{
    protected User $admin;

    protected User $calonSantri;

    protected User $santri;

    protected PeriodePendaftaran $periode;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-30 10:00:00', 'Asia/Jakarta');

        $this->admin = User::where('peran', User::PERAN_ADMIN)->first()
            ?? User::create([
                'nama_user' => 'admin_db_test_'.uniqid(),
                'password' => 'pass1234',
                'peran' => User::PERAN_ADMIN,
            ]);

        $this->calonSantri = User::create([
            'nama_user' => 'calon_db_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        $this->santri = User::create([
            'nama_user' => 'santri_db_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_SANTRI,
        ]);

        $this->periode = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Uji Dashboard',
            'tanggal_buka' => now()->subDays(5)->toDateString(),
            'tanggal_tutup' => now()->addDays(10)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);
    }

    // ── Pembantu ────────────────────────────────────────────────────────────

    private function buatPendaftar(User $user, string $nama, string $status = 'diproses'): Pendaftar
    {
        return Pendaftar::create([
            'id_user' => $user->id_user,
            'id_periode' => $this->periode->id_periode,
            'nama_lengkap' => $nama,
            'tanggal_lahir' => '2008-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali '.$nama,
            'no_telepon' => '081234567890',
            'status_daftar' => $status,
        ]);
    }

    private function buatPembayaran(Pendaftar $pendaftar, string $status = 'menunggu', string $jenis = 'pendaftaran'): Pembayaran
    {
        $tagihan = Tagihan::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'jenis_tagihan' => $jenis,
            'nominal' => 500000,
            'status_tagihan' => $status === 'berhasil' ? Tagihan::STATUS_LUNAS : Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        return Pembayaran::create([
            'id_tagihan' => $tagihan->id_tagihan,
            'jumlah_bayar' => 500000,
            'status_bayar' => $status,
            'tanggal_bayar' => $status === 'berhasil' ? now()->toDateString() : null,
        ]);
    }

    // =========================================================================
    // DASHBOARD — akses peran
    // =========================================================================

    public function test_calon_santri_mendapat_403_di_dashboard_admin(): void
    {
        $this->actingAs($this->calonSantri)
            ->get('/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_santri_mendapat_403_di_dashboard_admin(): void
    {
        $this->actingAs($this->santri)
            ->get('/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_tamu_diarahkan_ke_login_untuk_dashboard_admin(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_selects_available_period_and_keeps_future_open_period_scheduled(): void
    {
        Carbon::setTestNow('2026-11-01 10:00:00');

        try {
            $this->periode->update([
                'tanggal_buka' => '2026-11-01',
                'tanggal_tutup' => '2026-11-05',
                'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
            ]);
            $periodeTerjadwal = PeriodePendaftaran::create([
                'nama_periode' => 'Gelombang Dashboard Mendatang',
                'tanggal_buka' => '2026-11-06',
                'tanggal_tutup' => '2026-11-12',
                'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
            ]);

            $response = $this->actingAs($this->admin)->get('/admin/dashboard');

            $response->assertOk()->assertSee('Pendaftaran dibuka sampai 05 November 2026');
            $this->assertSame($this->periode->id_periode, $response->viewData('periodeTersedia')->id_periode);
            $this->assertSame($periodeTerjadwal->id_periode, $response->viewData('periodeTerjadwal')->id_periode);
            $this->assertSame(PeriodePendaftaran::STATUS_DIBUKA, $periodeTerjadwal->fresh()->status_periode);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dashboard_announces_scheduled_period_when_none_is_available(): void
    {
        Carbon::setTestNow('2026-11-01 10:00:00', 'Asia/Jakarta');

        try {
            $this->periode->update(['status_periode' => PeriodePendaftaran::STATUS_DITUTUP]);
            $periodeTerjadwal = PeriodePendaftaran::create([
                'nama_periode' => 'Gelombang Dashboard Dijadwalkan',
                'tanggal_buka' => now()->addDay()->toDateString(),
                'tanggal_tutup' => now()->addDays(11)->toDateString(),
                'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
            ]);

            $response = $this->actingAs($this->admin)->get('/admin/dashboard');

            $response->assertOk()->assertSee('Pendaftaran akan dibuka pada 02 November 2026');
            $this->assertNull($response->viewData('periodeTersedia'));
            $this->assertSame($periodeTerjadwal->id_periode, $response->viewData('periodeTerjadwal')->id_periode);
        } finally {
            Carbon::setTestNow();
        }
    }

    // =========================================================================
    // DASHBOARD — angka kartu benar
    // =========================================================================

    public function test_dashboard_menampilkan_angka_statistik_pendaftar_yang_benar(): void
    {
        // Catat jumlah awal agar delta tidak bergantung data lama
        $baseTotal = Pendaftar::count();
        $baseDiproses = Pendaftar::where('status_daftar', 'diproses')->count();
        $baseDiterima = Pendaftar::where('status_daftar', 'diterima')->count();
        $baseDitolak = Pendaftar::where('status_daftar', 'ditolak')->count();

        $u1 = User::create(['nama_user' => 'stat1_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
        $u2 = User::create(['nama_user' => 'stat2_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
        $u3 = User::create(['nama_user' => 'stat3_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);

        $this->buatPendaftar($u1, 'Diproses Satu', 'diproses');
        $this->buatPendaftar($u2, 'Diterima Satu', 'diterima');
        $this->buatPendaftar($u3, 'Ditolak Satu', 'ditolak');

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertStatus(200);

        $data = $response->viewData('totalPendaftar');
        $this->assertEquals($baseTotal + 3, $data);
        $this->assertEquals($baseDiproses + 1, $response->viewData('diproses'));
        $this->assertEquals($baseDiterima + 1, $response->viewData('diterima'));
        $this->assertEquals($baseDitolak + 1, $response->viewData('ditolak'));
    }

    // =========================================================================
    // DASHBOARD — daftar terbaru berurutan
    // =========================================================================

    public function test_dashboard_pendaftar_terbaru_diurutkan_id_menurun_dan_maks_lima(): void
    {
        $users = [];
        for ($i = 1; $i <= 6; $i++) {
            $u = User::create(['nama_user' => "ordr_p_{$i}_".uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
            $this->buatPendaftar($u, "Pendaftar Urut {$i}");
            $users[] = $u;
        }

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertStatus(200);

        $terbaru = $response->viewData('pendaftarTerbaru');
        $this->assertCount(5, $terbaru);

        // Urutan menurun: id_pendaftar item pertama > item kedua
        $this->assertGreaterThan($terbaru[1]->id_pendaftar, $terbaru[0]->id_pendaftar);
    }

    public function test_dashboard_pembayaran_terbaru_diurutkan_id_menurun_dan_maks_lima(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $u = User::create(['nama_user' => "ordr_b_{$i}_".uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
            $p = $this->buatPendaftar($u, "Bayar Urut {$i}", 'diterima');
            $this->buatPembayaran($p);
        }

        $response = $this->actingAs($this->admin)->get('/admin/dashboard');
        $response->assertStatus(200);

        $terbaru = $response->viewData('pembayaranTerbaru');
        $this->assertCount(5, $terbaru);
        $this->assertGreaterThan($terbaru[1]->id_pembayaran, $terbaru[0]->id_pembayaran);
    }

    // =========================================================================
    // DASHBOARD — tidak ada N+1 (jumlah query tidak bertambah seiring data)
    // =========================================================================

    public function test_dashboard_tidak_ada_query_n_plus_1(): void
    {
        // Siapkan 5 pembayaran untuk pengukuran pertama
        for ($i = 0; $i < 5; $i++) {
            $u = User::create(['nama_user' => "n1_{$i}_".uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
            $p = $this->buatPendaftar($u, "N+1 Test {$i}", 'diterima');
            $this->buatPembayaran($p);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertStatus(200);
        $queryCount5 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Tambah 5 pembayaran lagi (total 10, tapi dashboard selalu limit 5)
        for ($i = 5; $i < 10; $i++) {
            $u = User::create(['nama_user' => "n1_{$i}_".uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
            $p = $this->buatPendaftar($u, "N+1 Extra {$i}", 'diterima');
            $this->buatPembayaran($p);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin)->get('/admin/dashboard')->assertStatus(200);
        $queryCount10 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Dengan eager loading, penambahan data tidak boleh menggandakan jumlah query.
        // Jika terjadi N+1, queryCount10 akan tumbuh proporsional terhadap jumlah record.
        // Toleransi: selisih maksimal 3 query (overhead framework/sesi yang tidak dapat dihindari).
        $this->assertLessThanOrEqual(
            $queryCount5 + 3,
            $queryCount10,
            "Jumlah query tumbuh dari {$queryCount5} menjadi {$queryCount10} saat data bertambah — kemungkinan ada N+1."
        );
    }

    // =========================================================================
    // DATA PEMBAYARAN — akses peran
    // =========================================================================

    public function test_calon_santri_mendapat_403_di_data_pembayaran(): void
    {
        $this->actingAs($this->calonSantri)
            ->get('/admin/pembayaran')
            ->assertStatus(403);
    }

    public function test_santri_mendapat_403_di_data_pembayaran(): void
    {
        $this->actingAs($this->santri)
            ->get('/admin/pembayaran')
            ->assertStatus(403);
    }

    public function test_tamu_diarahkan_ke_login_untuk_data_pembayaran(): void
    {
        $this->get('/admin/pembayaran')
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // DATA PEMBAYARAN — fungsional
    // =========================================================================

    public function test_admin_dapat_melihat_halaman_data_pembayaran(): void
    {
        $pendaftar = $this->buatPendaftar($this->calonSantri, 'Pendaftar Bayar Bisa', 'diterima');
        $this->buatPembayaran($pendaftar, 'berhasil');

        $response = $this->actingAs($this->admin)->get('/admin/pembayaran');
        $response->assertStatus(200);
        $response->assertSee('Data Pembayaran');
        $response->assertSee('Pendaftar Bayar Bisa');
    }

    public function test_pencarian_nama_pendaftar_pada_data_pembayaran(): void
    {
        $u1 = User::create(['nama_user' => 'cari1_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
        $u2 = User::create(['nama_user' => 'cari2_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);

        $p1 = $this->buatPendaftar($u1, 'Ahmad Dicari', 'diterima');
        $p2 = $this->buatPendaftar($u2, 'Budi Tidak Dicari', 'diterima');
        $this->buatPembayaran($p1);
        $this->buatPembayaran($p2);

        $response = $this->actingAs($this->admin)->get('/admin/pembayaran?cari=Ahmad+Dicari');
        $response->assertStatus(200);
        $response->assertSee('Ahmad Dicari');
        $response->assertDontSee('Budi Tidak Dicari');
    }

    public function test_filter_status_bayar_pada_data_pembayaran(): void
    {
        $u1 = User::create(['nama_user' => 'fstatus1_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
        $u2 = User::create(['nama_user' => 'fstatus2_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);

        $p1 = $this->buatPendaftar($u1, 'Status Berhasil Tes', 'diterima');
        $p2 = $this->buatPendaftar($u2, 'Status Menunggu Tes', 'diterima');
        $this->buatPembayaran($p1, 'berhasil');
        $this->buatPembayaran($p2, 'menunggu');

        $response = $this->actingAs($this->admin)->get('/admin/pembayaran?status=berhasil');
        $response->assertStatus(200);
        $response->assertSee('Status Berhasil Tes');
        $response->assertDontSee('Status Menunggu Tes');
    }

    public function test_filter_jenis_tagihan_pada_data_pembayaran(): void
    {
        $u1 = User::create(['nama_user' => 'fjenis1_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
        $u2 = User::create(['nama_user' => 'fjenis2_'.uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);

        $p1 = $this->buatPendaftar($u1, 'Jenis Pendaftaran Tes', 'diterima');
        $p2 = $this->buatPendaftar($u2, 'Jenis Syahriyah Tes', 'diterima');
        $this->buatPembayaran($p1, 'menunggu', 'pendaftaran');
        $this->buatPembayaran($p2, 'menunggu', 'syahriyah');

        $response = $this->actingAs($this->admin)->get('/admin/pembayaran?jenis=pendaftaran');
        $response->assertStatus(200);
        $response->assertSee('Jenis Pendaftaran Tes');
        $response->assertDontSee('Jenis Syahriyah Tes');
    }

    // =========================================================================
    // DATA PEMBAYARAN — tidak ada rute POST/PUT/DELETE
    // =========================================================================

    public function test_tidak_ada_rute_post_di_bawah_admin_pembayaran(): void
    {
        // Hanya ada GET /admin/pembayaran — POST harus 405 (route ada tapi method tidak diizinkan)
        $this->actingAs($this->admin)
            ->post('/admin/pembayaran')
            ->assertStatus(405); // Method Not Allowed pada koleksi
    }

    public function test_tidak_ada_rute_put_di_bawah_admin_pembayaran(): void
    {
        // /admin/pembayaran/{id} tidak terdaftar sama sekali → 404
        // 404 lebih kuat dari 405: rute mutasi ini TIDAK ADA di sistem
        $this->actingAs($this->admin)
            ->put('/admin/pembayaran/1')
            ->assertStatus(404);
    }

    public function test_tidak_ada_rute_delete_di_bawah_admin_pembayaran(): void
    {
        // /admin/pembayaran/{id} tidak terdaftar sama sekali → 404
        $this->actingAs($this->admin)
            ->delete('/admin/pembayaran/1')
            ->assertStatus(404);
    }

    // =========================================================================
    // DATA PEMBAYARAN — tidak ada N+1
    // =========================================================================

    public function test_data_pembayaran_tidak_ada_query_n_plus_1(): void
    {
        // Buat 3 pembayaran untuk pengukuran pertama
        for ($i = 0; $i < 3; $i++) {
            $u = User::create(['nama_user' => "pn1_{$i}_".uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
            $p = $this->buatPendaftar($u, "Bayar N+1 {$i}", 'diterima');
            $this->buatPembayaran($p);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin)->get('/admin/pembayaran')->assertStatus(200);
        $queryCount3 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Buat 3 lagi (total 6, tapi paginasi default 15 per halaman — tetap 1 halaman)
        for ($i = 3; $i < 6; $i++) {
            $u = User::create(['nama_user' => "pn1_{$i}_".uniqid(), 'password' => 'pass1234', 'peran' => 'calon_santri']);
            $p = $this->buatPendaftar($u, "Bayar N+1 Extra {$i}", 'diterima');
            $this->buatPembayaran($p);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin)->get('/admin/pembayaran')->assertStatus(200);
        $queryCount6 = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Dengan eager loading, penambahan data tidak boleh menggandakan jumlah query.
        // Toleransi: selisih maksimal 3 query (overhead framework/sesi yang tidak dapat dihindari).
        $this->assertLessThanOrEqual(
            $queryCount3 + 3,
            $queryCount6,
            "Jumlah query tumbuh dari {$queryCount3} menjadi {$queryCount6} saat data bertambah — kemungkinan ada N+1."
        );
    }
}
