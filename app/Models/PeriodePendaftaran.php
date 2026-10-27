<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodePendaftaran extends Model
{
    public const STATUS_DIBUKA = 'dibuka';
    public const STATUS_DITUTUP = 'ditutup';

    protected $table = 'periode_pendaftaran';
    protected $primaryKey = 'id_periode';
    public $timestamps = false;

    protected $fillable = [
        'nama_periode',
        'tanggal_buka',
        'tanggal_tutup',
        'status_periode',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_buka' => 'date',
            'tanggal_tutup' => 'date',
        ];
    }

    public function isDibuka(): bool
    {
        return $this->status_periode === self::STATUS_DIBUKA;
    }

    public function pendaftar(): HasMany
    {
        return $this->hasMany(Pendaftar::class, 'id_periode', 'id_periode');
    }
}
