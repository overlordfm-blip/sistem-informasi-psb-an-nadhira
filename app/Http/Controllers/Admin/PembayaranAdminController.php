<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PembayaranAdminController extends Controller
{
    /**
     * Tampilkan daftar data pembayaran — hanya baca, tanpa tombol ubah.
     * Mendukung pencarian nama, filter status_bayar, filter jenis_tagihan, dan paginasi.
     * Eager loading mencegah N+1: tagihan → pendaftar + transaksi.
     */
    public function index(Request $request): View
    {
        $query = Pembayaran::with([
            'tagihan.pendaftar',
            'transaksi',
        ]);

        // Pencarian nama pendaftar (via relasi tagihan.pendaftar)
        if ($request->filled('cari')) {
            $cari = $request->query('cari');
            $query->whereHas('tagihan.pendaftar', function ($q) use ($cari) {
                $q->where('nama_lengkap', 'like', "%{$cari}%");
            });
        }

        // Filter status_bayar
        $statusValid = [
            Pembayaran::STATUS_MENUNGGU,
            Pembayaran::STATUS_BERHASIL,
            Pembayaran::STATUS_GAGAL,
            Pembayaran::STATUS_KEDALUWARSA,
        ];
        if ($request->filled('status') && in_array($request->query('status'), $statusValid, true)) {
            $query->where('status_bayar', $request->query('status'));
        }

        // Filter jenis_tagihan
        $jenisValid = [Tagihan::JENIS_PENDAFTARAN, Tagihan::JENIS_SYAHRIYAH];
        if ($request->filled('jenis') && in_array($request->query('jenis'), $jenisValid, true)) {
            $query->whereHas('tagihan', function ($q) use ($request) {
                $q->where('jenis_tagihan', $request->query('jenis'));
            });
        }

        $pembayaranList = $query->orderByDesc('id_pembayaran')
            ->paginate(15)
            ->withQueryString();

        return view('admin.pembayaran.index', compact('pembayaranList'));
    }
}
