@extends('layouts.admin')

@section('title', 'Data Pembayaran')

@section('content')
<section>
    <h1>Data Pembayaran</h1>
    <p>Daftar seluruh pembayaran. Verifikasi status pembayaran dilakukan otomatis melalui webhook Midtrans.</p>
</section>

{{-- Formulir Pencarian dan Filter --}}
<section>
    <form method="GET" action="{{ route('admin.pembayaran.index') }}" class="mb-6 flex flex-col gap-4 rounded-sm border border-garis bg-permukaan p-4 lg:flex-row lg:items-end">
        <div class="min-w-52 flex-1">
            <label for="cari">Cari Nama Pendaftar</label>
            <input type="text"
                   id="cari"
                   name="cari"
                   value="{{ request('cari') }}"
                   placeholder="Ketik nama pendaftar...">
        </div>

        <div>
            <label for="status">Filter Status Bayar</label>
            <select id="status" name="status">
                <option value="">Semua Status</option>
                <option value="{{ \App\Models\Pembayaran::STATUS_MENUNGGU }}"
                    {{ request('status') === \App\Models\Pembayaran::STATUS_MENUNGGU ? 'selected' : '' }}>
                    Menunggu
                </option>
                <option value="{{ \App\Models\Pembayaran::STATUS_BERHASIL }}"
                    {{ request('status') === \App\Models\Pembayaran::STATUS_BERHASIL ? 'selected' : '' }}>
                    Berhasil
                </option>
                <option value="{{ \App\Models\Pembayaran::STATUS_GAGAL }}"
                    {{ request('status') === \App\Models\Pembayaran::STATUS_GAGAL ? 'selected' : '' }}>
                    Gagal
                </option>
                <option value="{{ \App\Models\Pembayaran::STATUS_KEDALUWARSA }}"
                    {{ request('status') === \App\Models\Pembayaran::STATUS_KEDALUWARSA ? 'selected' : '' }}>
                    Kedaluwarsa
                </option>
            </select>
        </div>

        <div>
            <label for="jenis">Filter Jenis Tagihan</label>
            <select id="jenis" name="jenis">
                <option value="">Semua Jenis</option>
                <option value="{{ \App\Models\Tagihan::JENIS_PENDAFTARAN }}"
                    {{ request('jenis') === \App\Models\Tagihan::JENIS_PENDAFTARAN ? 'selected' : '' }}>
                    Pendaftaran
                </option>
                <option value="{{ \App\Models\Tagihan::JENIS_SYAHRIYAH }}"
                    {{ request('jenis') === \App\Models\Tagihan::JENIS_SYAHRIYAH ? 'selected' : '' }}>
                    Syahriyah
                </option>
            </select>
        </div>

        <x-tombol type="submit">Cari / Filter</x-tombol>
        @if (request()->hasAny(['cari', 'status', 'jenis']))
            <x-tombol varian="sekunder" href="{{ route('admin.pembayaran.index') }}">Reset</x-tombol>
        @endif
    </form>
</section>

<br>

{{-- Tabel Data Pembayaran --}}
<section>
    <h2>Daftar Pembayaran</h2>

    @if ($pembayaranList->isEmpty())
        <p>Belum ada data pembayaran yang sesuai.</p>
    @else
        <x-tabel>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Pendaftar</th>
                    <th>Jenis Tagihan</th>
                    <th>Jumlah Bayar</th>
                    <th>Tanggal Bayar</th>
                    <th>Metode</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pembayaranList as $index => $bayar)
                    @php
                        // Ambil transaksi berhasil, atau transaksi terakhir bila belum ada
                        $trxBerhasil = $bayar->transaksi->firstWhere('status_transaksi', \App\Models\Transaksi::STATUS_BERHASIL);
                        $trxTerakhir = $bayar->transaksi->sortByDesc('id_transaksi')->first();
                        $trx = $trxBerhasil ?? $trxTerakhir;
                    @endphp
                    <tr>
                        <td>{{ $pembayaranList->firstItem() + $index }}</td>
                        <td>{{ $bayar->tagihan?->pendaftar?->nama_lengkap ?? '-' }}</td>
                        <td>{{ ucfirst($bayar->tagihan?->jenis_tagihan ?? '-') }}</td>
                        <td>Rp {{ number_format($bayar->jumlah_bayar, 0, ',', '.') }}</td>
                        <td>{{ $bayar->tanggal_bayar?->translatedFormat('d F Y') ?? '-' }}</td>
                        <td>{{ $trx?->metode ?? '-' }}</td>
                        <td><x-badge-status :status="$bayar->status_bayar" :label="strtoupper($bayar->status_bayar)" /></td>
                    </tr>
                @endforeach
            </tbody>
        </x-tabel>

        <br>

        <nav aria-label="Navigasi Halaman Data Pembayaran">
            {{ $pembayaranList->links() }}
        </nav>
    @endif
</section>
@endsection
