@extends('layouts.admin')

@section('title', 'Kelola Informasi Pondok')

@section('content')
<section>
    <h2>Kelola Informasi Pondok</h2>
    <p>Kelola artikel, profil, pengumuman, dan ketentuan pondok pesantren yang ditampilkan ke publik.</p>

    <x-tombol href="{{ route('admin.informasi.create') }}" class="mt-3">Tambah Informasi Baru</x-tombol>
</section>

<!-- Tabel Daftar Informasi -->
<section>
    <h3>Daftar Informasi Pondok</h3>
    @if ($informasiList->isEmpty())
        <p>Belum ada data informasi pondok.</p>
    @else
        <x-tabel>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Judul Informasi</th>
                    <th>Terakhir Diperbarui</th>
                    <th>Pengelola (Admin)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($informasiList as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->judul }}</strong>
                            <p><small>{{ \Illuminate\Support\Str::limit($item->isi, 80) }}</small></p>
                        </td>
                        <td class="whitespace-nowrap">{{ $item->diperbarui ? $item->diperbarui->translatedFormat('d F Y') : '-' }}</td>
                        <td>{{ $item->user->nama_user ?? '-' }}</td>
                        <td>
                            <x-tombol varian="sekunder" href="{{ route('admin.informasi.edit', $item->id_informasi) }}" class="px-3 py-1 text-sm">Ubah</x-tombol>
                            <form action="{{ route('admin.informasi.destroy', $item->id_informasi) }}"
                                  method="POST"
                                  style="display:inline;"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus informasi ini?');">
                                @csrf
                                @method('DELETE')
                                <x-tombol varian="bahaya" type="submit" class="px-3 py-1 text-sm">Hapus</x-tombol>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-tabel>
    @endif
</section>
@endsection
