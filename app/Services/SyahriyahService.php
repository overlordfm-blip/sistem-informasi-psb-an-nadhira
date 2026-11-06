<?php

namespace App\Services;

use App\Models\Santri;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;

class SyahriyahService
{
    /**
     * Buat tagihan syahriyah untuk semua atau satu santri, dengan alasan setiap pengecualian.
     *
     * @return array{dibuat: int, dilewati: int, alasan: list<array{nama: string, alasan: string}>}
     */
    public function buat(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $query = Santri::query()
                ->with(['pendaftar.tagihan.pembayaran'])
                ->orderBy('id_santri')
                ->lockForUpdate();

            if ($data['sasaran'] === 'satu') {
                $query->where('id_santri', $data['id_santri']);
            }

            $dibuat = 0;
            $alasan = [];
            $bulanSekarang = now('Asia/Jakarta')->format('Y-m');

            foreach ($query->get() as $santri) {
                $pendaftar = $santri->pendaftar;
                $nama = $pendaftar?->nama_lengkap ?? 'Santri #'.$santri->id_santri;

                if ($santri->status_santri !== Santri::STATUS_AKTIF) {
                    $alasan[] = ['nama' => $nama, 'alasan' => 'Status santri tidak aktif.'];

                    continue;
                }

                $syahriyah = $pendaftar?->tagihan
                    ->where('jenis_tagihan', Tagihan::JENIS_SYAHRIYAH)
                    ->sortByDesc('id_tagihan');

                $belumDibayar = $syahriyah?->firstWhere('status_tagihan', Tagihan::STATUS_BELUM_DIBAYAR);
                if ($belumDibayar) {
                    $alasan[] = ['nama' => $nama, 'alasan' => 'Masih memiliki tagihan syahriyah yang belum dibayar.'];

                    continue;
                }

                $tagihanTerakhir = $syahriyah?->first();
                $tanggalBayarTerakhir = $tagihanTerakhir?->pembayaran?->tanggal_bayar;
                if (
                    ! ($data['paksa'] ?? false)
                    && $tagihanTerakhir?->status_tagihan === Tagihan::STATUS_LUNAS
                    && $tanggalBayarTerakhir?->format('Y-m') === $bulanSekarang
                ) {
                    $alasan[] = ['nama' => $nama, 'alasan' => 'Tagihan syahriyah terakhir sudah lunas pada bulan ini.'];

                    continue;
                }

                Tagihan::create([
                    'id_pendaftar' => $pendaftar->id_pendaftar,
                    'jenis_tagihan' => Tagihan::JENIS_SYAHRIYAH,
                    'nominal' => (int) $data['nominal'],
                    'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
                ]);
                $dibuat++;
            }

            return [
                'dibuat' => $dibuat,
                'dilewati' => count($alasan),
                'alasan' => $alasan,
            ];
        });
    }
}
