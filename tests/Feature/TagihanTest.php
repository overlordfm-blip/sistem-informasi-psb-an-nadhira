<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\MidtransService;
use Carbon\Carbon;
use Database\Seeders\DemoSyahriyahSeeder;
use Tests\TestCase;

class TagihanTest extends TestCase
{
    protected User $admin;

    protected User $calonSantri;

    protected User $santri;

    protected Pendaftar $pendaftar;

    protected Tagihan $tagihan;

    protected string $serverKey = 'TESTSERVERKEY';

    protected function setUp(): void
    {
        parent::setUp();

        // Paksa server key agar signature bisa dihitung dalam pengujian
        config(['services.midtrans.server_key' => $this->serverKey]);

        $periode = PeriodePendaftaran::create([
            'nama_periode' => 'Gelombang Uji Tagihan',
            'tanggal_buka' => now()->subDays(10)->toDateString(),
            'tanggal_tutup' => now()->addDays(10)->toDateString(),
            'status_periode' => PeriodePendaftaran::STATUS_DIBUKA,
        ]);

        $this->calonSantri = User::create([
            'nama_user' => 'calon_tagihan_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        $this->santri = User::create([
            'nama_user' => 'santri_tagihan_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_SANTRI,
        ]);

        $this->admin = User::where('peran', User::PERAN_ADMIN)->first()
            ?? User::create([
                'nama_user' => 'admin_tagihan_'.uniqid(),
                'password' => 'pass1234',
                'peran' => User::PERAN_ADMIN,
            ]);

        $this->pendaftar = Pendaftar::create([
            'id_user' => $this->calonSantri->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => 'Ahmad Uji Tagihan',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Uji',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);

        $this->tagihan = Tagihan::create([
            'id_pendaftar' => $this->pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_PENDAFTARAN,
            'nominal' => 500000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);
    }

    private function buatSantriAktif(string $nama, string $status = Santri::STATUS_AKTIF): array
    {
        $user = User::create([
            'nama_user' => 'santri_batch_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_SANTRI,
        ]);
        $pendaftar = Pendaftar::create([
            'id_user' => $user->id_user,
            'id_periode' => $this->pendaftar->id_periode,
            'nama_lengkap' => $nama,
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali '.$nama,
            'no_telepon' => '08'.random_int(100000000, 999999999),
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);
        $santri = Santri::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'tanggal_masuk' => '2026-01-01',
            'status_santri' => $status,
        ]);

        return [$user, $pendaftar, $santri];
    }

    private function buatTagihanSyahriyah(Pendaftar $pendaftar, string $status = Tagihan::STATUS_BELUM_DIBAYAR): Tagihan
    {
        return Tagihan::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => 300000,
            'status_tagihan' => $status,
        ]);
    }

    // -----------------------------------------------------------------------
    // Pengujian halaman tagihan
    // -----------------------------------------------------------------------

    public function test_admin_mendapat_403_di_halaman_tagihan(): void
    {
        $this->actingAs($this->admin)->get('/tagihan')->assertStatus(403);
    }

    public function test_daftar_admin_mendukung_pencarian_filter_tanggal_bayar_dan_menu(): void
    {
        $this->tagihan->update(['status_tagihan' => Tagihan::STATUS_LUNAS]);
        $pembayaran = Pembayaran::create([
            'id_tagihan' => $this->tagihan->id_tagihan,
            'tanggal_bayar' => '2026-10-28',
            'jumlah_bayar' => 500000,
            'status_bayar' => Pembayaran::STATUS_BERHASIL,
        ]);
        $this->buatTagihanSyahriyah($this->pendaftar);

        $response = $this->actingAs($this->admin)->get('/admin/tagihan?cari=Uji%20Tagihan&jenis=pendaftaran&status=lunas');

        $response->assertOk()
            ->assertSee('Ahmad Uji Tagihan')
            ->assertSee('28 Oktober 2026')
            ->assertSee('Kelola Tagihan');
        $this->assertCount(1, $response->viewData('tagihanList'));
        $this->assertSame($pembayaran->id_tagihan, $response->viewData('tagihanList')->first()->id_tagihan);
    }

