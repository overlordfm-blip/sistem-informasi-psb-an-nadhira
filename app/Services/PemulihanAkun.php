<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;

class PemulihanAkun
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DIBUKA = 'dibuka';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_KEDALUWARSA = 'kedaluwarsa';

    private const ALFABET_KODE = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function buatPermintaan(array $data): array
    {
        return $this->buatData($data, self::STATUS_MENUNGGU, null, null);
    }

    public function buatAksesOffline(array $data, int $idUser, int $idAdmin): array
    {
        return $this->buatData($data, self::STATUS_DIBUKA, $idUser, $idAdmin);
    }

    public function ambil(?string $kode): ?array
    {
        if (! $this->kodeValid($kode)) {
            return null;
        }

        return $this->denganKunci(function (string $direktori) use ($kode): ?array {
            $permintaan = $this->bacaBerkas($direktori, $kode);

            if ($permintaan === null) {
                return null;
            }

            if ($this->menungguKedaluwarsa($permintaan) || $this->aksesKedaluwarsa($permintaan)) {
                $permintaan['status'] = self::STATUS_KEDALUWARSA;
                $this->tulisBerkas($direktori, $permintaan);
            }

            return $permintaan;
        });
    }

    public function buka(string $kode, int $idUser, int $idAdmin): ?array
    {
        if (! $this->kodeValid($kode)) {
            return null;
        }

        return $this->denganKunci(function (string $direktori) use ($kode, $idUser, $idAdmin): ?array {
            $permintaan = $this->bacaBerkas($direktori, $kode);

            if ($permintaan === null || $permintaan['status'] !== self::STATUS_MENUNGGU) {
                return null;
            }

            if ($this->menungguKedaluwarsa($permintaan)) {
                $permintaan['status'] = self::STATUS_KEDALUWARSA;
                $this->tulisBerkas($direktori, $permintaan);

                return null;
            }

            $dibukaPada = now();
            $permintaan['id_user'] = $idUser;
            $permintaan['status'] = self::STATUS_DIBUKA;
            $permintaan['dibuka_oleh'] = $idAdmin;
            $permintaan['dibuka_pada'] = $dibukaPada->toIso8601String();
            $permintaan['berlaku_sampai'] = $dibukaPada->copy()->addMinutes(30)->toIso8601String();
            $this->tulisBerkas($direktori, $permintaan);

            return $permintaan;
        });
    }

    public function hapus(string $kode): bool
    {
        if (! $this->kodeValid($kode)) {
            return false;
        }

        return $this->denganKunci(function (string $direktori) use ($kode): bool {
            $path = $this->pathBerkas($direktori, $kode);

            return is_file($path) ? unlink($path) : false;
        });
    }

    public function selesaikan(string $kode, callable $simpanPassword): bool
    {
        if (! $this->kodeValid($kode)) {
            return false;
        }

        return $this->denganKunci(function (string $direktori) use ($kode, $simpanPassword): bool {
            $permintaan = $this->bacaBerkas($direktori, $kode);

            if ($permintaan === null || $permintaan['status'] !== self::STATUS_DIBUKA) {
                return false;
            }

            if ($this->aksesKedaluwarsa($permintaan)) {
                $permintaan['status'] = self::STATUS_KEDALUWARSA;
                $this->tulisBerkas($direktori, $permintaan);

                return false;
            }

            if ($simpanPassword($permintaan) !== true) {
                return false;
            }

            $permintaan['status'] = self::STATUS_SELESAI;
            $this->tulisBerkas($direktori, $permintaan);

            return true;
        });
    }

    public function daftarMenunggu(): array
    {
        return $this->denganKunci(function (string $direktori): array {
            $this->bersihkanDiDirektori($direktori);

            return array_values(array_filter(
                $this->semuaBerkas($direktori),
                fn (array $permintaan): bool => $permintaan['status'] === self::STATUS_MENUNGGU
            ));
        });
    }

    public function jumlahMenunggu(): int
    {
        return $this->denganKunci(function (string $direktori): int {
            return count(array_filter(
                $this->semuaBerkas($direktori),
                fn (array $permintaan): bool => $permintaan['status'] === self::STATUS_MENUNGGU
                    && ! $this->menungguKedaluwarsa($permintaan)
            ));
        });
    }

    public function bersihkan(): int
    {
        return $this->denganKunci(fn (string $direktori): int => $this->bersihkanDiDirektori($direktori));
    }

    private function buatData(array $data, string $status, ?int $idUser, ?int $idAdmin): array
    {
        return $this->denganKunci(function (string $direktori) use ($data, $status, $idUser, $idAdmin): array {
            do {
                $kode = $this->buatKode();
            } while (is_file($this->pathBerkas($direktori, $kode)));

            $dibuatPada = now();
            $permintaan = [
                'kode' => $kode,
                'nama_user_input' => $data['nama_user_input'] ?? null,
                'nama_lengkap' => $data['nama_lengkap'],
                'nama_wali' => $data['nama_wali'],
                'status' => $status,
                'dibuat_pada' => $dibuatPada->toIso8601String(),
                'id_user' => $idUser,
                'dibuka_oleh' => $idAdmin,
                'dibuka_pada' => $status === self::STATUS_DIBUKA ? $dibuatPada->toIso8601String() : null,
                'berlaku_sampai' => $status === self::STATUS_DIBUKA ? $dibuatPada->copy()->addMinutes(30)->toIso8601String() : null,
            ];

            $this->tulisBerkas($direktori, $permintaan);

            return $permintaan;
        });
    }

    private function denganKunci(callable $callback): mixed
    {
        $disk = Storage::disk('local');
        $direktori = $disk->path('pemulihan');

        if (! is_dir($direktori) && ! mkdir($direktori, 0700, true) && ! is_dir($direktori)) {
            throw new RuntimeException('Penyimpanan pemulihan tidak dapat disiapkan.');
        }

        $lock = fopen($disk->path('.pemulihan.lock'), 'c');

        if ($lock === false || ! flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new RuntimeException('Penyimpanan pemulihan tidak dapat dikunci.');
        }

        try {
            return $callback($direktori);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function tulisBerkas(string $direktori, array $permintaan): void
    {
        $path = $this->pathBerkas($direktori, $permintaan['kode']);
        $sementara = tempnam($direktori, '.pemulihan-');

        if ($sementara === false) {
            throw new RuntimeException('Berkas pemulihan tidak dapat dibuat.');
        }

        try {
            $json = json_encode($permintaan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $jumlahByte = file_put_contents($sementara, $json, LOCK_EX);

            if ($jumlahByte !== strlen($json)) {
                throw new RuntimeException('Berkas pemulihan tidak dapat ditulis.');
            }

            @chmod($sementara, 0600);

            if (! rename($sementara, $path)) {
                throw new RuntimeException('Berkas pemulihan tidak dapat disimpan.');
            }
        } catch (JsonException $exception) {
            throw new RuntimeException('Data pemulihan tidak dapat dikodekan.', previous: $exception);
        } finally {
            if (is_file($sementara)) {
                unlink($sementara);
            }
        }
    }

    private function bacaBerkas(string $direktori, string $kode): ?array
    {
        $path = $this->pathBerkas($direktori, $kode);

        if (! is_file($path)) {
            return null;
        }

        try {
            $permintaan = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($permintaan) || ($permintaan['kode'] ?? null) !== $kode) {
            return null;
        }

        return $permintaan;
    }

    private function semuaBerkas(string $direktori): array
    {
        $permintaan = [];

        foreach (glob($direktori.DIRECTORY_SEPARATOR.'*.json') ?: [] as $path) {
            $kode = basename($path, '.json');

            if (! $this->kodeValid($kode)) {
                continue;
            }

            $data = $this->bacaBerkas($direktori, $kode);

            if ($data !== null && isset($data['status'], $data['dibuat_pada'])) {
                $permintaan[] = $data;
            }
        }

        return $permintaan;
    }

    private function bersihkanDiDirektori(string $direktori): int
    {
        $jumlahDihapus = 0;

        foreach ($this->semuaBerkas($direktori) as $permintaan) {
            $harusDihapus = in_array($permintaan['status'], [self::STATUS_SELESAI, self::STATUS_KEDALUWARSA], true)
                || $this->menungguKedaluwarsa($permintaan)
                || $this->aksesKedaluwarsa($permintaan);

            if ($harusDihapus && unlink($this->pathBerkas($direktori, $permintaan['kode']))) {
                $jumlahDihapus++;
            }
        }

        return $jumlahDihapus;
    }

    private function menungguKedaluwarsa(array $permintaan): bool
    {
        return $permintaan['status'] === self::STATUS_MENUNGGU
            && Carbon::parse($permintaan['dibuat_pada'])->addHours(24)->lessThanOrEqualTo(now());
    }

    private function aksesKedaluwarsa(array $permintaan): bool
    {
        return $permintaan['status'] === self::STATUS_DIBUKA
            && isset($permintaan['berlaku_sampai'])
            && Carbon::parse($permintaan['berlaku_sampai'])->lessThanOrEqualTo(now());
    }

    private function buatKode(): string
    {
        $kode = '';
        $jumlahKarakter = strlen(self::ALFABET_KODE);

        for ($indeks = 0; $indeks < 8; $indeks++) {
            $kode .= self::ALFABET_KODE[random_int(0, $jumlahKarakter - 1)];
        }

        return $kode;
    }

    private function kodeValid(?string $kode): bool
    {
        return $kode !== null && preg_match('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/D', $kode) === 1;
    }

    private function pathBerkas(string $direktori, string $kode): string
    {
        return $direktori.DIRECTORY_SEPARATOR.$kode.'.json';
    }
}
