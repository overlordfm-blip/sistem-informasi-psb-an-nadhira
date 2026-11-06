<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaksi extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_GAGAL = 'gagal';

    public const STATUS_KEDALUWARSA = 'kedaluwarsa';

    protected $table = 'transaksi';

    protected $primaryKey = 'id_transaksi';

    public $timestamps = false;

    protected $fillable = [
        'id_pembayaran',
        'order_id',
        'token',
        'metode',
        'status_transaksi',
    ];

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'id_pembayaran', 'id_pembayaran');
    }

    /**
     * Tanggal percobaan transaksi diturunkan dari segmen terakhir order_id
     * (format ANH-{id_tagihan}-{unix_timestamp}, zona Asia/Jakarta)
     * karena tabel transaksi tidak memiliki kolom waktu (timestamps).
     * Mengembalikan Carbon jika format valid, atau null bila format tidak sesuai.
     */
    protected function tanggalPercobaan(): Attribute
    {
        return Attribute::make(
            get: function (): ?Carbon {
                if (empty($this->order_id) || ! is_string($this->order_id)) {
                    return null;
                }

                // Format valid: ANH-{id_tagihan}-{unix_timestamp}
                if (! preg_match('/^ANH-(\d+)-(\d+)$/', $this->order_id, $matches)) {
                    return null;
                }

                $timestamp = (int) $matches[2];
                if ($timestamp <= 0) {
                    return null;
                }

                try {
                    return Carbon::createFromTimestamp($timestamp, 'Asia/Jakarta');
                } catch (\Throwable) {
                    return null;
                }
            }
        );
    }
}
