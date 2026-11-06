<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TolakPendaftarRequest;
use App\Models\Pendaftar;
use App\Models\Tagihan;
use App\Services\CatatanPenolakan;
use App\Services\TesSeleksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PendaftarController extends Controller
{
    /**
     * Tampilkan daftar seluruh calon santri yang mendaftar.
     * Mendukung pencarian berdasarkan nama, filter status, dan paginasi.
     * Menggunakan eager loading untuk mencegah masalah query N+1.
     */
    public function index(Request $request, TesSeleksiService $tesSeleksiService): View
    {
        $query = Pendaftar::with(['periode', 'user', 'tagihan']);
        $filterTes = $request->query('tes');
        $filterTesValid = is_string($filterTes) && in_array($filterTes, [
            'belum_memilih',
            'menunggu',
            TesSeleksiService::HASIL_LULUS,
            TesSeleksiService::HASIL_TIDAK_LULUS,
        ], true) ? $filterTes : '';

        // Cari berdasarkan nama lengkap
        if ($request->filled('cari')) {
            $cari = $request->query('cari');
            $query->where('nama_lengkap', 'like', "%{$cari}%");
        }

        // Filter berdasarkan status pendaftaran
        if ($request->filled('status') && in_array($request->query('status'), [
            Pendaftar::STATUS_DIPROSES,
            Pendaftar::STATUS_DITERIMA,
            Pendaftar::STATUS_DITOLAK,
        ])) {
            $query->where('status_daftar', $request->query('status'));
        }

        $pendaftarList = $query->orderByDesc('id_pendaftar')
            ->paginate(10)
            ->withQueryString();

        $pendaftarDenganTes = $pendaftarList->getCollection()->map(function (Pendaftar $pendaftar) use ($tesSeleksiService): Pendaftar {
            $dataTes = $tesSeleksiService->baca($pendaftar->id_pendaftar);
            $pendaftar->setAttribute('tes_seleksi', $dataTes);
            $pendaftar->setAttribute('status_tes_filter', $tesSeleksiService->status($dataTes));

            return $pendaftar;
        });

        if ($filterTesValid !== '') {
            $pendaftarDenganTes = $pendaftarDenganTes
                ->filter(fn (Pendaftar $pendaftar): bool => $pendaftar->status_tes_filter === $filterTesValid)
                ->values();
        }

        $pendaftarList->setCollection($pendaftarDenganTes);

        return view('admin.pendaftar.index', compact('pendaftarList', 'filterTesValid'));
    }

    /**
     * Tampilkan detail data calon santri yang mendaftar.
     */
    public function show(int $id, CatatanPenolakan $catatanPenolakan, TesSeleksiService $tesSeleksiService): View
    {
        $pendaftar = Pendaftar::with(['periode', 'user', 'tagihan'])->findOrFail($id);
        $catatan = $pendaftar->status_daftar === Pendaftar::STATUS_DITOLAK
            ? $catatanPenolakan->baca($pendaftar->id_pendaftar)
            : null;
        $tesSeleksi = $tesSeleksiService->baca($pendaftar->id_pendaftar);

        return view('admin.pendaftar.show', compact('pendaftar', 'catatan', 'tesSeleksi'));
    }

    public function simpanHasilTes(Request $request, int $id, TesSeleksiService $tesSeleksiService): RedirectResponse
    {
        $pendaftar = Pendaftar::findOrFail($id);

        if ($pendaftar->status_daftar !== Pendaftar::STATUS_DIPROSES) {
            return redirect()->route('admin.pendaftar.show', $id)
                ->with('galat', 'Hasil tes hanya dapat diubah saat pendaftar berstatus diproses.');
        }

        $data = $request->validate([
            'hasil' => ['required', 'in:belum,lulus,tidak_lulus'],
            'nilai' => ['nullable', 'integer', 'min:0', 'max:100'],
            'catatan_tes' => ['nullable', 'string', 'max:2000'],
        ], [
            'hasil.required' => 'Pilih hasil tes seleksi.',
            'hasil.in' => 'Hasil tes seleksi tidak valid.',
            'nilai.integer' => 'Nilai tes harus berupa bilangan bulat.',
            'nilai.min' => 'Nilai tes minimal 0.',
            'nilai.max' => 'Nilai tes maksimal 100.',
        ]);

        try {
            $tesSeleksiService->simpanHasil(
                $pendaftar->id_pendaftar,
                $data['hasil'],
                isset($data['nilai']) ? (int) $data['nilai'] : null,
                $data['catatan_tes'] ?? null,
                (int) $request->user()->id_user,
            );
        } catch (\RuntimeException $exception) {
            return redirect()->route('admin.pendaftar.show', $id)
                ->withInput()
                ->with('galat', $exception->getMessage());
        }

        return redirect()->route('admin.pendaftar.show', $id)
            ->with('sukses', 'Hasil tes seleksi berhasil disimpan.');
    }

    /**
     * Terima pendaftar dalam transaksi database dengan penguncian baris (lockForUpdate).
     * Mengubah status menjadi diterima dan membuat satu tagihan jenis pendaftaran.
     * Peran pengguna tetap calon_santri dan tabel santri tetap kosong.
     */
    public function terima($id, TesSeleksiService $tesSeleksiService): RedirectResponse
    {
        return DB::transaction(function () use ($id, $tesSeleksiService) {
            $pendaftar = Pendaftar::where('id_pendaftar', $id)
                ->lockForUpdate()
                ->firstOrFail();

            // Hanya pendaftar berstatus 'diproses' yang boleh diterima
            if ($pendaftar->status_daftar !== Pendaftar::STATUS_DIPROSES) {
                return redirect()->route('admin.pendaftar.index')
                    ->with('galat', "Pendaftar {$pendaftar->nama_lengkap} tidak dapat diterima karena statusnya sudah bukan diproses.");
            }

            if ($tesSeleksiService->baca($pendaftar->id_pendaftar)['hasil'] !== TesSeleksiService::HASIL_LULUS) {
                return redirect()->route('admin.pendaftar.show', $pendaftar->id_pendaftar)
                    ->with('galat', 'Pendaftar hanya dapat diterima setelah lulus Tes Seleksi.');
            }

            // Ubah status menjadi diterima
            $pendaftar->update([
                'status_daftar' => Pendaftar::STATUS_DITERIMA,
            ]);

            // Buat tagihan pendaftaran jika belum ada (cegah tagihan ganda bila tombol ditekan ulang)
            $sudahAdaTagihan = Tagihan::where('id_pendaftar', $pendaftar->id_pendaftar)
                ->where('jenis_tagihan', Tagihan::JENIS_PENDAFTARAN)
                ->lockForUpdate()
                ->exists();

            if (! $sudahAdaTagihan) {
                Tagihan::create([
                    'id_pendaftar' => $pendaftar->id_pendaftar,
                    'jenis_tagihan' => Tagihan::JENIS_PENDAFTARAN,
                    'nominal' => config('pondok.biaya_pendaftaran', 150000),
                    'status_tagihan' => Tagihan::STATUS_BELUM_DIBAYAR,
                ]);
            }

            // Catatan: users.peran TIDAK diubah dan data santri TIDAK dibuat di sini (dilakukan di webhook pembayaran pada Fase 6).

            return redirect()->route('admin.pendaftar.index')
                ->with('sukses', "Pendaftar {$pendaftar->nama_lengkap} berhasil diterima dan tagihan pendaftaran telah diterbitkan.");
        });
    }

    /**
     * Tolak pendaftar dalam transaksi database dengan penguncian baris (lockForUpdate).
     * Mengubah status menjadi ditolak tanpa membuat tagihan.
     */
    public function tolak(TolakPendaftarRequest $request, int $id, CatatanPenolakan $catatanPenolakan): RedirectResponse
    {
        try {
            return DB::transaction(function () use ($request, $id, $catatanPenolakan) {
                $pendaftar = Pendaftar::where('id_pendaftar', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Hanya pendaftar berstatus 'diproses' yang boleh ditolak
                if ($pendaftar->status_daftar !== Pendaftar::STATUS_DIPROSES) {
                    return redirect()->route('admin.pendaftar.index')
                        ->with('galat', "Pendaftar {$pendaftar->nama_lengkap} tidak dapat ditolak karena statusnya sudah bukan diproses.");
                }

                $pendaftar->update([
                    'status_daftar' => Pendaftar::STATUS_DITOLAK,
                ]);
                $catatanPenolakan->simpan($pendaftar->id_pendaftar, $request->validated('catatan'));

                return redirect()->route('admin.pendaftar.index')
                    ->with('sukses', "Pendaftar {$pendaftar->nama_lengkap} telah ditolak.");
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('admin.pendaftar.show', $id)
                ->withInput()
                ->with('galat', 'Catatan penolakan gagal disimpan. Status pendaftar tidak diubah.');
        }
    }
}
