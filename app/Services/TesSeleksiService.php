<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;

class TesSeleksiService
{
    public const MODE_ONLINE = 'online';

    public const MODE_OFFLINE = 'offline';

    public const HASIL_BELUM = 'belum';

    public const HASIL_LULUS = 'lulus';

    public const HASIL_TIDAK_LULUS = 'tidak_lulus';

    public function pengaturan(): array
    {
        return $this->denganKunci(fn ($disk): array => $this->bacaJson($disk->path('pengaturan/tes.json')) ?? $this->pengaturanDefault());
    }

    public function simpanPengaturan(array $pengaturan): void
    {
        $this->denganKunci(function ($disk) use ($pengaturan): void {
            $this->tulisAtomik($disk->path('pengaturan/tes.json'), $pengaturan);
        });
    }

    public function baca(int $idPendaftar): array
    {
        $this->validasiId($idPendaftar);

        return $this->denganKunci(fn ($disk): array => $this->bacaJson($this->pathPendaftar($disk, $idPendaftar)) ?? $this->dataDefault());
    }

    public function pilihMode(int $idPendaftar, string $mode): array
    {
        $this->validasiId($idPendaftar);

        if (! in_array($mode, [self::MODE_ONLINE, self::MODE_OFFLINE], true)) {
            throw new RuntimeException('Mode tes tidak valid.');
        }

        return $this->denganKunci(function ($disk) use ($idPendaftar, $mode): array {
            $pengaturan = $this->bacaJson($disk->path('pengaturan/tes.json')) ?? $this->pengaturanDefault();

            if (! ($pengaturan[$mode]['aktif'] ?? false)) {
                throw new RuntimeException('Mode tes tersebut sedang tidak aktif.');
            }

            $path = $this->pathPendaftar($disk, $idPendaftar);
            $data = $this->bacaJson($path) ?? $this->dataDefault();

            if ($data['hasil'] !== self::HASIL_BELUM) {
                throw new RuntimeException('Mode tes tidak dapat diubah setelah hasil dinilai.');
            }
            if (is_array($data['jadwal_online'] ?? null) && $mode !== self::MODE_ONLINE) {
                throw new RuntimeException('Mode tidak dapat diubah setelah jadwal tes online diberikan.');
            }

            $data['mode'] = $mode;
            $data['dipilih_pada'] = now('Asia/Jakarta')->toIso8601String();
            $this->tulisAtomik($path, $data);

            return $data;
        });
    }

    public function jadwalkanOnline(int $idPendaftar, string $tanggal, string $jam, int $idAdmin): array
    {
        $this->validasiId($idPendaftar);

        return $this->denganKunci(function ($disk) use ($idPendaftar, $tanggal, $jam, $idAdmin): array {
            $path = $this->pathPendaftar($disk, $idPendaftar);
            $data = $this->bacaJson($path) ?? $this->dataDefault();

            if ($data['mode'] !== self::MODE_ONLINE || $data['hasil'] !== self::HASIL_BELUM) {
                throw new RuntimeException('Calon santri belum memilih tes online atau hasil tes sudah dinilai.');
            }
            if (is_array($data['jadwal_online'] ?? null)) {
                throw new RuntimeException('Calon santri ini sudah memiliki jadwal tes online.');
            }

            $waktuTes = $tanggal.' '.$jam;
            $carbonTes = Carbon::createFromFormat('!Y-m-d H:i', $waktuTes, 'Asia/Jakarta');
            if ($carbonTes === false || $carbonTes->format('Y-m-d H:i') !== $waktuTes || $carbonTes->lessThanOrEqualTo(now('Asia/Jakarta'))) {
                throw new RuntimeException('Jadwal tes harus berupa waktu mendatang yang valid.');
            }

            foreach (glob($disk->path('tes/pendaftar-*.json')) ?: [] as $berkas) {
                if ($berkas === $path) {
                    continue;
                }

                $jadwalLain = $this->bacaJson($berkas)['jadwal_online'] ?? null;
                if (is_array($jadwalLain)
                    && ($jadwalLain['tanggal'] ?? null) === $tanggal
                    && ($jadwalLain['jam'] ?? null) === $jam) {
                    throw new RuntimeException('Slot waktu tersebut sudah digunakan calon santri lain. Pilih tanggal atau jam yang berbeda.');
                }
            }

            $data['jadwal_online'] = [
                'tanggal' => $tanggal,
                'jam' => $jam,
                'ditetapkan_oleh' => $idAdmin,
                'ditetapkan_pada' => now('Asia/Jakarta')->toIso8601String(),
            ];
            $this->tulisAtomik($path, $data);

            return $data['jadwal_online'];
        });
    }

