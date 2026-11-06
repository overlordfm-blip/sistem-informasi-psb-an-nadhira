<?php

namespace App\Support;

class NomorTelepon
{
    public static function normalisasi(?string $nomorTelepon): ?string
    {
        if ($nomorTelepon === null) {
            return null;
        }

        $nomor = preg_replace('/[\s().-]/u', '', trim($nomorTelepon));

        if ($nomor === null || $nomor === '') {
            return null;
        }

        if (str_starts_with($nomor, '+62')) {
            $nomor = substr($nomor, 1);
        } elseif (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        }

        return preg_match('/^\d{10,15}$/D', $nomor) === 1 ? $nomor : null;
    }
}
