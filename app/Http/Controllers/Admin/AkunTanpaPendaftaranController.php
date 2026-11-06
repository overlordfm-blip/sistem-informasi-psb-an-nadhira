<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AkunTanpaPendaftaranController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::where('peran', User::PERAN_CALON_SANTRI)
            ->whereDoesntHave('pendaftar');

        if ($request->filled('cari')) {
            $query->where('nama_user', 'like', '%'.$request->query('cari').'%');
        }

        $akunList = $query->orderBy('nama_user')->paginate(15)->withQueryString();

        return view('admin.akun-tanpa-pendaftaran.index', compact('akunList'));
    }

    public function destroy(int $id): RedirectResponse
    {
        return DB::transaction(function () use ($id): RedirectResponse {
            $user = User::where('id_user', $id)->lockForUpdate()->firstOrFail();

            if ($user->peran !== User::PERAN_CALON_SANTRI) {
                return redirect()->route('admin.akun-tanpa-pendaftaran.index')
                    ->with('galat', 'Hanya akun calon santri yang dapat dihapus.');
            }

            if ($user->pendaftar()->exists()) {
                return redirect()->route('admin.akun-tanpa-pendaftaran.index')
                    ->with('galat', 'Akun yang sudah memiliki pendaftar tidak dapat dihapus.');
            }

            $user->delete();

            return redirect()->route('admin.akun-tanpa-pendaftaran.index')
                ->with('sukses', 'Akun calon santri berhasil dihapus.');
        });
    }
}