    public function simpanHasil(int $idPendaftar, string $hasil, ?int $nilai, ?string $catatan, int $idAdmin): array
    {
        $this->validasiId($idPendaftar);

        if (! in_array($hasil, [self::HASIL_BELUM, self::HASIL_LULUS, self::HASIL_TIDAK_LULUS], true)) {
            throw new RuntimeException('Hasil tes tidak valid.');
        }

        return $this->denganKunci(function ($disk) use ($idPendaftar, $hasil, $nilai, $catatan, $idAdmin): array {
            $path = $this->pathPendaftar($disk, $idPendaftar);
            $data = $this->bacaJson($path) ?? $this->dataDefault();

            if ($hasil !== self::HASIL_BELUM && $data['mode'] === null) {
                throw new RuntimeException('Pendaftar belum memilih mode tes.');
            }

            $data['hasil'] = $hasil;
            $data['nilai'] = $hasil === self::HASIL_BELUM ? null : $nilai;
            $data['catatan'] = $hasil === self::HASIL_BELUM ? null : $catatan;
            $data['dinilai_oleh'] = $hasil === self::HASIL_BELUM ? null : $idAdmin;
            $data['dinilai_pada'] = $hasil === self::HASIL_BELUM ? null : now('Asia/Jakarta')->toIso8601String();
            $this->tulisAtomik($path, $data);

            return $data;
        });
    }

    public function hapus(int $idPendaftar): void
    {
        $this->validasiId($idPendaftar);

        $this->denganKunci(function ($disk) use ($idPendaftar): void {
            $path = $this->pathPendaftar($disk, $idPendaftar);

            if (is_file($path) && ! unlink($path)) {
                throw new RuntimeException('Data tes seleksi tidak dapat dihapus.');
            }
        });
    }

    public function status(array $data): string
    {
        if ($data['mode'] === null) {
            return 'belum_memilih';
        }

        return match ($data['hasil']) {
            self::HASIL_LULUS => self::HASIL_LULUS,
            self::HASIL_TIDAK_LULUS => self::HASIL_TIDAK_LULUS,
            default => 'menunggu',
        };
    }

    private function pengaturanDefault(): array
    {
        return [
            self::MODE_ONLINE => [
                'aktif' => false,
                'tautan' => '',
                'petunjuk' => '',
            ],
            self::MODE_OFFLINE => [
                'aktif' => false,
                'tanggal' => '',
                'jam' => '',
                'lokasi' => '',
                'petunjuk' => '',
            ],
        ];
    }

    private function dataDefault(): array
    {
        return [
            'mode' => null,
            'dipilih_pada' => null,
            'jadwal_online' => null,
            'hasil' => self::HASIL_BELUM,
            'nilai' => null,
            'catatan' => null,
            'dinilai_oleh' => null,
            'dinilai_pada' => null,
        ];
    }

    private function denganKunci(callable $callback): mixed
    {
        $disk = Storage::disk('local');
        $akarPrivate = $disk->path('');

        if (! is_dir($akarPrivate) && ! mkdir($akarPrivate, 0700, true) && ! is_dir($akarPrivate)) {
            throw new RuntimeException('Penyimpanan tes seleksi tidak dapat disiapkan.');
        }

        $lock = fopen($disk->path('.tes-seleksi.lock'), 'c');

        if ($lock === false || ! flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new RuntimeException('Penyimpanan tes seleksi tidak dapat dikunci.');
        }

        try {
            return $callback($disk);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function bacaJson(string $path): ?array
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Berkas tes seleksi tidak dapat dibaca.', previous: $exception);
        }

        if (! is_array($data)) {
            throw new RuntimeException('Berkas tes seleksi tidak valid.');
        }

        return $data;
    }

    private function tulisAtomik(string $path, array $data): void
    {
        $direktori = dirname($path);

        if (! is_dir($direktori) && ! mkdir($direktori, 0700, true) && ! is_dir($direktori)) {
            throw new RuntimeException('Direktori tes seleksi tidak dapat dibuat.');
        }

        $sementara = tempnam($direktori, '.tes-');

        if ($sementara === false) {
            throw new RuntimeException('Berkas tes seleksi tidak dapat dibuat.');
        }

        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (file_put_contents($sementara, $json, LOCK_EX) !== strlen($json)) {
                throw new RuntimeException('Berkas tes seleksi tidak dapat ditulis.');
            }

            @chmod($sementara, 0600);

            if (! rename($sementara, $path)) {
                throw new RuntimeException('Berkas tes seleksi tidak dapat disimpan.');
            }
        } catch (JsonException $exception) {
            throw new RuntimeException('Data tes seleksi tidak dapat dikodekan.', previous: $exception);
        } finally {
            if (is_file($sementara)) {
                unlink($sementara);
            }
        }
    }

    private function pathPendaftar($disk, int $idPendaftar): string
    {
        return $disk->path("tes/pendaftar-{$idPendaftar}.json");
    }

    private function validasiId(int $idPendaftar): void
    {
        if ($idPendaftar < 1) {
            throw new RuntimeException('ID pendaftar tidak valid.');
        }
    }
}
