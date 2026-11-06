<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\User;
use App\Services\PemulihanAkun;
use App\Services\TautanGoogle;
use App\Support\NomorTelepon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AkunController extends Controller
{
    private const PERAN_AKUN = [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI];

    public function index(Request $request, TautanGoogle $tautanGoogle): View
    {
        $cari = $request->query('cari');
        $cari = is_string($cari) ? trim($cari) : '';
        $peran = $request->query('peran');

        $akunQuery = User::query()
            ->with('pendaftar')
            ->whereIn('peran', self::PERAN_AKUN);

        if (in_array($peran, self::PERAN_AKUN, true)) {
            $akunQuery->where('peran', $peran);
        }

        if ($cari !== '') {
            $nomorNormal = NomorTelepon::normalisasi($cari);
            $variasiNomor = $nomorNormal === null ? [] : array_values(array_unique([
                $nomorNormal,
                '+'.$nomorNormal,
                ...(str_starts_with($nomorNormal, '62') ? ['0'.substr($nomorNormal, 2)] : []),
            ]));

            $akunQuery->where(function ($query) use ($cari, $variasiNomor): void {
                $pola = '%'.$cari.'%';
                $query->where('nama_user', 'like', $pola)
                    ->orWhereHas('pendaftar', function ($pendaftarQuery) use ($pola, $variasiNomor): void {
                        $pendaftarQuery->where(function ($detailQuery) use ($pola, $variasiNomor): void {
                            $detailQuery->where('nama_lengkap', 'like', $pola)
                                ->orWhere('nama_wali', 'like', $pola);

                            if ($variasiNomor !== []) {
                                $kolomTelepon = (new Pendaftar)->qualifyColumn('no_telepon');
                                $nomorBersih = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$kolomTelepon}, ' ', ''), '-', ''), '(', ''), ')', ''), '.', '')";
                                $placeholders = implode(', ', array_fill(0, count($variasiNomor), '?'));
                                $detailQuery->orWhereRaw($nomorBersih." IN ({$placeholders})", $variasiNomor);
                            }
                        });
                    });
            });
        }

        $akun = $akunQuery->orderByDesc('id_user')->paginate(15)->withQueryString();
        $googleTautan = $akun->getCollection()->mapWithKeys(fn (User $item): array => [
            $item->id_user => $tautanGoogle->tautanPengguna((int) $item->id_user),
        ]);

        return view('admin.akun.index', compact('akun', 'googleTautan'));
    }

    public function bukaAksesOffline(Request $request, int $id, PemulihanAkun $pemulihanAkun): Response
    {
        $akun = $this->findAkunPengguna($id);
        $request->validate([
            'identitas_terverifikasi' => ['accepted'],
        ], [
            'identitas_terverifikasi.accepted' => 'Anda wajib mengonfirmasi bahwa identitas pengguna sudah diverifikasi.',
            'identitas_terverifikasi.required' => 'Anda wajib mengonfirmasi bahwa identitas pengguna sudah diverifikasi.',
        ]);
        $permintaan = $pemulihanAkun->buatAksesOffline([
            'nama_user_input' => $akun->nama_user,
            'nama_lengkap' => $akun->pendaftar?->nama_lengkap ?? $akun->nama_user,
            'nama_wali' => $akun->pendaftar?->nama_wali ?? '',
        ], $akun->id_user, $request->user()->id_user);

        Log::info('Akses pemulihan offline dibuka', [
            'admin_id' => $request->user()->id_user,
            'akun_id' => $akun->id_user,
            'waktu' => now()->toIso8601String(),
        ]);

        return response()->view('admin.pemulihan.offline-created', [
            'akun' => $akun,
            'permintaan' => $permintaan,
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function findAkunPengguna(int $id): User
    {
        return User::query()
            ->with('pendaftar')
            ->whereIn('peran', self::PERAN_AKUN)
            ->findOrFail($id);
    }
}
