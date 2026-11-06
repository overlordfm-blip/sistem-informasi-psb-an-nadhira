<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformasiPondokRequest;
use App\Models\InformasiPondok;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InformasiPondokController extends Controller
{
    /**
     * Tampilkan daftar seluruh informasi pondok.
     */
    public function index(): View
    {
        $informasiList = InformasiPondok::with('user')
            ->orderByDesc('diperbarui')
            ->orderByDesc('id_informasi')
            ->get();

        return view('admin.informasi.index', compact('informasiList'));
    }

    /**
     * Tampilkan formulir penambahan informasi pondok baru.
     */
    public function create(): View
    {
        $informasi = new InformasiPondok;

        return view('admin.informasi.create', compact('informasi'));
    }

    /**
     * Simpan informasi pondok baru ke basis data.
     */
    public function store(InformasiPondokRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['id_user'] = auth()->id();
        $data['diperbarui'] = now()->toDateString();

        InformasiPondok::create($data);

        return redirect()->route('admin.informasi.index')
            ->with('sukses', 'Informasi pondok berhasil ditambahkan.');
    }

    /**
     * Tampilkan formulir pengubahan informasi pondok.
     */
    public function edit(InformasiPondok $informasi): View
    {
        return view('admin.informasi.edit', compact('informasi'));
    }

    /**
     * Perbarui data informasi pondok di basis data.
     */
    public function update(InformasiPondokRequest $request, InformasiPondok $informasi): RedirectResponse
    {
        $data = $request->validated();
        $data['id_user'] = auth()->id();
        $data['diperbarui'] = now()->toDateString();

        $informasi->update($data);

        return redirect()->route('admin.informasi.index')
            ->with('sukses', 'Informasi pondok berhasil diperbarui.');
    }

    /**
     * Hapus data informasi pondok dari basis data.
     */
    public function destroy(InformasiPondok $informasi): RedirectResponse
    {
        $informasi->delete();

        return redirect()->route('admin.informasi.index')
            ->with('sukses', 'Informasi pondok berhasil dihapus.');
    }
}
