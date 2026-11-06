<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PemulihanAkun;
use App\Support\NomorTelepon;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PemulihanController extends Controller
{
    public function index(Request $request, PemulihanAkun $pemulihanAkun): ViewContract
    {
        $daftar = $pemulihanAkun->daftarMenunggu();
        $kodeCari = $request->query('kode');
        $kodeCari = is_string($kodeCari) ? strtoupper(trim($kodeCari)) : '';

        if ($kodeCari !== '') {
            $daftar = array_values(array_filter(
                $daftar,
                fn (array $permintaan): bool => str_contains($permintaan['kode'], $kodeCari)
            ));
        }

        $namaUser = array_values(array_unique(array_filter(array_column($daftar, 'nama_user_input'))));
        $namaUserCocok = $namaUser === []
            ? []
            : User::query()
                ->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI])
                ->whereIn('nama_user', $namaUser)
                ->pluck('nama_user')
                ->all();

        foreach ($daftar as &$permintaan) {
            $permintaan['nama_user_cocok'] = $permintaan['nama_user_input'] !== null
                && in_array($permintaan['nama_user_input'], $namaUserCocok, true);
        }
        unset($permintaan);

        usort($daftar, fn (array $kiri, array $kanan): int => strcmp($kanan['dibuat_pada'], $kiri['dibuat_pada']));

        $page = LengthAwarePaginator::resolveCurrentPage();
        $jumlahPerHalaman = 15;
        $permintaanList = new LengthAwarePaginator(
            array_slice($daftar, ($page - 1) * $jumlahPerHalaman, $jumlahPerHalaman),
            count($daftar),
            $jumlahPerHalaman,
            $page,
            ['path' => route('admin.pemulihan.index'), 'query' => $request->query()]
        );

        return view('admin.pemulihan.index', compact('permintaanList'));
    }

    public function show(Request $request, string $kode, PemulihanAkun $pemulihanAkun): ViewContract
    {
        $permintaan = $pemulihanAkun->ambil($kode);
        abort_unless($permintaan !== null && $permintaan['status'] === PemulihanAkun::STATUS_MENUNGGU, 404);

        $cariAkun = $request->query('cari_akun');
        $cariAkun = is_string($cariAkun) ? trim($cariAkun) : '';
        $akunQuery = User::query()
            ->with('pendaftar')
            ->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI]);

        if ($cariAkun !== '') {
            $pola = '%'.$cariAkun.'%';
            $akunQuery->where(function ($query) use ($pola): void {
                $query->where('nama_user', 'like', $pola)
                    ->orWhereHas('pendaftar', function ($pendaftarQuery) use ($pola): void {
                        $pendaftarQuery->where('nama_lengkap', 'like', $pola)
                            ->orWhere('nama_wali', 'like', $pola);
                    });
            });
        }

        $akunList = $akunQuery->orderBy('nama_user')->paginate(10)->withQueryString();
        $idUserPilihan = $request->query('id_user');
        $akunTerpilih = is_numeric($idUserPilihan)
            ? User::query()->with('pendaftar')->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI])->find((int) $idUserPilihan)
            : null;

        if ($akunTerpilih === null && $cariAkun === '' && $permintaan['nama_user_input']) {
            $akunTerpilih = User::query()
                ->with('pendaftar')
                ->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI])
                ->where('nama_user', $permintaan['nama_user_input'])
                ->first();
        }

        return view('admin.pemulihan.show', [
            'permintaan' => $permintaan,
            'akunList' => $akunList,
            'akunTerpilih' => $akunTerpilih,
            'nomorTelepon' => NomorTelepon::normalisasi($akunTerpilih?->pendaftar?->no_telepon),
        ]);
    }

    public function buka(Request $request, string $kode, PemulihanAkun $pemulihanAkun): RedirectResponse
    {
        $data = $request->validate([
            'id_user' => ['required', 'integer', 'exists:users,id_user'],
            'identitas_terverifikasi' => ['accepted'],
            'nomor_whatsapp' => ['nullable', 'string', 'max:30'],
        ], [
            'identitas_terverifikasi.accepted' => 'Anda wajib mengonfirmasi bahwa identitas pengguna sudah diverifikasi.',
            'identitas_terverifikasi.required' => 'Anda wajib mengonfirmasi bahwa identitas pengguna sudah diverifikasi.',
        ]);
        $akun = User::query()
            ->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI])
            ->findOrFail((int) $data['id_user']);
        $permintaan = $pemulihanAkun->buka($kode, $akun->id_user, $request->user()->id_user);

        if ($permintaan === null) {
            return back()->withErrors(['kode' => 'Permintaan tidak tersedia atau sudah kedaluwarsa.']);
        }

        Log::info('Akses pemulihan akun dibuka', [
            'admin_id' => $request->user()->id_user,
            'akun_id' => $akun->id_user,
            'waktu' => now()->toIso8601String(),
        ]);

        return redirect()->route('admin.pemulihan.index')->with(
            'sukses',
            'Akses pemulihan dibuka selama 30 menit.'
        );
    }

    public function tolak(string $kode, PemulihanAkun $pemulihanAkun): RedirectResponse
    {
        if (! $pemulihanAkun->hapus($kode)) {
            return back()->withErrors(['kode' => 'Permintaan tidak tersedia.']);
        }

        return redirect()->route('admin.pemulihan.index')->with('sukses', 'Permintaan pemulihan dihapus.');
    }
}
