<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Santri extends Model
{
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    protected $table = 'santri';
    protected $primaryKey = 'id_santri';
    public $timestamps = false;

    protected $fillable = [
        'id_pendaftar',
        'tanggal_masuk',
        'status_santri',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
        ];
    }

    public function pendaftar(): BelongsTo
    {
        return $this->belongsTo(Pendaftar::class, 'id_pendaftar', 'id_pendaftar');
    }
}
