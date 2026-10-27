<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformasiPondok extends Model
{
    protected $table = 'informasi_pondok';
    protected $primaryKey = 'id_informasi';
    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'judul',
        'isi',
        'diperbarui',
    ];

    protected function casts(): array
    {
        return [
            'diperbarui' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
