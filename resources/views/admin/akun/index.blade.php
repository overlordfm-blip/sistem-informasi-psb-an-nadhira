@extends('layouts.admin')

@section('title', 'Akun Pengguna')

@section('content')
<section class="space-y-5">
    <header>
        <h2 class="font-serif text-3xl font-bold text-primer">Akun Pengguna</h2>
        <p class="mt-2 text-base">Cari akun calon santri dan santri untuk membantu pemulihan akses.</p>
    </header>

    <x-notifikasi />

    <form method="GET" action="{{ route('admin.akun.index') }}" class="flex flex-col gap-4 rounded-sm border border-garis bg-permukaan p-4 sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
            <label for="cari" class="mb-1 block text-sm font-semibold">Nama user, nama lengkap, nama wali, atau nomor telepon</label>
            <input id="cari" name="cari" value="{{ request('cari') }}" type="search" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base" autocomplete="off">
        </div>
        <div>
            <label for="peran" class="mb-1 block text-sm font-semibold">Peran</label>
            <select id="peran" name="peran" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                <option value="">Semua peran</option>
                <option value="calon_santri" @selected(request('peran') === 'calon_santri')>Calon santri</option>
                <option value="santri" @selected(request('peran') === 'santri')>Santri</option>
            </select>
        </div>
        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white hover:bg-sekunder">Cari</button>
        @if (request()->hasAny(['cari', 'peran']))
            <a href="{{ route('admin.akun.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-sm border border-garis px-5 py-2 font-semibold text-primer">Reset</a>
        @endif
    </form>

    @if ($akun->isEmpty())
        <p class="border-y border-garis py-8 text-center">Tidak ada akun yang cocok.</p>
    @else
        <div class="overflow-x-auto border-y border-garis">
            <table class="w-full min-w-[64rem] text-left text-sm">
                <thead class="bg-permukaan text-teks">
                    <tr>
                        <th scope="col" class="px-3 py-3">Nama user</th>
                        <th scope="col" class="px-3 py-3">Peran</th>
                        <th scope="col" class="px-3 py-3">Nama lengkap</th>
                        <th scope="col" class="px-3 py-3">Nama wali</th>
                        <th scope="col" class="px-3 py-3">No. telepon</th>
                        <th scope="col" class="px-3 py-3">Gmail tertaut</th>
                        <th scope="col" class="px-3 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-garis bg-white">
                    @foreach ($akun as $item)
                        <tr>
                            <td class="px-3 py-3 font-semibold">{{ $item->nama_user }}</td>
                            <td class="px-3 py-3">{{ $item->peran === 'santri' ? 'Santri' : 'Calon santri' }}</td>
                            <td class="px-3 py-3">{{ $item->pendaftar?->nama_lengkap ?? '-' }}</td>
                            <td class="px-3 py-3">{{ $item->pendaftar?->nama_wali ?? '-' }}</td>
                            <td class="px-3 py-3">{{ $item->pendaftar?->no_telepon ?? '-' }}</td>
                            <td class="px-3 py-3">
                                @if ($googleTautan[$item->id_user])
                                    Ya, {{ $googleTautan[$item->id_user]['email_samar'] }}
                                @else
                                    Tidak
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <div class="space-y-4">
                                    @if ($googleTautan[$item->id_user])
                                        <form action="{{ route('admin.akun.google.destroy', $item->id_user) }}" method="POST" class="space-y-2" onsubmit="return confirm('Lepas tautan Gmail untuk akun ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <label class="flex items-start gap-2 text-xs">
                                                <input type="checkbox" name="identitas_terverifikasi" value="1" required class="mt-0.5 size-4 accent-primer">
                                                <span>Saya telah memverifikasi identitas pengguna</span>
                                            </label>
                                            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm border border-bahaya px-4 py-2 font-semibold text-bahaya underline underline-offset-4">Lepas tautan Gmail</button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.akun.pemulihan', $item->id_user) }}" method="POST" class="space-y-2">
                                    @csrf
                                    <label class="flex items-start gap-2 text-xs">
                                        <input type="checkbox" name="identitas_terverifikasi" value="1" required class="mt-0.5 size-4 accent-primer">
                                        <span>Identitas pengguna sudah saya verifikasi</span>
                                    </label>
                                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-4 py-2 font-semibold text-white underline underline-offset-4 hover:bg-sekunder">Buka akses pemulihan</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <nav aria-label="Navigasi halaman akun" class="pt-2">{{ $akun->links() }}</nav>
    @endif
</section>
@endsection