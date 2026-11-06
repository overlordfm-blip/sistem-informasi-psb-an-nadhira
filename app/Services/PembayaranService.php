<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PembayaranService
{
    public function __construct(
        private MidtransService $midtrans,
    ) {}

    // -----------------------------------------------------------------------
    // Inisiasi pembayaran (dipanggil dari POST /tagihan/{id}/bayar)
    // -----------------------------------------------------------------------

    /**
     * Buat atau dapatkan ulang Snap token untuk tagihan yang belum dibayar.
     *
     * @throws RuntimeException jika tagihan sudah lunas atau tidak ditemukan.
     */
    public function inisiasiBayar(Tagihan $tagihan, bool $paksaBaru = false): string
    {
        if ($tagihan->status_tagihan !== Tagihan::STATUS_BELUM_DIBAYAR) {
            throw new RuntimeException('Tagihan ini sudah lunas atau tidak dapat dibayar.');
        }

        // Muat relasi pendaftar agar bisa akses nama & telepon
        $tagihan->loadMissing(['pendaftar', 'pembayaran.transaksi']);

        return DB::transaction(function () use ($tagihan, $paksaBaru) {
            // 1. Dapatkan atau buat pembayaran
            $pembayaran = $tagihan->pembayaran;

            if (! $pembayaran) {
                $pembayaran = Pembayaran::create([
                    'id_tagihan' => $tagihan->id_tagihan,
                    'jumlah_bayar' => $tagihan->nominal,
                    'status_bayar' => Pembayaran::STATUS_MENUNGGU,
                    'tanggal_bayar' => null,
                ]);
            }

            // 2. Cek apakah ada transaksi menunggu yang tokennya masih bisa dipakai
            $transaksiAktif = $pembayaran->transaksi()
                ->where('status_transaksi', Transaksi::STATUS_MENUNGGU)
                ->whereNotNull('token')
                ->latest('id_transaksi')
                ->first();

            if ($transaksiAktif) {
                if ($paksaBaru) {
                    $transaksiAktif->update(['status_transaksi' => Transaksi::STATUS_KEDALUWARSA]);
                    $pembayaran->update(['status_bayar' => Pembayaran::STATUS_KEDALUWARSA]);
                    $transaksiAktif = null;
                } else {
                    // Periksa keabsahan status langsung ke Midtrans API
                    $status = $this->midtrans->getTransactionStatus($transaksiAktif->order_id);
                    if ($status) {
                        $txStatus = $status->transaction_status ?? null;
                        $expiryTime = $status->expiry_time ?? null;

                        $isExpired = in_array($txStatus, ['expire', 'cancel', 'deny', 'failure'], true)
                            || (! empty($expiryTime) && strtotime($expiryTime) <= time());

                        if ($isExpired) {
                            $transaksiAktif->update([
                                'status_transaksi' => Transaksi::STATUS_KEDALUWARSA,
                                'metode' => $status->payment_type ?? $transaksiAktif->metode,
                            ]);
                            $pembayaran->update(['status_bayar' => Pembayaran::STATUS_KEDALUWARSA]);
                            $transaksiAktif = null;
                        }
                    }
                }
            }

            if ($transaksiAktif) {
                return $transaksiAktif->token;
            }

            // Pastikan pembayaran kembali ke status menunggu jika akan dibuat transaksi baru
            if ($pembayaran->status_bayar !== Pembayaran::STATUS_MENUNGGU) {
                $pembayaran->update(['status_bayar' => Pembayaran::STATUS_MENUNGGU]);
            }

            // 3. Buat transaksi baru & panggil Midtrans Snap
            $orderId = 'ANH-'.$tagihan->id_tagihan.'-'.time();
            $nominal = (int) $tagihan->nominal;

            $pendaftar = $tagihan->pendaftar;
            $namaItem = $tagihan->jenis_tagihan === Tagihan::JENIS_PENDAFTARAN
                ? 'Biaya Pendaftaran'
                : 'Syahriyah';

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $nominal,
                ],
                'customer_details' => [
                    'first_name' => $pendaftar?->nama_lengkap ?? '-',
                    'phone' => $pendaftar?->no_telepon ?? '-',
                ],
                'item_details' => [
                    [
                        'id' => 'TAG-'.$tagihan->id_tagihan,
                        'price' => $nominal,
                        'quantity' => 1,
                        'name' => $namaItem,
                    ],
                ],
                'callbacks' => [
                    'finish' => url('/tagihan'),
                ],
            ];

            $token = $this->midtrans->getSnapToken($params);

            Transaksi::create([
                'id_pembayaran' => $pembayaran->id_pembayaran,
                'order_id' => $orderId,
                'token' => $token,
                'metode' => null,
                'status_transaksi' => Transaksi::STATUS_MENUNGGU,
            ]);

            return $token;
        });
    }

    /**
     * Sinkronkan status transaksi langsung dari API Midtrans menggunakan kredensial server.
     * Sangat berguna di lingkungan lokal saat webhook tidak dapat menjangkau localhost.
     */
    public function sinkronkanTransaksi(Transaksi $transaksi): void
    {
        if ($transaksi->status_transaksi !== Transaksi::STATUS_MENUNGGU) {
            return;
        }

        $status = $this->midtrans->getTransactionStatus($transaksi->order_id);
        if (! $status) {
            return;
        }

        $payload = (array) $status;
        if (empty($payload['signature_key']) && ! empty($payload['order_id']) && ! empty($payload['status_code']) && ! empty($payload['gross_amount'])) {
            $payload['signature_key'] = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$this->midtrans->getServerKey());
        }

        if (! empty($payload['signature_key'])) {
            $this->prosesNotifikasi($payload);
        }
    }

    // -----------------------------------------------------------------------
    // Penanganan webhook Midtrans
    // -----------------------------------------------------------------------

    /**
     * Proses payload notifikasi dari Midtrans.
     *
     * @param  array<string, mixed>  $payload
     * @return array{status: int, message: string}
     */
    public function prosesNotifikasi(array $payload): array
    {
        // 1. Verifikasi signature
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $serverKey = $this->midtrans->getServerKey();

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        $receivedSignature = $payload['signature_key'] ?? '';

        if (! hash_equals($expectedSignature, $receivedSignature)) {
            return ['status' => 403, 'message' => 'Signature tidak valid.'];
        }

        // 2. Cari transaksi berdasarkan order_id
        $transaksi = Transaksi::where('order_id', $orderId)->first();
        if (! $transaksi) {
            return ['status' => 404, 'message' => 'Transaksi tidak ditemukan.'];
        }

        // 3. Idempoten: abaikan jika sudah berhasil
        if ($transaksi->status_transaksi === Transaksi::STATUS_BERHASIL) {
            return ['status' => 200, 'message' => 'Notifikasi diabaikan (sudah berhasil).'];
        }

        // 4. Pemetaan status Midtrans
        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus = $payload['fraud_status'] ?? '';
        $paymentType = $payload['payment_type'] ?? null;

        $statusBaru = $this->petakanStatus($transactionStatus, $fraudStatus);

        // 5. Proses dalam satu transaksi database
        DB::transaction(function () use ($transaksi, $statusBaru, $paymentType, $grossAmount) {
            $pembayaran = $transaksi->pembayaran()->lockForUpdate()->first();
            $tagihan = $pembayaran->tagihan()->lockForUpdate()->first();

            // Perbarui transaksi
            $transaksi->update([
                'status_transaksi' => $statusBaru,
                'metode' => $paymentType,
            ]);

            if ($statusBaru === Transaksi::STATUS_BERHASIL) {
                // Pembayaran berhasil
                $pembayaran->update([
                    'status_bayar' => Pembayaran::STATUS_BERHASIL,
                    'tanggal_bayar' => now('Asia/Jakarta')->toDateString(),
                    'jumlah_bayar' => (int) $grossAmount,
                ]);

                $tagihan->update(['status_tagihan' => Tagihan::STATUS_LUNAS]);

                // Hanya untuk tagihan pendaftaran: buat santri & ubah peran
                if ($tagihan->jenis_tagihan === Tagihan::JENIS_PENDAFTARAN) {
                    $pendaftar = $tagihan->pendaftar;

                    // Buat santri jika belum ada
                    if (! $pendaftar->santri) {
                        Santri::create([
                            'id_pendaftar' => $pendaftar->id_pendaftar,
                            'tanggal_masuk' => now('Asia/Jakarta')->toDateString(),
                            'status_santri' => Santri::STATUS_AKTIF,
                        ]);
                    }

                    // Ubah peran user menjadi santri
                    $user = $pendaftar->user;
                    if ($user->peran !== User::PERAN_SANTRI) {
                        $user->update(['peran' => User::PERAN_SANTRI]);
                    }
                }
            } elseif (in_array($statusBaru, [
                Transaksi::STATUS_GAGAL,
                Transaksi::STATUS_KEDALUWARSA,
            ], true)) {
                // Gagal/kedaluwarsa: tagihan tetap belum_dibayar
                $pembayaran->update(['status_bayar' => $statusBaru === Transaksi::STATUS_GAGAL
                    ? Pembayaran::STATUS_GAGAL
                    : Pembayaran::STATUS_KEDALUWARSA]);
                // status_tagihan tetap belum_dibayar
            } elseif ($statusBaru === Transaksi::STATUS_MENUNGGU) {
                $pembayaran->update(['status_bayar' => Pembayaran::STATUS_MENUNGGU]);
            }
        });

        return ['status' => 200, 'message' => 'Notifikasi berhasil diproses.'];
    }

    // -----------------------------------------------------------------------
    // Pembantu
    // -----------------------------------------------------------------------

    /**
     * Petakan status dari Midtrans ke konstanta internal.
     */
    private function petakanStatus(string $transactionStatus, string $fraudStatus): string
    {
        return match (true) {
            $transactionStatus === 'settlement' => Transaksi::STATUS_BERHASIL,
            $transactionStatus === 'capture' && $fraudStatus === 'accept' => Transaksi::STATUS_BERHASIL,
            $transactionStatus === 'pending' => Transaksi::STATUS_MENUNGGU,
            in_array($transactionStatus, ['deny', 'cancel', 'failure'], true) => Transaksi::STATUS_GAGAL,
            $transactionStatus === 'expire' => Transaksi::STATUS_KEDALUWARSA,
            default => Transaksi::STATUS_MENUNGGU,
        };
    }
}
