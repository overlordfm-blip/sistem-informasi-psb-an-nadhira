@extends('layouts.admin')

@section('title', 'Kelola Tagihan')

@section('content')
<section>
    <h2>Kelola Tagihan</h2>
    <p>
        <x-tombol href="{{ route('admin.tagihan.syahriyah') }}" class="mt-2">Buat Tagihan Syahriyah</x-tombol>
    </p>

    <form action="{{ route('admin.tagihan.index') }}" method="GET" class="mb-6 flex flex-col gap-4 rounded-sm border border-garis bg-permukaan p-4 lg:flex-row lg:items-end">
        <div class="min-w-52 flex-1">
            <label for="cari">Nama Pendaftar</label>
            <input type="search" id="cari" name="cari" value="{{ request('cari') }}">
        </div>

        <div>
            <label for="jenis">Jenis Tagihan</label>
            <select id="jenis" name="jenis">
            <option value="">Semua jenis</option>
            <option value="pendaftaran" @selected(request('jenis') === 'pendaftaran')>Pendaftaran</option>
            <option value="syahriyah" @selected(request('jenis') === 'syahriyah')>Syahriyah</option>
            </select>
        </div>

        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
            <option value="">Semua status</option>
            <option value="belum_dibayar" @selected(request('status') === 'belum_dibayar')>Belum dibayar</option>
            <option value="lunas" @selected(request('status') === 'lunas')>Lunas</option>
            </select>
        </div>

        <x-tombol type="submit">Cari</x-tombol>
        <x-tombol varian="sekunder" href="{{ route('admin.tagihan.index') }}">Atur Ulang</x-tombol>
    </form>
</section>

<section>
    @if ($tagihanList->isEmpty())
        <p>Belum ada tagihan yang sesuai.</p>
    @else
        <x-tabel>
            <thead>
                <tr>
                    <th>Nama Pendaftar</th>
                    <th>Jenis</th>
                    <th>Nominal</th>
                    <th>Status</th>
                    <th>Tanggal Bayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tagihanList as $tagihan)
                    <tr>
                        <td>{{ $tagihan->pendaftar?->nama_lengkap ?? '-' }}</td>
                        <td>{{ ucfirst($tagihan->jenis_tagihan) }}</td>
                        <td>Rp {{ number_format($tagihan->nominal, 0, ',', '.') }}</td>
                        <td><x-badge-status :status="$tagihan->status_tagihan" /></td>
                        <td>{{ $tagihan->pembayaran?->tanggal_bayar?->translatedFormat('d F Y') ?? '-' }}</td>
                        <td>
                            @if ($tagihan->status_tagihan === \App\Models\Tagihan::STATUS_BELUM_DIBAYAR && ! $tagihan->pembayaran)
                                <form action="{{ route('admin.tagihan.destroy', $tagihan->id_tagihan) }}" method="POST"
                                      onsubmit="return confirm('Hapus tagihan ini? Tindakan ini tidak dapat dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <x-tombol varian="bahaya" type="submit" class="px-3 py-1 text-sm">Hapus</x-tombol>
                                </form>
                            @else
                                <span>-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-tabel>

        {{ $tagihanList->links() }}
    @endif
</section>
@endsection