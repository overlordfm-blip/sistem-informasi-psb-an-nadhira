<?php

namespace Tests\Feature;

use App\Models\InformasiPondok;
use App\Models\Pembayaran;
use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModelRelasiTest extends TestCase
{
    use DatabaseTransactions;
    public function test_basis_data_hanya_memiliki_delapan_tabel_aplikasi(): void
    {
        $tables = collect(DB::select('SHOW TABLES'))->map(function ($row) {
            return array_values((array) $row)[0];
        })->reject(fn ($name) => $name === 'migrations')->values()->all();

        $expectedTables = [
            'informasi_pondok',
            'pembayaran',
            'pendaftar',
            'periode_pendaftaran',
            'santri',
            'tagihan',
            'transaksi',
            'users',
        ];

        sort($tables);
        sort($expectedTables);

        $this->assertEquals($expectedTables, $tables);
    }

    public function test_tabel_users_tidak_memiliki_email_dan_remember_token(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['id_user', 'nama_user', 'password', 'peran']));
        $this->assertFalse(Schema::hasColumn('users', 'email'));
        $this->assertFalse(Schema::hasColumn('users', 'remember_token'));
        $this->assertFalse(Schema::hasColumn('users', 'created_at'));
        $this->assertFalse(Schema::hasColumn('users', 'updated_at'));
    }

    public function test_seeder_membuat_admin_periode_dan_informasi_pondok(): void
    {
        $admin = User::where('nama_user', 'admin')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check(env('ADMIN_PASSWORD', 'admin123'), $admin->password));

        $periode = PeriodePendaftaran::first();
        $this->assertNotNull($periode);
        $this->assertEquals(PeriodePendaftaran::STATUS_DITUTUP, $periode->status_periode);

        $this->assertEquals(3, InformasiPondok::count());
        $this->assertEquals($admin->id_user, InformasiPondok::first()->id_user);
    }

    public function test_seluruh_relasi_model_bekerja_dengan_benar(): void
    {
        // 1. Relasi User -> InformasiPondok
        $admin = User::where('peran', User::PERAN_ADMIN)->first();
        $this->assertGreaterThanOrEqual(1, $admin->informasiPondok()->count());
        $this->assertInstanceOf(User::class, $admin->informasiPondok->first()->user);

        // 2. Ambil user calon santri dan periode
        $calon = User::where('peran', User::PERAN_CALON_SANTRI)->first();
        $periode = PeriodePendaftaran::first();

        // 3. Buat pendaftar
        $pendaftar = Pendaftar::create([
            'id_user' => $calon->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => 'Muhammad Zaidan',
            'tanggal_lahir' => '2008-05-15',
            'alamat' => 'Wonosobo',
            'nama_wali' => 'H. Abdullah',
            'no_telepon' => '081234567890',
            'status_daftar' => Pendaftar::STATUS_DIPROSES,
        ]);

        // Verifikasi relasi Pendaftar -> User & Periode
        $this->assertEquals($calon->id_user, $pendaftar->user->id_user);
        $this->assertEquals($periode->id_periode, $pendaftar->periode->id_periode);
        $this->assertEquals($pendaftar->id_pendaftar, $calon->fresh()->pendaftar->id_pendaftar);
        $this->assertTrue($periode->fresh()->pendaftar->contains($pendaftar));

        // 4. Buat tagihan pendaftaran
        $tagihan = Tagihan::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_PENDAFTARAN,
            'nominal' => 150000,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);

        // Verifikasi relasi Pendaftar -> Tagihan
        $this->assertEquals($pendaftar->id_pendaftar, $tagihan->pendaftar->id_pendaftar);
        $this->assertTrue($pendaftar->fresh()->tagihan->contains($tagihan));

        // 5. Buat pembayaran
        $pembayaran = Pembayaran::create([
            'id_tagihan' => $tagihan->id_tagihan,
            'tanggal_bayar' => null,
            'jumlah_bayar' => 150000,
            'status_bayar' => Pembayaran::STATUS_MENUNGGU,
        ]);

        // Verifikasi relasi Tagihan <-> Pembayaran
        $this->assertEquals($tagihan->id_tagihan, $pembayaran->tagihan->id_tagihan);
        $this->assertEquals($pembayaran->id_pembayaran, $tagihan->fresh()->pembayaran->id_pembayaran);

        // 6. Buat transaksi Midtrans
        $transaksi = Transaksi::create([
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'order_id' => 'ANH-TEST-' . uniqid(),
            'token' => 'mock-midtrans-snap-token',
            'metode' => null,
            'status_transaksi' => Transaksi::STATUS_MENUNGGU,
        ]);

        // Verifikasi relasi Pembayaran <-> Transaksi
        $this->assertEquals($pembayaran->id_pembayaran, $transaksi->pembayaran->id_pembayaran);
        $this->assertTrue($pembayaran->fresh()->transaksi->contains($transaksi));

        // 7. Buat santri
        $santri = Santri::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'tanggal_masuk' => '2026-07-01',
            'status_santri' => Santri::STATUS_AKTIF,
        ]);

        // Verifikasi relasi Pendaftar <-> Santri
        $this->assertEquals($pendaftar->id_pendaftar, $santri->pendaftar->id_pendaftar);
        $this->assertEquals($santri->id_santri, $pendaftar->fresh()->santri->id_santri);
    }
}
