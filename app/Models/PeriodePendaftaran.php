<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function tersedia(): bool
    {
        $hariIni = today('Asia/Jakarta');

        return $this->status_periode === self::STATUS_DIBUKA
            && $this->tanggal_buka !== null
            && $this->tanggal_tutup !== null
            && $this->tanggal_buka->lte($hariIni)
            && $this->tanggal_tutup->gte($hariIni);
    }

    public function labelStatus(): string
    {
        if ($this->status_periode !== self::STATUS_DIBUKA) {
            return 'Ditutup';
        }

        if ($this->tanggal_buka && $this->tanggal_buka->gt(today('Asia/Jakarta'))) {
            return 'Dijadwalkan';
        }

        return $this->tersedia() ? 'Dibuka' : 'Ditutup';
    }

    public function scopeTersedia(Builder $query): Builder
    {
        $hariIni = today('Asia/Jakarta')->toDateString();

        return $query->where('status_periode', self::STATUS_DIBUKA)
            ->whereDate('tanggal_buka', '<=', $hariIni)
            ->whereDate('tanggal_tutup', '>=', $hariIni);
    }

    public function pendaftar(): HasMany
    {
        return $this->hasMany(Pendaftar::class, 'id_periode', 'id_periode');
    }
}
