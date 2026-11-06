<?php

namespace App\Services;

use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production', false);
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
        Config::$curlOptions = [];
    }

    /**
     * Dapatkan Snap token dari Midtrans.
     *
     * @param  array<string, mixed>  $params
     */
    public function getSnapToken(array $params): string
    {
        return Snap::getSnapToken($params);
    }

    /**
     * Kembalikan server key aktif (untuk pembuatan signature verifikasi webhook).
     */
    public function getServerKey(): string
    {
        return (string) config('services.midtrans.server_key');
    }

    /**
     * Dapatkan status transaksi dari API Midtrans (jika ada).
     */
    public function getTransactionStatus(string $orderId): ?object
    {
        try {
            $status = Transaction::status($orderId);

            return is_object($status) ? $status : (object) $status;
        } catch (\Throwable) {
            return null;
        }
    }
}
