<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PemulihanAkun;
use App\Support\NomorTelepon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\JsonResponse;

class LupaAkunController extends Controller
{
    public function index(): View
    {
        return view('auth.lupa-akun', $this->dataKontak());
    }

    public function permintaan(Request $request, PemulihanAkun $pemulihanAkun): RedirectResponse
    {
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nama_wali' => ['required', 'string', 'max:100'],
            'nama_user_input' => ['nullable', 'string', 'max:50'],
        ], [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'nama_wali.required' => 'Nama wali wajib diisi.',
        ]);

        $permintaan = $pemulihanAkun->buatPermintaan($data);
        $request->session()->put('pemulihan.kode', $permintaan['kode']);

        return redirect()->route('lupa-akun.status')->with(
            'sukses',
            'Permintaan pemulihan diterima. Admin akan memverifikasi identitas Anda.'
        );
    }

    public function status(Request $request, PemulihanAkun $pemulihanAkun): View|JsonResponse
    {
        $kode = $request->session()->get('pemulihan.kode');
        $permintaan = $pemulihanAkun->ambil(is_string($kode) ? $kode : null);
        $status = $permintaan['status'] ?? PemulihanAkun::STATUS_KEDALUWARSA;

        if ($request->expectsJson()) {
            return response()->json(['status' => $status]);
        }

        return view('auth.lupa-akun-status', array_merge($this->dataKontak(), [
            'permintaan' => $permintaan,
            'status' => $status,
        ]));
    }

    public function formLanjut(): View
    {
        return view('auth.lupa-akun-lanjut');
    }

    public function lanjut(Request $request, PemulihanAkun $pemulihanAkun): RedirectResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'regex:/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/'],
        ], [
            'kode.required' => 'Kode pemulihan wajib diisi.',
            'kode.regex' => 'Kode pemulihan tidak valid.',
        ]);
        $permintaan = $pemulihanAkun->ambil($data['kode']);

        if ($permintaan === null || in_array($permintaan['status'], [PemulihanAkun::STATUS_SELESAI, PemulihanAkun::STATUS_KEDALUWARSA], true)) {
            return back()->withErrors(['kode' => 'Kode tidak valid atau sudah kedaluwarsa.']);
        }

        $request->session()->put('pemulihan.kode', $permintaan['kode']);

        return redirect()->route('lupa-akun.status');
    }

    public function ubahPassword(Request $request, PemulihanAkun $pemulihanAkun): RedirectResponse
    {
        $kode = $request->session()->get('pemulihan.kode');

        if (! is_string($kode)) {
            return redirect()->route('lupa-akun.lanjut')
                ->withErrors(['kode' => 'Masukkan kode pemulihan untuk melanjutkan.']);
        }

        $validator = Validator::make($request->all(), [
            'password_baru' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password_baru.required' => 'Password baru wajib diisi.',
            'password_baru.min' => 'Password baru minimal 8 karakter.',
            'password_baru.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except(['password_baru', 'password_baru_confirmation']));
        }

        $passwordBaru = $validator->validated()['password_baru'];
        $berhasil = $pemulihanAkun->selesaikan($kode, function (array $permintaan) use ($passwordBaru): bool {
            $akun = User::query()
                ->whereIn('peran', [User::PERAN_CALON_SANTRI, User::PERAN_SANTRI])
                ->find($permintaan['id_user']);

            if ($akun === null) {
                return false;
            }

            $akun->password = Hash::make($passwordBaru);
            $akun->save();

            return true;
        });

        if (! $berhasil) {
            return redirect()->route('lupa-akun.status')
                ->withErrors(['kode' => 'Akses pemulihan tidak tersedia atau sudah kedaluwarsa.']);
        }

        $request->session()->forget('pemulihan.kode');

        return redirect()->route('login')->with(
            'sukses',
            'Password berhasil diubah. Silakan masuk menggunakan password baru.'
        );
    }

    private function dataKontak(): array
    {
        return [
            'nomorWhatsApp' => NomorTelepon::normalisasi(config('pondok.whatsapp')),
            'alamatPondok' => config('pondok.alamat'),
            'jamLayanan' => config('pondok.jam_layanan'),
        ];
    }
}
