@extends('layouts.admin')

@section('title', 'Periode Pendaftaran')

@section('content')
<section>
    <h2>Periode Pendaftaran</h2>
    <p>Kelola jadwal buka dan tutup gelombang penerimaan santri baru.</p>

    <x-tombol href="{{ route('admin.periode.create') }}" class="mt-3">Tambah Periode Baru</x-tombol>
</section>

<!-- Box Periode Saat Ini -->
<section>
    <h3>Periode Saat Ini</h3>
    @if ($periodeAktif || $periodeTerjadwal)
        @php($periodeDitampilkan = $periodeAktif ?? $periodeTerjadwal)
        <article>
            <p><strong>Nama Periode:</strong> {{ $periodeDitampilkan->nama_periode }}</p>
            <p><strong>Tanggal Buka:</strong> {{ $periodeDitampilkan->tanggal_buka ? $periodeDitampilkan->tanggal_buka->translatedFormat('d F Y') : '-' }}</p>
            <p><strong>Tanggal Tutup:</strong> {{ $periodeDitampilkan->tanggal_tutup ? $periodeDitampilkan->tanggal_tutup->translatedFormat('d F Y') : '-' }}</p>
            <p class="flex flex-wrap items-center gap-2"><strong>Status:</strong> <x-badge-status :status="$periodeDitampilkan->status_periode" :label="$periodeDitampilkan->labelStatus()" /></p>
            <p><strong>Sisa Kuota:</strong> {{ $kuota > 0 ? max(0, $kuota - $periodeDitampilkan->pendaftar_count) . ' dari ' . $kuota : 'Tanpa batas' }}</p>
            <form action="{{ route('admin.periode.tutup', $periodeDitampilkan->id_periode) }}" method="POST">
                @csrf
                <x-tombol varian="sekunder" type="submit">Tutup Pendaftaran</x-tombol>
            </form>
        </article>
    @else
        <p><em>Saat ini tidak ada periode pendaftaran yang sedang dibuka.</em></p>
    @endif
</section>

<br>

<!-- Tabel Daftar Periode -->
<section>
    <h3>Daftar Semua Periode</h3>
    @if ($periodeList->isEmpty())
        <p>Belum ada data periode pendaftaran.</p>
    @else
        <x-tabel>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Periode</th>
                    <th>Tanggal Buka</th>
                    <th>Tanggal Tutup</th>
                    <th>Status</th>
                    <th>Sisa Kuota</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($periodeList as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->nama_periode }}</td>
                        <td>{{ $item->tanggal_buka ? $item->tanggal_buka->translatedFormat('d F Y') : '-' }}</td>
                        <td>{{ $item->tanggal_tutup ? $item->tanggal_tutup->translatedFormat('d F Y') : '-' }}</td>
                        <td>
                            <x-badge-status :status="$item->status_periode" :label="$item->labelStatus()" />
                        </td>
                        <td>{{ $kuota > 0 ? max(0, $kuota - $item->pendaftar_count) . ' dari ' . $kuota : 'Tanpa batas' }}</td>
                        <td>
                            <x-tombol varian="sekunder" href="{{ route('admin.periode.edit', $item->id_periode) }}" class="px-3 py-1 text-sm">Ubah</x-tombol>
                            @if ($item->isDibuka())
                                <form action="{{ route('admin.periode.tutup', $item->id_periode) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <x-tombol varian="sekunder" type="submit" class="px-3 py-1 text-sm">Tutup</x-tombol>
                                </form>
                            @else
                                <form action="{{ route('admin.periode.buka', $item->id_periode) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <x-tombol type="submit" class="px-3 py-1 text-sm">Buka</x-tombol>
                                </form>
                            @endif
                            <form action="{{ route('admin.periode.destroy', $item->id_periode) }}"
                                  method="POST"
                                  style="display:inline;"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus periode pendaftaran ini?');">
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
