@extends('layouts.admin')

@section('title', 'Pengaturan Tes Seleksi')

@section('content')
<section class="mx-auto max-w-3xl space-y-6">
    <header>
        <h1 class="font-serif text-3xl font-bold text-primer">Pengaturan Tes Seleksi</h1>
        <p class="mt-2">Atur pilihan tes yang tersedia bagi calon santri.</p>
    </header>

    <section class="space-y-4 border-y border-garis py-6">
        <header>
            <h2 class="font-serif text-2xl font-bold text-primer">Jadwal Tes Online Calon Santri</h2>
            <p class="mt-2 text-base">Atur tanggal dan jam untuk calon santri berstatus diproses yang telah memilih mode Online.</p>
        </header>

        @error('jadwal_online')
            <p role="alert" class="border-l-4 border-bahaya bg-bahaya/5 px-4 py-3 text-bahaya">{{ $message }}</p>
        @enderror

        @if ($calonUntukJadwal->isEmpty())
            <p class="border-l-4 border-peringatan bg-peringatan-latar px-4 py-3 text-peringatan">Belum ada calon santri yang menunggu jadwal. Calon berstatus diproses perlu memilih Tes Online dari halaman Tes Seleksi; namanya akan muncul di sini.</p>
        @else
            <div class="overflow-x-auto border-y border-garis">
                <table class="w-full min-w-[54rem] text-left text-sm">
                    <thead class="bg-permukaan">
                        <tr>
                            <th scope="col" class="px-3 py-3">Nama calon santri</th>
                            <th scope="col" class="px-3 py-3">Nama user</th>
                            <th scope="col" class="px-3 py-3">Tanggal tes</th>
                            <th scope="col" class="px-3 py-3">Jam tes</th>
                            <th scope="col" class="px-3 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-garis bg-white">
                        @foreach ($calonUntukJadwal as $calon)
                            <tr>
                                <td class="px-3 py-3 font-semibold">{{ $calon->nama_lengkap }}</td>
                                <td class="px-3 py-3">{{ $calon->user?->nama_user ?? '-' }}</td>
                                <td class="px-3 py-3">
                                    <form id="jadwal-{{ $calon->id_pendaftar }}" action="{{ route('admin.pengaturan-tes.jadwal-online') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id_pendaftar" value="{{ $calon->id_pendaftar }}">
                                    </form>
                                    <label class="sr-only" for="tanggal-{{ $calon->id_pendaftar }}">Tanggal tes untuk {{ $calon->nama_lengkap }}</label>
                                    <input form="jadwal-{{ $calon->id_pendaftar }}" id="tanggal-{{ $calon->id_pendaftar }}" name="tanggal" type="date" min="{{ today('Asia/Jakarta')->toDateString() }}" value="{{ old('id_pendaftar') == $calon->id_pendaftar ? old('tanggal') : '' }}" required class="min-h-10 rounded-sm border border-garis bg-white px-2 py-1 text-sm">
                                </td>
                                <td class="px-3 py-3">
                                    <label class="sr-only" for="jam-{{ $calon->id_pendaftar }}">Jam tes untuk {{ $calon->nama_lengkap }}</label>
                                    <input form="jadwal-{{ $calon->id_pendaftar }}" id="jam-{{ $calon->id_pendaftar }}" name="jam" type="time" value="{{ old('id_pendaftar') == $calon->id_pendaftar ? old('jam') : '' }}" required class="min-h-10 rounded-sm border border-garis bg-white px-2 py-1 text-sm">
                                </td>
                                <td class="px-3 py-3">
                                    <button form="jadwal-{{ $calon->id_pendaftar }}" type="submit" class="inline-flex min-h-10 items-center justify-center rounded-sm bg-primer px-4 py-2 font-semibold text-white hover:bg-sekunder">Berikan Jadwal</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <form action="{{ route('admin.pengaturan-tes.simpan') }}" method="POST" class="space-y-6">
        @csrf
        <fieldset class="space-y-4 border-y border-garis py-5">
            <legend class="font-serif text-xl font-bold text-primer">Tes Online</legend>
            <label class="flex items-center gap-3 text-base font-semibold">
                <input type="checkbox" name="online_aktif" value="1" @checked(old('online_aktif', $pengaturan['online']['aktif'])) class="size-4 accent-primer">
                Aktif
            </label>
            <div>
                <label for="online_tautan" class="mb-1 block text-sm font-semibold">Tautan https</label>
                <input id="online_tautan" name="online_tautan" type="url" value="{{ old('online_tautan', $pengaturan['online']['tautan']) }}" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                @error('online_tautan')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="online_petunjuk" class="mb-1 block text-sm font-semibold">Petunjuk</label>
                <textarea id="online_petunjuk" name="online_petunjuk" rows="3" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">{{ old('online_petunjuk', $pengaturan['online']['petunjuk']) }}</textarea>
                @error('online_petunjuk')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
            </div>
        </fieldset>

        <fieldset class="space-y-4 border-b border-garis pb-5">
            <legend class="font-serif text-xl font-bold text-primer">Tes Offline</legend>
            <label class="flex items-center gap-3 text-base font-semibold">
                <input type="checkbox" name="offline_aktif" value="1" @checked(old('offline_aktif', $pengaturan['offline']['aktif'])) class="size-4 accent-primer">
                Aktif
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="offline_tanggal" class="mb-1 block text-sm font-semibold">Tanggal</label>
                    <input id="offline_tanggal" name="offline_tanggal" type="date" min="{{ today('Asia/Jakarta')->toDateString() }}" value="{{ old('offline_tanggal', $pengaturan['offline']['tanggal']) }}" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                    @error('offline_tanggal')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="offline_jam" class="mb-1 block text-sm font-semibold">Jam</label>
                    <input id="offline_jam" name="offline_jam" type="time" value="{{ old('offline_jam', $pengaturan['offline']['jam']) }}" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                    @error('offline_jam')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label for="offline_lokasi" class="mb-1 block text-sm font-semibold">Lokasi</label>
                <input id="offline_lokasi" name="offline_lokasi" type="text" value="{{ old('offline_lokasi', $pengaturan['offline']['lokasi']) }}" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                @error('offline_lokasi')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="offline_petunjuk" class="mb-1 block text-sm font-semibold">Petunjuk</label>
                <textarea id="offline_petunjuk" name="offline_petunjuk" rows="3" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">{{ old('offline_petunjuk', $pengaturan['offline']['petunjuk']) }}</textarea>
                @error('offline_petunjuk')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
            </div>
        </fieldset>

        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white hover:bg-sekunder">Simpan Pengaturan</button>
    </form>

</section>
@endsection