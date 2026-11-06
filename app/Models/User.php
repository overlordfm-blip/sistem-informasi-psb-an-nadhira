<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const PERAN_ADMIN = 'admin';

    public const PERAN_CALON_SANTRI = 'calon_santri';

    public const PERAN_SANTRI = 'santri';

    protected $table = 'users';

    protected $primaryKey = 'id_user';

    public $timestamps = false;

    protected $fillable = [
        'nama_user',
        'password',
        'peran',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function isAdmin(): bool
    {
        return $this->peran === self::PERAN_ADMIN;
    }

    public function isCalonSantri(): bool
    {
        return $this->peran === self::PERAN_CALON_SANTRI;
    }

    public function isSantri(): bool
    {
        return $this->peran === self::PERAN_SANTRI;
    }

    public function pendaftar(): HasOne
    {
        return $this->hasOne(Pendaftar::class, 'id_user', 'id_user');
    }

    public function getNamaTampilanAttribute(): string
    {
        return $this->pendaftar?->nama_lengkap ?: $this->nama_user;
    }

    public function informasiPondok(): HasMany
    {
        return $this->hasMany(InformasiPondok::class, 'id_user', 'id_user');
    }
}