    public function test_admin_dapat_membuat_tagihan_syahriyah_massal_dan_santri_nonaktif_dilewati(): void
    {
        [, $pendaftarAktif] = $this->buatSantriAktif('Aktif Batch');
        [, $pendaftarNonaktif] = $this->buatSantriAktif('Nonaktif Batch', Santri::STATUS_NONAKTIF);

        $response = $this->actingAs($this->admin)->post('/admin/tagihan/syahriyah', [
            'nominal' => 425000,
            'sasaran' => 'semua',
        ]);

        $response->assertRedirect(route('admin.tagihan.syahriyah'))
            ->assertSessionHas('hasilBatch', function (array $hasil) use ($pendaftarAktif, $pendaftarNonaktif): bool {
                $santriNonaktifDilewati = collect($hasil['alasan'])->contains(
                    fn (array $item): bool => $item['nama'] === 'Nonaktif Batch'
                        && $item['alasan'] === 'Status santri tidak aktif.'
                );

                return $hasil['dibuat'] >= 1
                    && $hasil['dilewati'] >= 1
                    && $santriNonaktifDilewati
                    && Tagihan::where('id_pendaftar', $pendaftarAktif->id_pendaftar)
                        ->where('jenis_tagihan', Tagihan::JENIS_SYAHRIYAH)->exists()
                    && ! Tagihan::where('id_pendaftar', $pendaftarNonaktif->id_pendaftar)
                        ->where('jenis_tagihan', Tagihan::JENIS_SYAHRIYAH)->exists();
            });
        $this->assertDatabaseHas('tagihan', [
            'id_pendaftar' => $pendaftarAktif->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => 425000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);
        $this->assertDatabaseMissing('tagihan', [
            'id_pendaftar' => $pendaftarNonaktif->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
        ]);

        $this->actingAs($this->admin)->get('/admin/tagihan/syahriyah')
            ->assertSee('Nonaktif Batch')
            ->assertSee('Status santri tidak aktif.');
    }

    public function test_admin_dapat_membuat_tagihan_untuk_satu_santri_aktif(): void
    {
        [, $pendaftarPilihan, $santriPilihan] = $this->buatSantriAktif('Santri Pilihan');
        [, $pendaftarLain] = $this->buatSantriAktif('Santri Lain');

        $response = $this->actingAs($this->admin)->post('/admin/tagihan/syahriyah', [
            'nominal' => 300000,
            'sasaran' => 'satu',
            'id_santri' => $santriPilihan->id_santri,
        ]);

        $response->assertRedirect(route('admin.tagihan.syahriyah'));
        $this->assertDatabaseHas('tagihan', [
            'id_pendaftar' => $pendaftarPilihan->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
        ]);
        $this->assertDatabaseMissing('tagihan', [
            'id_pendaftar' => $pendaftarLain->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
        ]);
    }

    public function test_tagihan_syahriyah_belum_dibayar_menghalangi_tagihan_baru(): void
    {
        [, $pendaftar, $santri] = $this->buatSantriAktif('Masih Berutang');
        $tagihanLama = $this->buatTagihanSyahriyah($pendaftar);

        $this->actingAs($this->admin)->post('/admin/tagihan/syahriyah', [
            'nominal' => 300000,
            'sasaran' => 'satu',
            'id_santri' => $santri->id_santri,
        ])->assertRedirect(route('admin.tagihan.syahriyah'));

        $this->assertSame(1, Tagihan::where('id_pendaftar', $pendaftar->id_pendaftar)
            ->where('jenis_tagihan', Tagihan::JENIS_SYAHRIYAH)->count());
        $this->actingAs($this->admin)->get('/admin/tagihan/syahriyah')
            ->assertSee('Masih Berutang')
            ->assertSee('Masih memiliki tagihan syahriyah yang belum dibayar.');
        $this->assertSame(Tagihan::STATUS_BELUM_DIBAYAR, $tagihanLama->fresh()->status_tagihan);
    }

    public function test_tagihan_lunas_bulan_ini_dilewati_kecuali_override_dicentang(): void
    {
        $this->travelTo(Carbon::parse('2026-10-28 12:00:00', 'Asia/Jakarta'));
        [, $pendaftarTanpaPaksa, $santriTanpaPaksa] = $this->buatSantriAktif('Sudah Bayar Bulan Ini');
        [, $pendaftarPaksa, $santriPaksa] = $this->buatSantriAktif('Override Bulan Ini');

        foreach ([$pendaftarTanpaPaksa, $pendaftarPaksa] as $pendaftar) {
            $tagihanLunas = $this->buatTagihanSyahriyah($pendaftar, Tagihan::STATUS_LUNAS);
            Pembayaran::create([
                'id_tagihan' => $tagihanLunas->id_tagihan,
                'tanggal_bayar' => '2026-10-03',
                'jumlah_bayar' => 300000,
                'status_bayar' => Pembayaran::STATUS_BERHASIL,
            ]);
        }

        $this->actingAs($this->admin)->post('/admin/tagihan/syahriyah', [
            'nominal' => 310000,
            'sasaran' => 'satu',
            'id_santri' => $santriTanpaPaksa->id_santri,
        ])->assertRedirect(route('admin.tagihan.syahriyah'));
        $this->assertSame(1, Tagihan::where('id_pendaftar', $pendaftarTanpaPaksa->id_pendaftar)
            ->where('jenis_tagihan', Tagihan::JENIS_SYAHRIYAH)->count());

        $this->actingAs($this->admin)->post('/admin/tagihan/syahriyah', [
            'nominal' => 310000,
            'sasaran' => 'satu',
            'id_santri' => $santriPaksa->id_santri,
            'paksa' => '1',
        ])->assertRedirect(route('admin.tagihan.syahriyah'));
        $this->assertSame(2, Tagihan::where('id_pendaftar', $pendaftarPaksa->id_pendaftar)
            ->where('jenis_tagihan', Tagihan::JENIS_SYAHRIYAH)->count());
    }

    public function test_nominal_syahriyah_harus_integer_minimal_seribu(): void
    {
        foreach (['999', '1000.50'] as $nominal) {
            $response = $this->actingAs($this->admin)->from('/admin/tagihan/syahriyah')
                ->post('/admin/tagihan/syahriyah', [
                    'nominal' => $nominal,
                    'sasaran' => 'semua',
                ]);
            $response->assertRedirect('/admin/tagihan/syahriyah')->assertSessionHasErrors('nominal');
        }
    }

    public function test_tagihan_hanya_dapat_dihapus_jika_belum_dibayar_dan_belum_memiliki_pembayaran(): void
    {
        $tagihanTanpaPembayaran = $this->buatTagihanSyahriyah($this->pendaftar);
        $tagihanDenganPembayaran = $this->buatTagihanSyahriyah($this->pendaftar);
        Pembayaran::create([
            'id_tagihan' => $tagihanDenganPembayaran->id_tagihan,
            'jumlah_bayar' => $tagihanDenganPembayaran->nominal,
            'status_bayar' => Pembayaran::STATUS_MENUNGGU,
        ]);
        $tagihanLunas = $this->buatTagihanSyahriyah($this->pendaftar, Tagihan::STATUS_LUNAS);

        $this->actingAs($this->admin)->delete('/admin/tagihan/'.$tagihanTanpaPembayaran->id_tagihan)
            ->assertRedirect(route('admin.tagihan.index'))
            ->assertSessionHas('sukses');
        $this->assertDatabaseMissing('tagihan', ['id_tagihan' => $tagihanTanpaPembayaran->id_tagihan]);

        $this->actingAs($this->admin)->delete('/admin/tagihan/'.$tagihanDenganPembayaran->id_tagihan)
            ->assertSessionHas('galat', 'Tagihan yang sudah memiliki data pembayaran tidak dapat dihapus.');
        $this->actingAs($this->admin)->delete('/admin/tagihan/'.$tagihanLunas->id_tagihan)
            ->assertSessionHas('galat', 'Tagihan hanya dapat dihapus jika statusnya belum dibayar.');
        $this->assertDatabaseHas('tagihan', ['id_tagihan' => $tagihanDenganPembayaran->id_tagihan]);
        $this->assertDatabaseHas('tagihan', ['id_tagihan' => $tagihanLunas->id_tagihan]);
    }

    public function test_semua_rute_admin_tagihan_menolak_peran_lain(): void
    {
        foreach ([$this->calonSantri, $this->santri] as $pengguna) {
            $this->actingAs($pengguna)->get('/admin/tagihan')->assertForbidden();
            $this->actingAs($pengguna)->get('/admin/tagihan/syahriyah')->assertForbidden();
            $this->actingAs($pengguna)->post('/admin/tagihan/syahriyah', [])->assertForbidden();
            $this->actingAs($pengguna)->delete('/admin/tagihan/'.$this->tagihan->id_tagihan)->assertForbidden();
        }
    }

    public function test_tagihan_pengguna_diurutkan_dari_terbaru(): void
    {
        $tagihanLebihLama = $this->buatTagihanSyahriyah($this->pendaftar);
        $tagihanTerbaru = $this->buatTagihanSyahriyah($this->pendaftar);

        $response = $this->actingAs($this->calonSantri)->get('/tagihan')->assertOk();
        $tagihanTampil = $response->viewData('tagihan');

        $this->assertSame($tagihanTerbaru->id_tagihan, $tagihanTampil->first()->id_tagihan);
        $this->assertGreaterThan($tagihanLebihLama->id_tagihan, $tagihanTerbaru->id_tagihan);
    }

    public function test_calon_santri_dapat_melihat_halaman_tagihan(): void
    {
        $tagihanSyahriyah = Tagihan::create([
            'id_pendaftar' => $this->pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => 300000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        $response = $this->actingAs($this->calonSantri)->get('/tagihan');

        $response->assertOk()
            ->assertSee('Tagihan')
            ->assertSee('id="btn-bayar-'.$this->tagihan->id_tagihan.'"', false)
            ->assertSee('data-jenis-tagihan="pendaftaran"', false)
            ->assertSee('id="btn-bayar-'.$tagihanSyahriyah->id_tagihan.'"', false)
            ->assertSee('data-jenis-tagihan="syahriyah"', false)
            ->assertSee('onclick="bayarTagihan('.$this->tagihan->id_tagihan.')"', false)
            ->assertSee('app.sandbox.midtrans.com/snap/snap.js', false)
            ->assertSee('window.snap.pay(data.snap_token', false)
            ->assertSee('Pembayaran pendaftaran Anda telah lunas', false)
            ->assertSee('Pembayaran tagihan syahriyah Anda telah lunas', false);
    }

    public function test_calon_santri_ditolak_melihat_pesan_tidak_ada_tagihan(): void
    {
        $userDitolak = User::create([
            'nama_user' => 'calon_ditolak_tagihan_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        Pendaftar::create([
            'id_user' => $userDitolak->id_user,
            'id_periode' => $this->pendaftar->id_periode,
            'nama_lengkap' => 'Calon Tidak Diterima',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567892',
            'status_daftar' => Pendaftar::STATUS_DITOLAK,
        ]);

        $this->actingAs($userDitolak)->get('/tagihan')
            ->assertOk()
            ->assertSee('Tidak ada tagihan karena pendaftaran tidak diterima.');
    }

    public function test_santri_dapat_melihat_halaman_tagihan(): void
    {
        // Buat pendaftar untuk santri
        $pendaftarSantri = Pendaftar::create([
            'id_user' => $this->santri->id_user,
            'id_periode' => $this->pendaftar->id_periode,
            'nama_lengkap' => 'Santri Uji',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567891',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);
        Tagihan::create([
            'id_pendaftar' => $pendaftarSantri->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => 300000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        $response = $this->actingAs($this->santri)->get('/tagihan');
        $response->assertStatus(200);
        $response->assertSee('Santri Uji')->assertDontSee($this->santri->nama_user);
    }

    public function test_halaman_tagihan_hanya_memuat_tagihan_milik_pengguna_sendiri(): void
    {
        // $this->calonSantri memiliki tagihan Rp 500.000 (dibuat di setUp)
        $userLain = User::create([
            'nama_user' => 'calon_privasi_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $pendaftarLain = Pendaftar::create([
            'id_user' => $userLain->id_user,
            'id_periode' => $this->pendaftar->id_periode,
            'nama_lengkap' => 'Santri Lain Privasi',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali Lain',
            'no_telepon' => '081234567895',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);
        Tagihan::create([
            'id_pendaftar' => $pendaftarLain->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_PENDAFTARAN,
            'nominal' => 750000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        // Calon santri sendiri hanya melihat nominal miliknya
        $resCalon = $this->actingAs($this->calonSantri)->get('/tagihan');
        $resCalon->assertStatus(200);
        $resCalon->assertSee('Rp 500.000');
        $resCalon->assertDontSee('Rp 750.000');

        // Pengguna lain hanya melihat nominal miliknya
        $resLain = $this->actingAs($userLain)->get('/tagihan');
        $resLain->assertStatus(200);
        $resLain->assertSee('Rp 750.000');
        $resLain->assertDontSee('Rp 500.000');
    }

    // -----------------------------------------------------------------------
    // POST /tagihan/{id}/bayar — dengan MidtransService di-mock
    // -----------------------------------------------------------------------

    public function test_bayar_mengembalikan_snap_token(): void
    {
        // Mock MidtransService agar tidak memanggil jaringan
        $mock = $this->createStub(MidtransService::class);
        $mock->method('getSnapToken')->willReturn('mock-snap-token-123');
        $mock->method('getServerKey')->willReturn($this->serverKey);
        $this->app->instance(MidtransService::class, $mock);

        $response = $this->actingAs($this->calonSantri)
            ->postJson("/tagihan/{$this->tagihan->id_tagihan}/bayar");

        $response->assertStatus(200)
            ->assertJsonStructure(['snap_token'])
            ->assertJson(['snap_token' => 'mock-snap-token-123']);

        // Pembayaran dan transaksi harus dibuat
        $this->assertDatabaseHas('pembayaran', ['id_tagihan' => $this->tagihan->id_tagihan]);
        $this->assertDatabaseHas('transaksi', ['token' => 'mock-snap-token-123']);
    }

    public function test_pengguna_tidak_bisa_membayar_tagihan_orang_lain(): void
    {
        $userLain = User::create([
            'nama_user' => 'calon_lain_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        $response = $this->actingAs($userLain)
            ->postJson("/tagihan/{$this->tagihan->id_tagihan}/bayar");

        $response->assertStatus(404);
    }

    public function test_tagihan_lunas_tidak_bisa_dibayar_lagi(): void
    {
        $this->tagihan->update(['status_tagihan' => Tagihan::STATUS_LUNAS]);

        $response = $this->actingAs($this->calonSantri)
            ->postJson("/tagihan/{$this->tagihan->id_tagihan}/bayar");

        $response->assertStatus(422);
    }

    // -----------------------------------------------------------------------
    // Webhook Midtrans — semua skenario tanpa jaringan
    // -----------------------------------------------------------------------

    /** Buat payload notifikasi Midtrans dengan signature yang benar */
    private function buatPayload(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $transactionStatus,
        string $fraudStatus = 'accept',
        ?string $paymentType = 'bank_transfer',
    ): array {
        $signature = hash('sha512', $orderId.$statusCode.$grossAmount.$this->serverKey);

        return [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'payment_type' => $paymentType,
            'signature_key' => $signature,
        ];
    }

    /** Buat pembayaran + transaksi untuk tagihan tertentu */
    private function buatTransaksi(Tagihan $tagihan, string $orderId, string $statusTrx = 'menunggu'): Transaksi
    {
        $pembayaran = Pembayaran::firstOrCreate(
            ['id_tagihan' => $tagihan->id_tagihan],
            [
                'jumlah_bayar' => $tagihan->nominal,
                'status_bayar' => Pembayaran::STATUS_MENUNGGU,
                'tanggal_bayar' => null,
            ]
        );

        return Transaksi::create([
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'order_id' => $orderId,
            'token' => 'dummy-token',
            'metode' => null,
            'status_transaksi' => $statusTrx,
        ]);
    }

    public function test_webhook_signature_salah_mendapat_403_dan_data_tidak_berubah(): void
    {
        $trx = $this->buatTransaksi($this->tagihan, 'ANH-999-111');

        $payload = $this->buatPayload('ANH-999-111', '200', '500000', 'settlement');
        $payload['signature_key'] = 'SIGNATURE_SALAH_TOTAL';

        $response = $this->postJson('/midtrans/notification', $payload);
        $response->assertStatus(403);

        // Data tidak boleh berubah
        $this->assertDatabaseHas('transaksi', [
            'order_id' => 'ANH-999-111',
            'status_transaksi' => Transaksi::STATUS_MENUNGGU,
        ]);
    }

    public function test_webhook_order_id_tidak_dikenal_mendapat_404(): void
    {
        $payload = $this->buatPayload('ANH-TIDAK-ADA', '200', '500000', 'settlement');

        $response = $this->postJson('/midtrans/notification', $payload);
        $response->assertStatus(404);
    }

    public function test_webhook_berhasil_tagihan_pendaftaran_membuat_santri_dan_ubah_peran(): void
    {
        $orderId = 'ANH-'.$this->tagihan->id_tagihan.'-'.time();
        $trx = $this->buatTransaksi($this->tagihan, $orderId);

        $payload = $this->buatPayload($orderId, '200', '500000', 'settlement');

        $response = $this->postJson('/midtrans/notification', $payload);
        $response->assertStatus(200);

        // Tagihan harus lunas
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $this->tagihan->id_tagihan,
            'status_tagihan' => Tagihan::STATUS_LUNAS,
        ]);

        // Pembayaran berhasil dengan tanggal_bayar
        $pembayaran = Pembayaran::where('id_tagihan', $this->tagihan->id_tagihan)->first();
        $this->assertEquals(Pembayaran::STATUS_BERHASIL, $pembayaran->status_bayar);
        $this->assertNotNull($pembayaran->tanggal_bayar);

        // Transaksi berhasil
        $this->assertDatabaseHas('transaksi', [
            'order_id' => $orderId,
            'status_transaksi' => Transaksi::STATUS_BERHASIL,
            'metode' => 'bank_transfer',
        ]);

        // Santri dibuat
        $this->assertDatabaseHas('santri', [
            'id_pendaftar' => $this->pendaftar->id_pendaftar,
        ]);

        // Peran user berubah menjadi santri
        $this->assertDatabaseHas('users', [
            'id_user' => $this->calonSantri->id_user,
            'peran' => User::PERAN_SANTRI,
        ]);

        $halamanSantri = $this->actingAs($this->calonSantri->fresh())->get('/tagihan');
        $halamanSantri->assertOk()
            ->assertSee('Ahmad Uji Tagihan')
            ->assertDontSee($this->calonSantri->nama_user);
    }

    public function test_webhook_berhasil_tagihan_syahriyah_tidak_membuat_santri_dan_tidak_ubah_peran(): void
    {
        // Siapkan santri dengan tagihan syahriyah
        $pendaftarSantri = Pendaftar::create([
            'id_user' => $this->santri->id_user,
            'id_periode' => $this->pendaftar->id_periode,
            'nama_lengkap' => 'Santri Syahriyah',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567891',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);
        $tagihanSyahriyah = Tagihan::create([
            'id_pendaftar' => $pendaftarSantri->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => 300000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        $orderId = 'ANH-SYH-'.$tagihanSyahriyah->id_tagihan.'-'.time();
        $this->buatTransaksi($tagihanSyahriyah, $orderId);

        $payload = $this->buatPayload($orderId, '200', '300000', 'settlement');
        $response = $this->postJson('/midtrans/notification', $payload);
        $response->assertStatus(200);

        // Tagihan lunas
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $tagihanSyahriyah->id_tagihan,
            'status_tagihan' => Tagihan::STATUS_LUNAS,
        ]);

        // Peran santri TIDAK berubah
        $this->assertDatabaseHas('users', [
            'id_user' => $this->santri->id_user,
            'peran' => User::PERAN_SANTRI,
        ]);

        // Tidak ada record santri baru dengan id_pendaftar ini (sudah ada Santri aktif)
        $jumlahSantri = Santri::where('id_pendaftar', $pendaftarSantri->id_pendaftar)->count();
        $this->assertLessThanOrEqual(1, $jumlahSantri);
    }

    public function test_webhook_berhasil_ganda_tidak_membuat_data_ganda(): void
    {
        $orderId = 'ANH-IDEM-'.$this->tagihan->id_tagihan.'-'.time();
        $trx = $this->buatTransaksi($this->tagihan, $orderId);

        $payload = $this->buatPayload($orderId, '200', '500000', 'settlement');

        // Notifikasi pertama
        $this->postJson('/midtrans/notification', $payload)->assertStatus(200);

        // Notifikasi kedua (duplikat)
        $this->postJson('/midtrans/notification', $payload)->assertStatus(200);

        // Hanya satu santri
        $jumlahSantri = Santri::where('id_pendaftar', $this->pendaftar->id_pendaftar)->count();
        $this->assertEquals(1, $jumlahSantri);

        // Tagihan tetap lunas (tidak di-downgrade)
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $this->tagihan->id_tagihan,
            'status_tagihan' => Tagihan::STATUS_LUNAS,
        ]);
    }

    public function test_webhook_pending_tidak_mengubah_tagihan(): void
    {
        $orderId = 'ANH-PEND-'.$this->tagihan->id_tagihan.'-'.time();
        $this->buatTransaksi($this->tagihan, $orderId);

        $payload = $this->buatPayload($orderId, '201', '500000', 'pending');
        $this->postJson('/midtrans/notification', $payload)->assertStatus(200);

        // Tagihan tetap belum_dibayar
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $this->tagihan->id_tagihan,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);
    }

    public function test_webhook_deny_cancel_dan_failure_membuat_transaksi_dan_pembayaran_gagal_tagihan_tetap_belum_dibayar_peran_tidak_berubah(): void
    {
        foreach (['deny', 'cancel', 'failure'] as $statusMidtrans) {
            $tagihan = Tagihan::create([
                'id_pendaftar' => $this->pendaftar->id_pendaftar,
                'jenis_tagihan' => Tagihan::JENIS_PENDAFTARAN,
                'nominal' => 500000,
                'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
            ]);

            $orderId = 'ANH-'.strtoupper($statusMidtrans).'-'.$tagihan->id_tagihan.'-'.uniqid();
            $trx = $this->buatTransaksi($tagihan, $orderId);

            $payload = $this->buatPayload($orderId, '202', '500000', $statusMidtrans);
            $response = $this->postJson('/midtrans/notification', $payload);
            $response->assertStatus(200);

            // Transaksi berstatus gagal
            $this->assertDatabaseHas('transaksi', [
                'order_id' => $orderId,
                'status_transaksi' => Transaksi::STATUS_GAGAL,
            ]);

            // Pembayaran berstatus gagal
            $this->assertDatabaseHas('pembayaran', [
                'id_pembayaran' => $trx->id_pembayaran,
                'status_bayar' => Pembayaran::STATUS_GAGAL,
            ]);

            // Tagihan tetap belum_dibayar
            $this->assertEquals(Tagihan::STATUS_BELUM_DIBAYAR, $tagihan->fresh()->status_tagihan);

            // Peran pengguna tidak berubah (tetap calon_santri)
            $this->assertEquals(User::PERAN_CALON_SANTRI, $this->calonSantri->fresh()->peran);

            // Tidak membuat santri baru
            $this->assertDatabaseMissing('santri', [
                'id_pendaftar' => $this->pendaftar->id_pendaftar,
            ]);
        }
    }

    public function test_webhook_kedaluwarsa_lalu_bayar_ulang_membuat_transaksi_baru(): void
    {
        $orderId = 'ANH-EXP-'.$this->tagihan->id_tagihan.'-'.time();
        $trx = $this->buatTransaksi($this->tagihan, $orderId);

        // Notifikasi kedaluwarsa
        $payload = $this->buatPayload($orderId, '407', '500000', 'expire');
        $this->postJson('/midtrans/notification', $payload)->assertStatus(200);

        // Status transaksi lama harus kedaluwarsa
        $this->assertDatabaseHas('transaksi', [
            'order_id' => $orderId,
            'status_transaksi' => Transaksi::STATUS_KEDALUWARSA,
        ]);

        // Tagihan masih belum_dibayar
        $this->assertDatabaseHas('tagihan', [
            'id_tagihan' => $this->tagihan->id_tagihan,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        // Mock Midtrans untuk bayar ulang
        $mock = $this->createStub(MidtransService::class);
        $mock->method('getSnapToken')->willReturn('new-snap-token-after-expire');
        $mock->method('getServerKey')->willReturn($this->serverKey);
        $this->app->instance(MidtransService::class, $mock);

        // Bayar ulang harus berhasil dan menghasilkan transaksi baru
        $bayarUlang = $this->actingAs($this->calonSantri)
            ->postJson("/tagihan/{$this->tagihan->id_tagihan}/bayar");
        $bayarUlang->assertStatus(200)
            ->assertJson(['snap_token' => 'new-snap-token-after-expire']);

        // Harus ada transaksi baru
        $jumlahTrx = Transaksi::whereHas('pembayaran', function ($q) {
            $q->where('id_tagihan', $this->tagihan->id_tagihan);
        })->count();
        $this->assertGreaterThanOrEqual(2, $jumlahTrx);
    }

    public function test_polling_status_tagihan_mengembalikan_json(): void
    {
        $response = $this->actingAs($this->calonSantri)
            ->getJson("/tagihan/{$this->tagihan->id_tagihan}/status");

        $response->assertStatus(200)
            ->assertJson([
                'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
                'lunas' => false,
                'peran' => User::PERAN_CALON_SANTRI,
            ]);
    }

    public function test_polling_status_sinkron_otomatis_saat_transaksi_berhasil_di_midtrans(): void
    {
        $orderId = 'ANH-POLL-'.$this->tagihan->id_tagihan.'-'.time();
        $trx = $this->buatTransaksi($this->tagihan, $orderId);

        $gross = (string) (int) $this->tagihan->nominal;
        $sig = hash('sha512', $orderId.'200'.$gross.'.00'.$this->serverKey);

        $mock = $this->createMock(MidtransService::class);
        $mock->method('getServerKey')->willReturn($this->serverKey);
        $mock->method('getTransactionStatus')->with($orderId)->willReturn((object) [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => $gross.'.00',
            'signature_key' => $sig,
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
        ]);
        $this->app->instance(MidtransService::class, $mock);

        $response = $this->actingAs($this->calonSantri)
            ->getJson("/tagihan/{$this->tagihan->id_tagihan}/status");

        $response->assertStatus(200)
            ->assertJson([
                'status_tagihan' => Tagihan::STATUS_LUNAS,
                'lunas' => true,
                'peran' => User::PERAN_SANTRI,
            ]);

        $this->assertEquals(User::PERAN_SANTRI, $this->calonSantri->fresh()->peran);
        $this->assertDatabaseHas('santri', [
            'id_pendaftar' => $this->pendaftar->id_pendaftar,
        ]);
    }

    public function test_pengguna_tidak_bisa_melihat_status_tagihan_orang_lain(): void
    {
        $userLain = User::create([
            'nama_user' => 'calon_status_lain_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);

        $response = $this->actingAs($userLain)
            ->getJson("/tagihan/{$this->tagihan->id_tagihan}/status");

        $response->assertStatus(404);
    }

    public function test_polling_status_tagihan_dibatasi_frekuensinya_throttle(): void
    {
        $userThrottle = User::create([
            'nama_user' => 'calon_throttle_'.uniqid(),
            'password' => 'pass1234',
            'peran' => User::PERAN_CALON_SANTRI,
        ]);
        $pendaftarThrottle = Pendaftar::create([
            'id_user' => $userThrottle->id_user,
            'id_periode' => $this->pendaftar->id_periode,
            'nama_lengkap' => 'Santri Throttle',
            'tanggal_lahir' => '2010-01-01',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'Wali',
            'no_telepon' => '081234567897',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);
        $tagihanThrottle = Tagihan::create([
            'id_pendaftar' => $pendaftarThrottle->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_PENDAFTARAN,
            'nominal' => 500000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        // Lakukan 60 permintaan yang diperbolehkan
        for ($i = 0; $i < 60; $i++) {
            $res = $this->actingAs($userThrottle)
                ->getJson("/tagihan/{$tagihanThrottle->id_tagihan}/status");
            $res->assertStatus(200);
        }

        // Permintaan ke-61 harus terblokir dengan HTTP 429
        $resBlokir = $this->actingAs($userThrottle)
            ->getJson("/tagihan/{$tagihanThrottle->id_tagihan}/status");
        $resBlokir->assertStatus(429);
    }

    // -----------------------------------------------------------------------
    // Fase 7 Bagian B: Riwayat Pembayaran & Tagihan Syahriyah
    // -----------------------------------------------------------------------

    public function test_riwayat_pembayaran_tanggal_berhasil_memakai_tanggal_bayar(): void
    {
        $pembayaran = Pembayaran::create([
            'id_tagihan' => $this->tagihan->id_tagihan,
            'tanggal_bayar' => '2026-10-28',
            'jumlah_bayar' => 500000,
            'status_bayar' => Pembayaran::STATUS_BERHASIL,
        ]);

        // Buat transaksi dengan order_id yang timestamp-nya berbeda dari tanggal_bayar
        $timestampLama = strtotime('2026-05-10 10:00:00');
        Transaksi::create([
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'order_id' => "ANH-{$this->tagihan->id_tagihan}-{$timestampLama}",
            'token' => 'token-berhasil-lama',
            'metode' => 'qris',
            'status_transaksi' => Transaksi::STATUS_BERHASIL,
        ]);

        // Model accessor tanggal_riwayat harus memakai tanggal_bayar
        $this->assertSame('28 Oktober 2026', $pembayaran->tanggal_riwayat?->translatedFormat('d F Y'));

        $response = $this->actingAs($this->calonSantri)->get('/tagihan');
        $response->assertStatus(200);
        $response->assertSee('28 Oktober 2026');
        $response->assertDontSee('10 Mei 2026');
    }

    public function test_riwayat_pembayaran_gagal_kedaluwarsa_dan_menunggu_memakai_tanggal_dari_order_id(): void
    {
        // Mock getTransactionStatus agar status menunggu tidak memicu pemanggilan jaringan Midtrans
        $mock = $this->createStub(MidtransService::class);
        $mock->method('getTransactionStatus')->willReturn(null);
        $mock->method('getServerKey')->willReturn($this->serverKey);
        $this->app->instance(MidtransService::class, $mock);

        // 1. Kasus status menunggu (pada tagihan tersendiri)
        $tagihanMenunggu = Tagihan::create([
            'id_pendaftar' => $this->pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => 300000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);
        $timestampMenunggu = strtotime('2026-10-25 14:00:00');
        $pembayaranMenunggu = Pembayaran::create([
            'id_tagihan' => $tagihanMenunggu->id_tagihan,
            'tanggal_bayar' => null,
            'jumlah_bayar' => 300000,
            'status_bayar' => Pembayaran::STATUS_MENUNGGU,
        ]);
        Transaksi::create([
            'id_pembayaran' => $pembayaranMenunggu->id_pembayaran,
            'order_id' => "ANH-{$tagihanMenunggu->id_tagihan}-{$timestampMenunggu}",
            'token' => 'token-menunggu',
            'metode' => null,
            'status_transaksi' => Transaksi::STATUS_MENUNGGU,
        ]);
        $this->assertSame('25 Oktober 2026', $pembayaranMenunggu->tanggal_riwayat?->translatedFormat('d F Y'));

        // 2. Kasus status gagal (pada tagihan tersendiri)
        $tagihanGagal = Tagihan::create([
            'id_pendaftar' => $this->pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => 300000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);
        $timestampGagal = strtotime('2026-10-26 15:30:00');
        $pembayaranGagal = Pembayaran::create([
            'id_tagihan' => $tagihanGagal->id_tagihan,
            'tanggal_bayar' => null,
            'jumlah_bayar' => 300000,
            'status_bayar' => Pembayaran::STATUS_GAGAL,
        ]);
        Transaksi::create([
            'id_pembayaran' => $pembayaranGagal->id_pembayaran,
            'order_id' => "ANH-{$tagihanGagal->id_tagihan}-{$timestampGagal}",
            'token' => 'token-gagal',
            'metode' => 'qris',
            'status_transaksi' => Transaksi::STATUS_GAGAL,
        ]);
        $this->assertSame('26 Oktober 2026', $pembayaranGagal->tanggal_riwayat?->translatedFormat('d F Y'));

        // 3. Kasus status kedaluwarsa (pada tagihan tersendiri)
        $tagihanKedaluwarsa = Tagihan::create([
            'id_pendaftar' => $this->pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => 300000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);
        $timestampKedaluwarsa = strtotime('2026-10-27 16:45:00');
        $pembayaranKedaluwarsa = Pembayaran::create([
            'id_tagihan' => $tagihanKedaluwarsa->id_tagihan,
            'tanggal_bayar' => null,
            'jumlah_bayar' => 300000,
            'status_bayar' => Pembayaran::STATUS_KEDALUWARSA,
        ]);
        Transaksi::create([
            'id_pembayaran' => $pembayaranKedaluwarsa->id_pembayaran,
            'order_id' => "ANH-{$tagihanKedaluwarsa->id_tagihan}-{$timestampKedaluwarsa}",
            'token' => 'token-kedaluwarsa',
            'metode' => 'bank_transfer',
            'status_transaksi' => Transaksi::STATUS_KEDALUWARSA,
        ]);
        $this->assertSame('27 Oktober 2026', $pembayaranKedaluwarsa->tanggal_riwayat?->translatedFormat('d F Y'));

        // Cek halaman /tagihan memuat ketiga tanggal tersebut
        $response = $this->actingAs($this->calonSantri)->get('/tagihan');
        $response->assertStatus(200);
        $response->assertSee('25 Oktober 2026');
        $response->assertSee('26 Oktober 2026');
        $response->assertSee('27 Oktober 2026');
    }

    public function test_riwayat_pembayaran_menampilkan_strip_bila_tanpa_transaksi(): void
    {
        $pembayaran = Pembayaran::create([
            'id_tagihan' => $this->tagihan->id_tagihan,
            'tanggal_bayar' => null,
            'jumlah_bayar' => 500000,
            'status_bayar' => Pembayaran::STATUS_MENUNGGU,
        ]);

        $this->assertNull($pembayaran->tanggal_riwayat);

        $response = $this->actingAs($this->calonSantri)->get('/tagihan');
        $response->assertStatus(200);
        $response->assertSee('<td>-</td>', false);
    }

    public function test_accessor_mengembalikan_null_untuk_order_id_berformat_lain(): void
    {
        // Format tidak sesuai harus mengembalikan null
        $trx1 = new Transaksi(['order_id' => 'ANH-POLL-1-1761652495']);
        $this->assertNull($trx1->tanggalPercobaan);

        $trx2 = new Transaksi(['order_id' => 'INV-20261028']);
        $this->assertNull($trx2->tanggalPercobaan);

        $trx3 = new Transaksi(['order_id' => 'ANH-abc-def']);
        $this->assertNull($trx3->tanggalPercobaan);

        $trx4 = new Transaksi(['order_id' => 'ANH-1']);
        $this->assertNull($trx4->tanggalPercobaan);

        $trx5 = new Transaksi(['order_id' => '']);
        $this->assertNull($trx5->tanggalPercobaan);

        $trx6 = new Transaksi(['order_id' => null]);
        $this->assertNull($trx6->tanggalPercobaan);

        // Format valid: ANH-{id_tagihan}-{unix_timestamp}
        $ts = strtotime('2026-10-28 15:30:00');
        $trxValid = new Transaksi(['order_id' => 'ANH-99-'.$ts]);
        $this->assertInstanceOf(Carbon::class, $trxValid->tanggalPercobaan);
        $this->assertSame('28 Oktober 2026', $trxValid->tanggalPercobaan->translatedFormat('d F Y'));
        $this->assertSame('Asia/Jakarta', $trxValid->tanggalPercobaan->timezoneName);
    }

    public function test_santri_demo_melihat_tagihan_syahriyah_dan_membayarnya_peran_tetap_santri_dan_tabel_santri_tidak_bertambah(): void
    {
        // 1. Jalankan seeder demo di environment local
        $this->app->detectEnvironment(fn () => 'local');
        $this->artisan('db:seed', [
            '--class' => DemoSyahriyahSeeder::class,
            '--force' => true,
        ]);
        $this->app->detectEnvironment(fn () => 'testing');

        $santriDemo = User::where('nama_user', 'santri_demo')->firstOrFail();
        $pendaftar = $santriDemo->pendaftar;
        $this->assertNotNull($pendaftar);

        $tagihanBelum = Tagihan::where('id_pendaftar', $pendaftar->id_pendaftar)
            ->where('status_tagihan', Tagihan::STATUS_BELUM_DIBAYAR)
            ->firstOrFail();

        $tagihanLunas = Tagihan::where('id_pendaftar', $pendaftar->id_pendaftar)
            ->where('status_tagihan', Tagihan::STATUS_LUNAS)
            ->firstOrFail();

        // Jumlah awal data di tabel santri
        $santriCountAwal = Santri::count();

        // 2. Santri demo melihat halaman /tagihan
        $response = $this->actingAs($santriDemo)->get('/tagihan');
        $response->assertStatus(200);
        $response->assertSee('Syahriyah');
        $response->assertSee('BELUM_DIBAYAR');
        $response->assertSee('BERHASIL');
        $response->assertSee('01 Oktober 2026');

        // 3. Mock Midtrans untuk pembayaran tagihan syahriyah yang belum dibayar
        $mock = $this->createMock(MidtransService::class);
        $mock->expects($this->once())->method('getSnapToken')->willReturn('snap-demo-token-123');
        $mock->expects($this->any())->method('getServerKey')->willReturn($this->serverKey);
        $this->app->instance(MidtransService::class, $mock);

        $bayarResponse = $this->actingAs($santriDemo)
            ->postJson("/tagihan/{$tagihanBelum->id_tagihan}/bayar");
        $bayarResponse->assertStatus(200)
            ->assertJson(['snap_token' => 'snap-demo-token-123']);

        $transaksiBaru = Transaksi::whereHas('pembayaran', function ($q) use ($tagihanBelum) {
            $q->where('id_tagihan', $tagihanBelum->id_tagihan);
        })->latest('id_transaksi')->firstOrFail();

        // 4. Webhook berhasil dari Midtrans
        $orderId = $transaksiBaru->order_id;
        $gross = (string) (int) $tagihanBelum->nominal;
        $sig = hash('sha512', $orderId.'200'.$gross.'.00'.$this->serverKey);

        $webhookPayload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => $gross.'.00',
            'signature_key' => $sig,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
        ];

        $webhookRes = $this->postJson('/midtrans/notification', $webhookPayload);
        $webhookRes->assertStatus(200);

        // 5. Verifikasi tagihan lunas dan pembayaran berhasil
        $this->assertSame(Tagihan::STATUS_LUNAS, $tagihanBelum->fresh()->status_tagihan);
        $this->assertSame(Pembayaran::STATUS_BERHASIL, $tagihanBelum->pembayaran->fresh()->status_bayar);

        // 6. Peran pengguna TETAP santri (tidak berubah)
        $this->assertSame(User::PERAN_SANTRI, $santriDemo->fresh()->peran);

        // 7. Tabel santri TIDAK bertambah (tetap sama dengan jumlah awal)
        $this->assertSame($santriCountAwal, Santri::count());
    }

    public function test_seeder_demo_menolak_berjalan_di_environment_produksi(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DemoSyahriyahSeeder hanya boleh dijalankan di environment local.');

        $this->artisan('db:seed', [
            '--class' => DemoSyahriyahSeeder::class,
            '--force' => true,
        ]);
    }
}
