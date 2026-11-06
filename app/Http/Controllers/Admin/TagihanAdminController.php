<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagihanSyahriyahRequest;
use App\Models\Pembayaran;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Services\SyahriyahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TagihanAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tagihan::with(['pendaftar', 'pembayaran']);

        if ($request->filled('cari')) {
            $cari = $request->query('cari');
            $query->whereHas('pendaftar', fn ($q) => $q->where('nama_lengkap', 'like', "%{$cari}%"));
        }

        if (in_array($request->query('jenis'), [Tagihan::JENIS_PENDAFTARAN, Tagihan::JENIS_SYAHRIYAH], true)) {
            $query->where('jenis_tagihan', $request->query('jenis'));
        }

        if (in_array($request->query('status'), [Tagihan::STATUS_BELUM_DIBAYAR, Tagihan::STATUS_LUNAS], true)) {
            $query->where('status_tagihan', $request->query('status'));
        }

        $tagihanList = $query->orderByDesc('id_tagihan')->paginate(15)->withQueryString();

        return view('admin.tagihan.index', compact('tagihanList'));
    }

    public function createSyahriyah(): View
    {
        $santriAktif = Santri::with('pendaftar')
            ->where('status_santri', Santri::STATUS_AKTIF)
            ->orderBy('id_santri')
            ->get();

        return view('admin.tagihan.syahriyah', compact('santriAktif'));
    }

    public function storeSyahriyah(TagihanSyahriyahRequest $request, SyahriyahService $service): RedirectResponse
    {
        $hasil = $service->buat($request->validated());

        return redirect()->route('admin.tagihan.syahriyah')->with('hasilBatch', $hasil);
    }

    public function destroy(int $id): RedirectResponse
    {
        return DB::transaction(function () use ($id): RedirectResponse {
            $tagihan = Tagihan::where('id_tagihan', $id)->lockForUpdate()->firstOrFail();

            if ($tagihan->status_tagihan !== Tagihan::STATUS_BELUM_DIBAYAR) {
                return redirect()->route('admin.tagihan.index')
                    ->with('galat', 'Tagihan hanya dapat dihapus jika statusnya belum dibayar.');
            }

            if (Pembayaran::where('id_tagihan', $tagihan->id_tagihan)->exists()) {
                return redirect()->route('admin.tagihan.index')
                    ->with('galat', 'Tagihan yang sudah memiliki data pembayaran tidak dapat dihapus.');
            }

            $tagihan->delete();

            return redirect()->route('admin.tagihan.index')->with('sukses', 'Tagihan berhasil dihapus.');
        });
    }
}
