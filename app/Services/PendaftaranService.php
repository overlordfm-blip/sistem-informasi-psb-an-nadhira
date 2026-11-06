<?php

namespace App\Services;

use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PendaftaranService
{
    public function __construct(
        private CatatanPenolakan $catatanPenolakan,
        private TesSeleksiService $tesSeleksiService,
    ) {}

    /**
     * Dapatkan periode pendaftaran yang saat ini sedang dibuka.
     */
    public function getPeriodeTersedia(): ?PeriodePendaftaran
    {
        return PeriodePendaftaran::query()
            ->tersedia()
            ->orderByDesc('tanggal_buka')
            ->orderByDesc('id_periode')
            ->first();
    }

    public function sisaKuota(PeriodePendaftaran $periode): ?int
    {
        if (! PeriodePendaftaran::query()->tersedia()->whereKey($periode->id_periode)->exists()) {
            return 0;
        }

        $kuota = (int) config('pondok.kuota', 0);
        if ($kuota <= 0) {
            return null;
        }

        $jumlahPendaftar = Pendaftar::where('id_periode', $periode->id_periode)->count();

        return max(0, $kuota - $jumlahPendaftar);
    }

    /**
     * Periksa kelayakan peran calon santri untuk mendaftar.
     * Calon santri layak mendaftar jika:
     * - Ada periode yang sedang dibuka, DAN
     * - Belum punya pendaftar, ATAU pendaftarnya berstatus ditolak dengan id_periode berbeda dari periode yang sedang dibuka.
     */
    public function cekKelayakan(User $user, ?PeriodePendaftaran $periode = null): array
    {
        $periode = $periode ?? $this->getPeriodeTersedia();
        if (! $periode || ! $periode->tersedia()) {
            return [
                'layak' => false,
                'alasan' => 'periode_ditutup',
                'pesan' => 'Pendaftaran sedang ditutup.',
            ];
        }

        $pendaftar = $user->pendaftar;
        if (! $pendaftar) {
            return [
                'layak' => true,
                'alasan' => 'baru',
                'pendaftar' => null,
            ];
        }

        // Jika pendaftar berstatus ditolak pada periode yang berbeda dari periode dibuka
        if ($pendaftar->status_daftar === Pendaftar::STATUS_DITOLAK && $pendaftar->id_periode !== $periode->id_periode) {
            return [
                'layak' => true,
                'alasan' => 'pembaruan',
                'pendaftar' => $pendaftar,
            ];
        }

        // Jika pendaftar ditolak pada periode yang sedang dibuka
        if ($pendaftar->status_daftar === Pendaftar::STATUS_DITOLAK && $pendaftar->id_periode === $periode->id_periode) {
            return [
                'layak' => false,
                'alasan' => 'ditolak_periode_ini',
                'pesan' => 'Pendaftaran Anda pada periode ini tidak diterima. Silakan menunggu periode berikutnya.',
            ];
        }

        // Sedang diproses atau diterima
        return [
            'layak' => false,
            'alasan' => 'sudah_mendaftar',
            'pesan' => __('pendaftaran.sudah_mendaftar'),
        ];
    }

    /**
     * Daftarkan calon santri baru atau perbarui pendaftaran lama yang ditolak pada periode sebelumnya.
     * Dijalankan dalam DB::transaction dengan lockForUpdate pada baris pendaftar milik pengguna.
     * Tidak boleh ada tagihan yang terbuat pada proses ini.
     *
     * @throws RuntimeException
     */
    public function daftarkan(User $user, array $data): Pendaftar
    {
        return DB::transaction(function () use ($user, $data) {
            // 1. Periksa ketersediaan periode yang dibuka
            $periode = PeriodePendaftaran::query()->tersedia()
                ->lockForUpdate()
                ->first();
            if (! $periode) {
                throw new RuntimeException('Pendaftaran tidak dapat diproses karena periode pendaftaran sedang ditutup.');
            }

            $kuota = (int) config('pondok.kuota', 0);
            if ($kuota > 0 && Pendaftar::where('id_periode', $periode->id_periode)->count() >= $kuota) {
                throw new RuntimeException('Kuota pendaftaran periode ini telah penuh');
            }

            // 2. Lock baris pendaftar milik pengguna jika sudah ada
            $pendaftar = Pendaftar::where('id_user', $user->id_user)
                ->lockForUpdate()
                ->first();

            // 3. Periksa ulang kelayakan mendaftar
            if ($pendaftar) {
                if ($pendaftar->status_daftar === Pendaftar::STATUS_DIPROSES || $pendaftar->status_daftar === Pendaftar::STATUS_DITERIMA) {
                    throw new RuntimeException(__('pendaftaran.sudah_mendaftar'));
                }

                if ($pendaftar->status_daftar === Pendaftar::STATUS_DITOLAK && $pendaftar->id_periode === $periode->id_periode) {
                    throw new RuntimeException('Pendaftaran Anda pada periode ini tidak diterima. Silakan menunggu periode berikutnya.');
                }

                if ($pendaftar->status_daftar === Pendaftar::STATUS_DITOLAK && $pendaftar->id_periode !== $periode->id_periode) {
                    // Perbarui baris pendaftar yang sama: data baru, id_periode periode dibuka, status kembali diproses
                    $pendaftar->update([
                        'id_periode' => $periode->id_periode,
                        'nama_lengkap' => $data['nama_lengkap'],
                        'tanggal_lahir' => $data['tanggal_lahir'],
                        'alamat' => $data['alamat'],
                        'nama_wali' => $data['nama_wali'],
                        'no_telepon' => $data['no_telepon'],
                        'status_daftar' => Pendaftar::STATUS_DIPROSES,
                    ]);
                    $this->catatanPenolakan->hapus($pendaftar->id_pendaftar);
                    $this->tesSeleksiService->hapus($pendaftar->id_pendaftar);

                    return $pendaftar;
                }

                throw new RuntimeException('Status pendaftaran Anda tidak memenuhi syarat untuk mendaftar.');
            }

            // 4. Jika belum ada, buat baris pendaftar baru
            return Pendaftar::create([
                'id_user' => $user->id_user,
                'id_periode' => $periode->id_periode,
                'nama_lengkap' => $data['nama_lengkap'],
                'tanggal_lahir' => $data['tanggal_lahir'],
                'alamat' => $data['alamat'],
                'nama_wali' => $data['nama_wali'],
                'no_telepon' => $data['no_telepon'],
                'status_daftar' => Pendaftar::STATUS_DIPROSES,
            ]);
        });
    }
}
