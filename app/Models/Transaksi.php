<?php

namespace App\Models;

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
}
