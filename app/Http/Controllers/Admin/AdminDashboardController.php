<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Pendaftar;
use App\Models\PeriodePendaftaran;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Tampilkan dashboard admin.
     * Berisi kartu statistik pendaftar dan dua daftar terbaru (pendaftar & pembayaran).
     * Eager loading dipakai di semua relasi untuk mencegah N+1.
     */
    public function index(): View
    {
        // ── Kartu statistik ──────────────────────────────────────────────────
        $totalPendaftar = Pendaftar::count();
        $diproses = Pendaftar::where('status_daftar', Pendaftar::STATUS_DIPROSES)->count();
        $diterima = Pendaftar::where('status_daftar', Pendaftar::STATUS_DITERIMA)->count();
        $ditolak = Pendaftar::where('status_daftar', Pendaftar::STATUS_DITOLAK)->count();

        // ── Lima pendaftar terbaru (urut id_pendaftar menurun) ────────────────
        $pendaftarTerbaru = Pendaftar::orderByDesc('id_pendaftar')
            ->limit(5)
            ->get(['id_pendaftar', 'nama_lengkap', 'status_daftar']);

        // ── Lima pembayaran terbaru (urut id_pembayaran menurun) ─────────────
        // Eager load: tagihan → pendaftar (untuk nama)
        $pembayaranTerbaru = Pembayaran::with(['tagihan.pendaftar'])
            ->orderByDesc('id_pembayaran')
            ->limit(5)
            ->get();
        $periodeTersedia = PeriodePendaftaran::query()
            ->tersedia()
            ->orderByDesc('tanggal_buka')
            ->orderByDesc('id_periode')
            ->first();
        $periodeTerjadwal = PeriodePendaftaran::query()
            ->where('status_periode', PeriodePendaftaran::STATUS_DIBUKA)
            ->whereDate('tanggal_buka', '>', today('Asia/Jakarta')->toDateString())
            ->orderBy('tanggal_buka')
            ->orderBy('id_periode')
            ->first();

        return view('admin.dashboard', compact(
            'totalPendaftar',
            'diproses',
            'diterima',
            'ditolak',
            'pendaftarTerbaru',
            'pembayaranTerbaru',
            'periodeTersedia',
            'periodeTerjadwal',
        ));
    }
}
