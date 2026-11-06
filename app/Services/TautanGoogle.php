<?php

namespace App\Services;

use DomainException;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class TautanGoogle
{
    public function hashSub(string $sub): string
    {
        if ($sub === '') {
            throw new DomainException('Identitas Google tidak valid.');
        }

        return hash('sha256', $sub);
    }

    public function emailSamar(string $email): string
    {
        [$nama, $domain] = explode('@', $email, 2);

        return substr($nama, 0, 1).'***@'.strtolower($domain);
    }

    /** @return array{id_user: int, email_samar: string, ditautkan_pada: string}|null */
    public function akunDariSub(string $sub): ?array
    {
        return $this->bacaJson($this->path('sub-'.$this->hashSub($sub).'.json'));
    }

    /** @return array{sub_hash: string, email_samar: string, ditautkan_pada: string}|null */
    public function tautanPengguna(int $idUser): ?array
    {
        $tautan = $this->bacaJson($this->path('user-'.$idUser.'.json'));
        if (! is_array($tautan) || ! isset($tautan['sub_hash'])) {
            return null;
        }

        $akunGoogle = $this->bacaJson($this->path('sub-'.$tautan['sub_hash'].'.json'));
        if (! is_array($akunGoogle) || (int) ($akunGoogle['id_user'] ?? 0) !== $idUser) {
            return null;
        }

        return [
            'sub_hash' => $tautan['sub_hash'],
            'email_samar' => $akunGoogle['email_samar'],
            'ditautkan_pada' => $akunGoogle['ditautkan_pada'],
        ];
    }

    public function tautkan(int $idUser, string $sub, string $emailSamar): void
    {
        $this->tautkanHash($idUser, $this->hashSub($sub), $emailSamar);
    }

    public function tautkanHash(int $idUser, string $subHash, string $emailSamar): void
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $subHash)) {
            throw new DomainException('Identitas Google tidak valid.');
        }

        $subPath = $this->path('sub-'.$subHash.'.json');
        $userPath = $this->path('user-'.$idUser.'.json');
        $this->siapkanDirektori();

        $subLock = $this->kunci('sub-'.$subHash.'.lock');
        $userLock = null;
        $subFile = null;
        $tempPath = null;
        $subCreated = false;

        try {
            $userLock = $this->kunci('user-'.$idUser.'.lock');

            if (is_file($subPath) || $this->tautanPengguna($idUser) !== null) {
                throw new DomainException('Akun Google sudah tertaut atau sedang digunakan.');
            }

            $subFile = @fopen($subPath, 'x');
            if ($subFile === false) {
                throw new RuntimeException('Berkas tautan Google tidak dapat dibuat.');
            }
            $subCreated = true;
            @chmod($subPath, 0600);

            $dataSub = json_encode([
                'id_user' => $idUser,
                'email_samar' => $emailSamar,
                'ditautkan_pada' => now()->toIso8601String(),
            ], JSON_THROW_ON_ERROR);
            $this->tulisHandle($subFile, $dataSub);
            fclose($subFile);
            $subFile = null;

            $tempPath = tempnam(dirname($userPath), '.google-user-');
            if ($tempPath === false) {
                throw new RuntimeException('Berkas tautan pengguna tidak dapat disiapkan.');
            }

            $dataPengguna = json_encode(['sub_hash' => $subHash], JSON_THROW_ON_ERROR);
            if (file_put_contents($tempPath, $dataPengguna, LOCK_EX) !== strlen($dataPengguna)) {
                throw new RuntimeException('Berkas tautan pengguna tidak dapat ditulis.');
            }
            @chmod($tempPath, 0600);

            if (is_file($userPath) || ! @rename($tempPath, $userPath)) {
                throw new DomainException('Akun pengguna sudah memiliki tautan Google.');
            }
            $tempPath = null;
        } catch (Throwable $exception) {
            if (is_resource($subFile)) {
                fclose($subFile);
            }
            if ($subCreated && is_file($subPath)) {
                @unlink($subPath);
            }
            if (is_string($tempPath) && is_file($tempPath)) {
                @unlink($tempPath);
            }

            throw $exception;
        } finally {
            if (is_resource($userLock)) {
                flock($userLock, LOCK_UN);
                fclose($userLock);
            }
            flock($subLock, LOCK_UN);
            fclose($subLock);
        }
    }

    public function lepasTautan(int $idUser): bool
    {
        $tautan = $this->tautanPengguna($idUser);
        if ($tautan === null) {
            return false;
        }

        $subHash = $tautan['sub_hash'];
        $subLock = $this->kunci('sub-'.$subHash.'.lock');
        $userLock = null;
        $subPath = $this->path('sub-'.$subHash.'.json');
        $userPath = $this->path('user-'.$idUser.'.json');

        try {
            $userLock = $this->kunci('user-'.$idUser.'.lock');
            $tautanKini = $this->tautanPengguna($idUser);
            if ($tautanKini === null || $tautanKini['sub_hash'] !== $subHash) {
                return false;
            }

            $akunGoogle = $this->bacaJson($subPath);
            if (is_array($akunGoogle) && (int) ($akunGoogle['id_user'] ?? 0) === $idUser) {
                if (! @unlink($subPath)) {
                    throw new RuntimeException('Berkas tautan Google tidak dapat dihapus.');
                }
            }

            if (is_file($userPath) && ! @unlink($userPath)) {
                throw new RuntimeException('Berkas tautan pengguna tidak dapat dihapus.');
            }

            return true;
        } finally {
            if (is_resource($userLock)) {
                flock($userLock, LOCK_UN);
                fclose($userLock);
            }
            flock($subLock, LOCK_UN);
            fclose($subLock);
        }
    }

    private function path(string $nama): string
    {
        return Storage::disk('local')->path('google/'.$nama);
    }

    private function siapkanDirektori(): void
    {
        $direktori = dirname($this->path('tautan.json'));
        if (! is_dir($direktori) && ! @mkdir($direktori, 0700, true) && ! is_dir($direktori)) {
            throw new RuntimeException('Direktori tautan Google tidak dapat dibuat.');
        }
    }

    /** @return resource */
    private function kunci(string $nama): mixed
    {
        $path = $this->path($nama);
        $handle = @fopen($path, 'c+b');
        if ($handle === false || ! flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            throw new RuntimeException('Penyimpanan tautan Google sedang tidak tersedia.');
        }

        @chmod($path, 0600);

        return $handle;
    }

    /** @param resource $handle */
    private function tulisHandle(mixed $handle, string $isi): void
    {
        if (fwrite($handle, $isi) !== strlen($isi) || ! fflush($handle)) {
            throw new RuntimeException('Berkas tautan Google tidak dapat ditulis.');
        }
    }

    /** @return array<string, mixed>|null */
    private function bacaJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }
}
