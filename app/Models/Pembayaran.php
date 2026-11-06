<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembayaran extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_GAGAL = 'gagal';

    public const STATUS_KEDALUWARSA = 'kedaluwarsa';

    protected $table = 'pembayaran';

    protected $primaryKey = 'id_pembayaran';

    public $timestamps = false;

    protected $fillable = [
        'id_tagihan',
        'tanggal_bayar',
        'jumlah_bayar',
        'status_bayar',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bayar' => 'date',
            'jumlah_bayar' => 'decimal:2',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'id_tagihan', 'id_tagihan');
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_pembayaran', 'id_pembayaran');
    }

    /**
     * Tanggal untuk riwayat pembayaran.
     * Bila pembayaran berhasil, memakai tanggal_bayar.
     * Bila tidak berhasil, memakai tanggal dari transaksi terakhir pembayaran itu yang
     * diturunkan dari segmen terakhir order_id (format ANH-{id_tagihan}-{unix_timestamp}, zona Asia/Jakarta)
     * karena tabel transaksi tidak memiliki kolom waktu (timestamps).
     * Bila tidak ada transaksi atau format order_id tidak sesuai, mengembalikan null.
     */
    protected function tanggalRiwayat(): Attribute
    {
        return Attribute::make(
            get: function (): ?Carbon {
                if ($this->status_bayar === self::STATUS_BERHASIL && $this->tanggal_bayar) {
                    return $this->tanggal_bayar;
                }

                $transaksiTerakhir = $this->transaksi->sortByDesc('id_transaksi')->first();

                return $transaksiTerakhir?->tanggalPercobaan;
            }
        );
    }
}
