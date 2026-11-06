<?php

namespace App\Http\Controllers;

use App\Models\Pendaftar;
use App\Models\User;
use App\Services\TesSeleksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class TesController extends Controller
{
    public function index(Request $request, TesSeleksiService $tesSeleksiService): View
    {
        $pendaftar = $this->pendaftarDiproses($request);

        return view('tes.index', [
            'pengaturan' => $tesSeleksiService->pengaturan(),
            'tes' => $tesSeleksiService->baca($pendaftar->id_pendaftar),
        ]);
    }

    public function pilih(Request $request, TesSeleksiService $tesSeleksiService): RedirectResponse
    {
        $pendaftar = $this->pendaftarDiproses($request);
        $data = $request->validate([
            'mode' => ['required', 'in:online,offline'],
        ], [
            'mode.required' => 'Pilih mode tes seleksi.',
            'mode.in' => 'Mode tes seleksi tidak valid.',
        ]);

        try {
            $tesSeleksiService->pilihMode($pendaftar->id_pendaftar, $data['mode']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['mode' => $exception->getMessage()]);
        }

        return redirect()->route('tes')->with('sukses', 'Mode tes seleksi berhasil disimpan.');
    }

    private function pendaftarDiproses(Request $request): Pendaftar
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->peran === User::PERAN_CALON_SANTRI, 403);

        $pendaftar = $user->pendaftar;
        abort_unless($pendaftar instanceof Pendaftar, 404);
        abort_unless($pendaftar->status_daftar === Pendaftar::STATUS_DIPROSES, 403);

        return $pendaftar;
    }
}
