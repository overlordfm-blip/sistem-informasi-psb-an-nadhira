<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tagihan extends Model
{
    public const JENIS_PENDAFTARAN = 'pendaftaran';
    public const JENIS_SYAHRIYAH = 'syahriyah';

    public const STATUS_BELUM_DIBAYAR = 'belum_dibayar';
    public const STATUS_LUNAS = 'lunas';

    protected $table = 'tagihan';
    protected $primaryKey = 'id_tagihan';
    public $timestamps = false;

    protected $fillable = [
        'id_pendaftar',
        'jenis_tagihan',
        'nominal',
        'status_tagihan',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
        ];
    }

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class, 'id_pendaftar', 'id_pendaftar');
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class, 'id_tagihan', 'id_tagihan');
    }
}
