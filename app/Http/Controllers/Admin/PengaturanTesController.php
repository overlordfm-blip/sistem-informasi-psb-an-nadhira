<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Services\TesSeleksiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

class PengaturanTesController extends Controller
{
    public function index(TesSeleksiService $tesSeleksiService): View
    {
        $calonUntukJadwal = Pendaftar::query()
            ->with('user')
            ->where('status_daftar', Pendaftar::STATUS_DIPROSES)
            ->orderBy('nama_lengkap')
            ->get()
            ->filter(function (Pendaftar $pendaftar) use ($tesSeleksiService): bool {
                $tes = $tesSeleksiService->baca((int) $pendaftar->id_pendaftar);

                return $tes['mode'] === TesSeleksiService::MODE_ONLINE
                    && $tes['hasil'] === TesSeleksiService::HASIL_BELUM
                    && ! is_array($tes['jadwal_online'] ?? null);
            })
            ->values();

        return view('admin.pengaturan-tes.index', [
            'pengaturan' => $tesSeleksiService->pengaturan(),
            'calonUntukJadwal' => $calonUntukJadwal,
        ]);
    }

    public function jadwalkanOnline(Request $request, TesSeleksiService $tesSeleksiService): RedirectResponse
    {
        $data = $request->validate([
            'id_pendaftar' => ['required', 'integer', 'exists:pendaftar,id_pendaftar'],
            'tanggal' => ['required', 'date', 'after_or_equal:'.today('Asia/Jakarta')->toDateString()],
            'jam' => ['required', 'date_format:H:i'],
        ], [
            'id_pendaftar.required' => 'Calon santri wajib dipilih.',
            'tanggal.required' => 'Tanggal tes online wajib diisi.',
            'tanggal.after_or_equal' => 'Tanggal tes online tidak boleh sudah lewat.',
            'jam.required' => 'Jam tes online wajib diisi.',
            'jam.date_format' => 'Format jam tes online tidak valid.',
        ]);

        $pendaftar = Pendaftar::query()
            ->where('status_daftar', Pendaftar::STATUS_DIPROSES)
            ->findOrFail($data['id_pendaftar']);
        $waktu = Carbon::createFromFormat('!Y-m-d H:i', $data['tanggal'].' '.$data['jam'], 'Asia/Jakarta');
        if ($waktu === false || $waktu->lessThanOrEqualTo(now('Asia/Jakarta'))) {
            return back()->withInput()->withErrors([
                'jadwal_online' => 'Jadwal tes harus ditetapkan pada waktu mendatang.',
            ]);
        }

        try {
            $tesSeleksiService->jadwalkanOnline(
                (int) $pendaftar->id_pendaftar,
                $data['tanggal'],
                $data['jam'],
                (int) $request->user()->id_user
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['jadwal_online' => $exception->getMessage()]);
        }

        return redirect()->route('admin.pengaturan-tes')->with('sukses', 'Jadwal tes online berhasil diberikan.');
    }

    public function simpan(Request $request, TesSeleksiService $tesSeleksiService): RedirectResponse
    {
        $aturanTanggalOffline = ['nullable', 'date'];
        if ($request->boolean('offline_aktif')) {
            $aturanTanggalOffline = [
                'required',
                'date',
                'after_or_equal:'.today('Asia/Jakarta')->toDateString(),
            ];
        }

        $data = $request->validate([
            'online_aktif' => ['nullable', 'boolean'],
            'online_tautan' => ['nullable', 'required_if:online_aktif,1', 'url', 'starts_with:https://', 'max:2048'],
            'online_petunjuk' => ['nullable', 'string', 'max:2000'],
            'offline_aktif' => ['nullable', 'boolean'],
            'offline_tanggal' => $aturanTanggalOffline,
            'offline_jam' => [$request->boolean('offline_aktif') ? 'required' : 'nullable', 'date_format:H:i'],
            'offline_lokasi' => [$request->boolean('offline_aktif') ? 'required' : 'nullable', 'string', 'max:255'],
            'offline_petunjuk' => ['nullable', 'string', 'max:2000'],
        ], [
            'online_tautan.required_if' => 'Tautan https tes online wajib diisi saat mode online aktif.',
            'online_tautan.starts_with' => 'Tautan tes online harus menggunakan https.',
            'offline_tanggal.required' => 'Tanggal tes offline wajib diisi saat mode offline aktif.',
            'offline_tanggal.after_or_equal' => 'Tanggal tes offline tidak boleh sudah lewat.',
            'offline_jam.required' => 'Jam tes offline wajib diisi saat mode offline aktif.',
            'offline_lokasi.required' => 'Lokasi tes offline wajib diisi saat mode offline aktif.',
        ]);

        $tesSeleksiService->simpanPengaturan([
            'online' => [
                'aktif' => $request->boolean('online_aktif'),
                'tautan' => $data['online_tautan'] ?? '',
                'petunjuk' => $data['online_petunjuk'] ?? '',
            ],
            'offline' => [
                'aktif' => $request->boolean('offline_aktif'),
                'tanggal' => $data['offline_tanggal'] ?? '',
                'jam' => $data['offline_jam'] ?? '',
                'lokasi' => $data['offline_lokasi'] ?? '',
                'petunjuk' => $data['offline_petunjuk'] ?? '',
            ],
        ]);

        return redirect()->route('admin.pengaturan-tes')->with('sukses', 'Pengaturan tes seleksi berhasil disimpan.');
    }
}
