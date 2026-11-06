<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CatatanPenolakan
{
    public function simpan(int $idPendaftar, string $catatan): void
    {
        $berhasil = Storage::disk('local')->put($this->path($idPendaftar), $catatan);

        if (! $berhasil) {
            throw new RuntimeException('Catatan penolakan gagal disimpan.');
        }
    }

    public function baca(int $idPendaftar): ?string
    {
        $disk = Storage::disk('local');
        $path = $this->path($idPendaftar);

        return $disk->exists($path) ? $disk->get($path) : null;
    }

    public function hapus(int $idPendaftar): void
    {
        $disk = Storage::disk('local');
        $path = $this->path($idPendaftar);

        if ($disk->exists($path) && ! $disk->delete($path)) {
            throw new RuntimeException('Catatan penolakan gagal dihapus.');
        }
    }

    private function path(int $idPendaftar): string
    {
        return "catatan/pendaftar-{$idPendaftar}.txt";
    }
}
