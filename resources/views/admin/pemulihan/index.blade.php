@extends('layouts.admin')

@section('title', 'Permintaan Pemulihan')

@section('content')
<section class="space-y-5">
    <header>
        <h1 class="font-serif text-3xl font-bold text-primer">Permintaan Pemulihan</h1>
        <p class="mt-2">Verifikasi identitas secara langsung sebelum membuka akses.</p>
    </header>

    <form method="GET" action="{{ route('admin.pemulihan.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
            <label for="kode" class="mb-1 block text-sm font-semibold">Cari kode</label>
            <input id="kode" name="kode" value="{{ request('kode') }}" type="search" maxlength="8" class="w-full rounded-sm border border-garis bg-white px-3 py-2 font-mono text-base uppercase">
        </div>
        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white">Cari</button>
        @if (request()->filled('kode'))
            <a href="{{ route('admin.pemulihan.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-sm border border-garis px-5 py-2 font-semibold text-primer">Reset</a>
        @endif
    </form>

    @if (count($permintaanList) === 0)
        <p class="border-y border-garis py-8 text-center">Tidak ada permintaan yang menunggu.</p>
    @else
        <div class="overflow-x-auto border-y border-garis">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <thead class="bg-permukaan">
                    <tr>
                        <th scope="col" class="px-3 py-3">Kode</th>
                        <th scope="col" class="px-3 py-3">Dibuat</th>
                        <th scope="col" class="px-3 py-3">Nama user diingat</th>
                        <th scope="col" class="px-3 py-3">Kecocokan</th>
                        <th scope="col" class="px-3 py-3">Nama lengkap</th>
                        <th scope="col" class="px-3 py-3">Nama wali</th>
                        <th scope="col" class="px-3 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-garis bg-white">
                    @foreach ($permintaanList as $item)
                        <tr>
                            <td class="px-3 py-3 font-mono font-bold tracking-wider">{{ $item['kode'] }}</td>
                            <td class="px-3 py-3">{{ \Carbon\Carbon::parse($item['dibuat_pada'])->format('d-m-Y H:i') }}</td>
                            <td class="px-3 py-3">{{ $item['nama_user_input'] ?: '-' }}</td>
                            <td class="px-3 py-3">{{ $item['nama_user_input'] ? ($item['nama_user_cocok'] ? 'Cocok' : 'Tidak cocok') : 'Tidak diisi' }}</td>
                            <td class="px-3 py-3">{{ $item['nama_lengkap'] }}</td>
                            <td class="px-3 py-3">{{ $item['nama_wali'] }}</td>
                            <td class="px-3 py-3"><a href="{{ route('admin.pemulihan.show', $item['kode']) }}" class="font-semibold text-primer underline underline-offset-4">Periksa</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $permintaanList->links() }}
    @endif
</section>
@endsection