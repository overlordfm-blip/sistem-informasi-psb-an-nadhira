<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pendaftar extends Model
{
    public const STATUS_DIPROSES = 'diproses';
    public const STATUS_DITERIMA = 'diterima';
    public const STATUS_DITOLAK = 'ditolak';

    protected $table = 'pendaftar';
    protected $primaryKey = 'id_pendaftar';
    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'id_periode',
        'nama_lengkap',
        'tanggal_lahir',
        'alamat',
        'nama_wali',
        'no_telepon',
        'status_daftar',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodePendaftaran::class, 'id_periode', 'id_periode');
    }

    public function santri(): HasOne
    {
        return $this->hasOne(Santri::class, 'id_pendaftar', 'id_pendaftar');
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class, 'id_pendaftar', 'id_pendaftar');
    }
}
