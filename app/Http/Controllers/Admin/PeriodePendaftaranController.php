<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PeriodePendaftaranRequest;
use App\Models\PeriodePendaftaran;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PeriodePendaftaranController extends Controller
{
    /**
     * Tampilkan daftar seluruh periode pendaftaran.
     */
    public function index(): View
    {
        $periodeList = PeriodePendaftaran::withCount('pendaftar')->orderByDesc('id_periode')->get();
        $periodeAktif = PeriodePendaftaran::query()
            ->tersedia()
            ->withCount('pendaftar')
            ->orderByDesc('tanggal_buka')
            ->first();
        $periodeTerjadwal = PeriodePendaftaran::query()
            ->withCount('pendaftar')
            ->where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
            ->whereDate('tanggal_buka', '>', today('Asia/Jakarta')->toDateString())
            ->orderBy('tanggal_buka')
            ->first();
        $kuota = (int) config('pondok.kuota', 0);

        return view('admin.periode.index', compact('periodeList', 'periodeAktif', 'periodeTerjadwal', 'kuota'));
    }

    /**
     * Tampilkan formulir penambahan periode pendaftaran baru.
     */
    public function create(): View
    {
        $periode = new PeriodePendaftaran;

        return view('admin.periode.create', compact('periode'));
    }

    /**
     * Simpan periode pendaftaran baru ke basis data.
     */
    public function store(PeriodePendaftaranRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status_periode'] = $data['status_periode'] ?? PeriodePendaftaran::STATUS_DITUTUP;
        PeriodePendaftaran::create($data);

        return redirect()->route('admin.periode.index')
            ->with('sukses', 'Periode pendaftaran berhasil ditambahkan.');
    }

    /**
     * Tampilkan formulir pengubahan data periode pendaftaran.
     */
    public function edit(PeriodePendaftaran $periode): View
    {
        return view('admin.periode.edit', compact('periode'));
    }

    /**
     * Perbarui data periode pendaftaran di basis data.
     */
    public function update(PeriodePendaftaranRequest $request, PeriodePendaftaran $periode): RedirectResponse
    {
        $data = $request->validated();
        $periode->update($data);

        return redirect()->route('admin.periode.index')
            ->with('sukses', 'Periode pendaftaran '.$periode->nama_periode.' berhasil diperbarui.');
    }

    /**
     * Aktifkan periode sekarang atau jadwalkan pembukaannya pada tanggal mulai.
     */
    public function buka(PeriodePendaftaran $periode): RedirectResponse
    {
        if ($periode->tanggal_tutup->lt(today('Asia/Jakarta'))) {
            return redirect()->route('admin.periode.index')
                ->with('galat', 'Periode yang telah melewati tanggal tutup tidak dapat dibuka kembali.');
        }

        $periode->update(['status_periode' => PeriodePendaftaran::STATUS_DIBUKA]);

        return redirect()->route('admin.periode.index')
            ->with('sukses', 'Periode pendaftaran '.$periode->nama_periode.' berhasil '.($periode->tanggal_buka->gt(today('Asia/Jakarta')) ? 'dijadwalkan.' : 'dibuka.'));
    }

    /**
     * Tutup pendaftaran untuk periode yang dipilih.
     */
    public function tutup(PeriodePendaftaran $periode): RedirectResponse
    {
        $periode->update(['status_periode' => PeriodePendaftaran::STATUS_DITUTUP]);

        return redirect()->route('admin.periode.index')
            ->with('sukses', 'Periode pendaftaran '.$periode->nama_periode.' berhasil ditutup.');
    }

    /**
     * Beralih status antara buka dan tutup.
     */
    public function toggleStatus(PeriodePendaftaran $periode): RedirectResponse
    {
        if ($periode->status_periode === PeriodePendaftaran::STATUS_DIBUKA) {
            return $this->tutup($periode);
        }

        return $this->buka($periode);
    }

    /**
     * Hapus periode pendaftaran dari basis data sesuai aturan bisnis.
     * Hanya boleh jika: sudah terlewat (tanggal_tutup < hari ini di Asia/Jakarta),
     * berstatus ditutup, dan tidak memiliki pendaftar.
     */
    public function destroy(PeriodePendaftaran $periode): RedirectResponse
    {
        // 1. Tolak jika masih berstatus dibuka
        if ($periode->status_periode === PeriodePendaftaran::STATUS_DIBUKA) {
            return redirect()->route('admin.periode.index')
                ->with('galat', 'Periode pendaftaran tidak dapat dihapus karena masih berstatus dibuka. Tutup periode terlebih dahulu.');
        }

        // 2. Tolak jika belum terlewat (tanggal_tutup >= hari ini menurut Asia/Jakarta)
        $hariIni = today('Asia/Jakarta');
        if ($periode->tanggal_tutup && $periode->tanggal_tutup->gte($hariIni)) {
            return redirect()->route('admin.periode.index')
                ->with('galat', 'Periode pendaftaran tidak dapat dihapus karena belum terlewat (tanggal penutupan belum berakhir).');
        }

        // 3. Tolak jika sudah memiliki pendaftar
        if ($periode->pendaftar()->exists()) {
            return redirect()->route('admin.periode.index')
                ->with('galat', 'Periode pendaftaran tidak dapat dihapus karena sudah memiliki data pendaftar.');
        }

        // 4. Eksekusi hapus dengan penanganan pelanggaran kunci tamu
        try {
            $namaPeriode = $periode->nama_periode;
            $periode->delete();

            return redirect()->route('admin.periode.index')
                ->with('sukses', "Periode pendaftaran {$namaPeriode} berhasil dihapus.");
        } catch (QueryException $e) {
            return redirect()->route('admin.periode.index')
                ->with('galat', 'Periode pendaftaran tidak dapat dihapus karena masih terkait dengan data lain.');
        }
    }
}
