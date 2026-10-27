<?php

namespace App\Models;

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
}
