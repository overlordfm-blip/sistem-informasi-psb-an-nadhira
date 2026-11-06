<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Services\PembayaranService;
use App\Services\TautanGoogle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class TagihanController extends Controller
{
    /**
     * Tampilkan semua tagihan milik pengguna yang sedang login.
     * Dapat diakses oleh calon_santri (punya pendaftar) dan santri.
     */
    public function index(Request $request, PembayaranService $service, TautanGoogle $tautanGoogle): View
    {
        $user = $request->user();

        // Ambil tagihan lewat relasi: users → pendaftar → tagihan → pembayaran → transaksi
        $pendaftar = $user->pendaftar()->with(['tagihan.pembayaran.transaksi'])->first();
        $tagihan = ($pendaftar?->tagihan ?? collect())->sortByDesc('id_tagihan')->values();

        // Sinkronkan status transaksi yang masih menunggu ke API Midtrans
        // (memastikan status lunas dan santri tetap terbuat meski webhook Midtrans belum sampai ke server lokal)
        if ($pendaftar && $tagihan->isNotEmpty()) {
            $adaPerubahan = false;
            foreach ($tagihan as $item) {
                if ($item->status_tagihan === Tagihan::STATUS_BELUM_DIBAYAR && $item->pembayaran) {
                    foreach ($item->pembayaran->transaksi as $trx) {
                        if ($trx->status_transaksi === Transaksi::STATUS_MENUNGGU) {
                            $service->sinkronkanTransaksi($trx);
                            $adaPerubahan = true;
                        }
                    }
                }
            }

            if ($adaPerubahan) {
                $pendaftar->unsetRelation('tagihan');
                $pendaftar->load(['tagihan.pembayaran.transaksi']);
                $tagihan = $pendaftar->tagihan->sortByDesc('id_tagihan')->values();
            }
        }

        // Riwayat pembayaran hanya milik pengguna sendiri, urut id_pembayaran menurun, dengan eager loading
        $riwayatPembayaran = $pendaftar
            ? Pembayaran::whereHas('tagihan', function ($q) use ($pendaftar) {
                $q->where('id_pendaftar', $pendaftar->id_pendaftar);
            })
                ->with(['tagihan', 'transaksi'])
                ->orderByDesc('id_pembayaran')
                ->get()
            : collect();

        $googleTertaut = $tautanGoogle->tautanPengguna((int) $user->id_user) !== null;

        return view('tagihan.index', compact('tagihan', 'pendaftar', 'riwayatPembayaran', 'googleTertaut'));
    }

    /**
     * Inisiasi pembayaran: membuat/mengambil Snap token lalu mengembalikan JSON.
     * Hanya pemilik tagihan yang boleh, dan hanya jika status belum_dibayar.
     */
    public function bayar(Request $request, int $id, PembayaranService $service): JsonResponse
    {
        $user = $request->user();
        $pendaftar = $user->pendaftar;

        // Pastikan tagihan milik user ini
        $tagihan = Tagihan::where('id_tagihan', $id)
            ->where('id_pendaftar', $pendaftar?->id_pendaftar)
            ->first();

        if (! $tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan atau bukan milik Anda.'], 404);
        }

        if ($tagihan->status_tagihan !== Tagihan::STATUS_BELUM_DIBAYAR) {
            return response()->json(['message' => 'Tagihan ini sudah lunas.'], 422);
        }

        try {
            $paksaBaru = $request->boolean('baru');
            $token = $service->inisiasiBayar($tagihan, $paksaBaru);

            return response()->json(['snap_token' => $token]);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Cek status tagihan secara berkala (polling) untuk auto-refresh antarmuka.
     */
    public function status(Request $request, int $id, PembayaranService $service): JsonResponse
    {
        $user = $request->user();
        $pendaftar = $user->pendaftar;

        $tagihan = Tagihan::where('id_tagihan', $id)
            ->where('id_pendaftar', $pendaftar?->id_pendaftar)
            ->first();

        if (! $tagihan) {
            return response()->json(['message' => 'Tagihan tidak ditemukan atau bukan milik Anda.'], 404);
        }

        // Jika belum lunas, coba sinkronkan transaksi menunggu yang ada
        if ($tagihan->status_tagihan === Tagihan::STATUS_BELUM_DIBAYAR && $tagihan->pembayaran) {
            foreach ($tagihan->pembayaran->transaksi as $trx) {
                if ($trx->status_transaksi === Transaksi::STATUS_MENUNGGU) {
                    $service->sinkronkanTransaksi($trx);
                }
            }
            $tagihan->refresh();
        }

        $lunas = ($tagihan->status_tagihan === Tagihan::STATUS_LUNAS);

        return response()->json([
            'status_tagihan' => $tagihan->status_tagihan,
            'lunas' => $lunas,
            'peran' => $user->fresh()->peran,
        ]);
    }
}
