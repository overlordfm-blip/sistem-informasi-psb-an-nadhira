<?php

namespace Database\Seeders;

use App\Models\Pembayaran;
use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoSyahriyahSeeder extends Seeder
{
    /**
     * Jalankan seeder data contoh syahriyah.
     * Tidak dipanggil dari DatabaseSeeder dan menolak berjalan di luar environment local.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new RuntimeException('DemoSyahriyahSeeder hanya boleh dijalankan di environment local.');
        }

        // Cari atau buat periode untuk data pendaftar
        $periode = PeriodePendaftaran::first();
        if (! $periode) {
            $periode = PeriodePendaftaran::create([
                'nama_periode' => 'Periode Demo 2026',
                'tanggal_buka' => '2026-01-01',
                'tanggal_tutup' => '2026-12-31',
                'status_periode' => PeriodePendaftaran::STATUS_DITUTUP,
            ]);
        }

        // Bersihkan data santri demo lama jika sudah ada (idempoten)
        $userLama = User::where('nama_user', 'santri_demo')->first();
        if ($userLama) {
            if ($userLama->pendaftar) {
                foreach ($userLama->pendaftar->tagihan as $tagihanItem) {
                    if ($tagihanItem->pembayaran) {
                        $tagihanItem->pembayaran->transaksi()->delete();
                        $tagihanItem->pembayaran()->delete();
                    }
                    $tagihanItem->delete();
                }
                $userLama->pendaftar->santri()->delete();
                $userLama->pendaftar()->delete();
            }
            $userLama->delete();
        }

        // 1. User berperan santri
        $user = User::create([
            'nama_user' => 'santri_demo',
            'password' => 'santri123',
            'peran' => User::PERAN_SANTRI,
        ]);

        // 2. Pendaftar diterima
        $pendaftar = Pendaftar::create([
            'id_user' => $user->id_user,
            'id_periode' => $periode->id_periode,
            'nama_lengkap' => 'Ahmad Santri Demo',
            'tanggal_lahir' => '2008-05-15',
            'alamat' => 'Jl. Pesantren No. 10, Kalibeber, Wonosobo',
            'nama_wali' => 'H. Mahmud',
            'no_telepon' => '081234567899',
            'status_daftar' => Pendaftar::STATUS_DITERIMA,
        ]);

        // 3. Santri aktif
        Santri::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'tanggal_masuk' => '2026-07-01',
            'status_santri' => Santri::STATUS_AKTIF,
        ]);

        $nominalSyahriyah = config('pondok.syahriyah', 300000);

        // 4. Tagihan syahriyah pertama: lunas lengkap dengan pembayaran & transaksi berhasil
        $tagihanLunas = Tagihan::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => $nominalSyahriyah,
            'status_tagihan' => Tagihan::STATUS_LUNAS,
        ]);

        $pembayaranLunas = Pembayaran::create([
            'id_tagihan' => $tagihanLunas->id_tagihan,
            'tanggal_bayar' => '2026-10-01',
            'jumlah_bayar' => $nominalSyahriyah,
            'status_bayar' => Pembayaran::STATUS_BERHASIL,
        ]);

        // Order ID berformat valid: ANH-{id_tagihan}-{unix_timestamp}
        $timestampValid = strtotime('2026-10-01 09:15:00');
        $orderIdValid = 'ANH-'.$tagihanLunas->id_tagihan.'-'.$timestampValid;

        Transaksi::create([
            'id_pembayaran' => $pembayaranLunas->id_pembayaran,
            'order_id' => $orderIdValid,
            'token' => 'snap-token-demo-'.$tagihanLunas->id_tagihan,
            'metode' => 'bank_transfer',
            'status_transaksi' => Transaksi::STATUS_BERHASIL,
        ]);

        // 5. Tagihan syahriyah kedua: belum_dibayar
        Tagihan::create([
            'id_pendaftar' => $pendaftar->id_pendaftar,
            'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
            'nominal' => $nominalSyahriyah,
            'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
        ]);
    }
}
