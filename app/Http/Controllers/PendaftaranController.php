<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendaftaranRequest;
use App\Models\Pendaftar;
use App\Services\CatatanPenolakan;
use App\Services\PendaftaranService;
use App\Services\TautanGoogle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PendaftaranController extends Controller
{
    /**
     * Tampilkan formulir pendaftaran santri baru.
     * Jika sudah terdaftar, dialihkan ke /status-pendaftaran.
     * Jika periode ditutup, ditampilkan pemberitahuan pendaftaran ditutup.
     */
    public function tampilFormulir(Request $request, PendaftaranService $service): View|RedirectResponse
    {
        $user = $request->user();
        $periode = $service->getPeriodeTersedia();

        // Jika tidak ada periode yang sedang dibuka
        if (! $periode) {
            $pendaftar = $user->pendaftar;
            if ($pendaftar && in_array($pendaftar->status_daftar, [Pendaftar::STATUS_DIPROSES, Pendaftar::STATUS_DITERIMA], true)) {
                return redirect()->route('status-pendaftaran')
                    ->with('info', __('pendaftaran.sudah_mendaftar'));
            }
            if ($pendaftar && $pendaftar->status_daftar === Pendaftar::STATUS_DITOLAK) {
                return redirect()->route('status-pendaftaran')
                    ->with('info', __('pendaftaran.ditolak'));
            }

            return view('pendaftaran', ['periode' => null, 'pendaftar' => null]);
        }

        // Periksa kelayakan calon santri untuk mendaftar
        $kelayakan = $service->cekKelayakan($user, $periode);

        if (! $kelayakan['layak']) {
            if ($kelayakan['alasan'] === 'ditolak_periode_ini') {
                return redirect()->route('status-pendaftaran')
                    ->with('info', 'Pendaftaran Anda pada periode ini tidak diterima. Silakan menunggu periode berikutnya.');
            }

            return redirect()->route('status-pendaftaran')
                ->with('info', __('pendaftaran.sudah_mendaftar'));
        }

        $sisaKuota = $service->sisaKuota($periode);
        if ($sisaKuota === 0) {
            return view('pendaftaran', [
                'periode' => $periode,
                'pendaftar' => $kelayakan['pendaftar'] ?? null,
                'kuotaPenuh' => true,
                'sisaKuota' => 0,
            ]);
        }

        // Jika pembaruan (layak karena ditolak di periode lama), isi awal dari data lama
        $pendaftar = $kelayakan['pendaftar'] ?? null;

        $kuotaPenuh = false;

        return view('pendaftaran', compact('periode', 'pendaftar', 'kuotaPenuh', 'sisaKuota'));
    }

    /**
     * Proses pengiriman formulir pendaftaran santri baru.
     */
    public function prosesPendaftaran(PendaftaranRequest $request, PendaftaranService $service): RedirectResponse
    {
        $user = $request->user();

        try {
            $service->daftarkan($user, $request->validated());

            return redirect()->route('status-pendaftaran')
                ->with('sukses', 'Pendaftaran berhasil');
        } catch (RuntimeException $e) {
            // Jika penolakan terjadi karena periode ditutup
            if (str_contains($e->getMessage(), 'ditutup')) {
                return redirect()->route('pendaftaran')
                    ->with('galat', $e->getMessage());
            }

            // Jika penolakan terjadi karena pengguna sudah terdaftar sebelumnya
            return redirect()->route('status-pendaftaran')
                ->with('galat', $e->getMessage());
        }
    }

    /**
     * Tampilkan halaman pelacak status pendaftaran milik pengguna saat ini.
     */
    public function statusPendaftaran(Request $request, PendaftaranService $service, CatatanPenolakan $catatanPenolakan, TautanGoogle $tautanGoogle): View
    {
        $user = $request->user();

        // Dapatkan data pendaftaran milik pengguna sendiri secara aman tanpa ID di URL
        $pendaftar = $user->pendaftar()->with(['periode', 'tagihan.pembayaran'])->first();
        $periodeTersedia = $service->getPeriodeTersedia();
        $catatan = $pendaftar?->status_daftar === Pendaftar::STATUS_DITOLAK
            ? $catatanPenolakan->baca($pendaftar->id_pendaftar)
            : null;
        $googleTertaut = $tautanGoogle->tautanPengguna((int) $user->id_user) !== null;

        return view('status-pendaftaran', compact('pendaftar', 'periodeTersedia', 'catatan', 'googleTertaut'));
    }
}
