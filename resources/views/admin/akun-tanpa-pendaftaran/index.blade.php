@extends('layouts.admin')

@section('title', 'Akun Tanpa Pendaftaran')

@section('content')
<section>
    <h2>Akun Calon Santri Tanpa Pendaftaran</h2>
    <form action="{{ route('admin.akun-tanpa-pendaftaran.index') }}" method="GET" class="mb-6 flex flex-col gap-4 rounded-sm border border-garis bg-permukaan p-4 sm:flex-row sm:items-end">
        <div class="min-w-52 flex-1">
            <label for="cari">Cari Nama User</label>
            <input type="search" id="cari" name="cari" value="{{ request('cari') }}">
        </div>
        <x-tombol type="submit">Cari</x-tombol>
        <x-tombol varian="sekunder" href="{{ route('admin.akun-tanpa-pendaftaran.index') }}">Atur Ulang</x-tombol>
    </form>
</section>

<section>
    @if ($akunList->isEmpty())
        <p>Tidak ada akun calon santri tanpa pendaftaran.</p>
    @else
        <x-tabel>
            <thead>
                <tr>
                    <th>Nama User</th>
                    <th>Peran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($akunList as $akun)
                    <tr>
                        <td>{{ $akun->nama_user }}</td>
                        <td>{{ $akun->peran }}</td>
                        <td>
                            <form action="{{ route('admin.akun-tanpa-pendaftaran.destroy', $akun->id_user) }}"
                                  method="POST"
                                  onsubmit="return confirm('Hapus akun calon santri {{ $akun->nama_user }}?');">
                                @csrf
                                @method('DELETE')
                                <x-tombol varian="bahaya" type="submit" class="px-3 py-1 text-sm">Hapus</x-tombol>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-tabel>

        {{ $akunList->links() }}
    @endif
</section>
@endsection