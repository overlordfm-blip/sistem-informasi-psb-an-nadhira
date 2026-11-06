@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')
<section>
    <h1>Dashboard Admin</h1>
    <p>Selamat datang, <strong>{{ auth()->user()->nama_user }}</strong>.</p>
</section>

<section>
    <h2>Periode Pendaftaran</h2>
    @if ($periodeTersedia)
        <p>Pendaftaran dibuka sampai {{ $periodeTersedia->tanggal_tutup->translatedFormat('d F Y') }}.</p>
    @elseif ($periodeTerjadwal)
        <p>Pendaftaran akan dibuka pada {{ $periodeTerjadwal->tanggal_buka->translatedFormat('d F Y') }}.</p>
    @else
        <p>Pendaftaran ditutup.</p>
    @endif
</section>

{{-- ── Kartu statistik pendaftar ────────────────────────────────────────── --}}
<section>
    <h2>Ringkasan Pendaftar</h2>
    <x-tabel>
        <thead>
            <tr>
                <th>Total Pendaftar</th>
                <th>Diproses</th>
                <th>Diterima</th>
                <th>Ditolak</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td id="jumlah-total">{{ $totalPendaftar }}</td>
                <td id="jumlah-diproses">{{ $diproses }}</td>
                <td id="jumlah-diterima">{{ $diterima }}</td>
                <td id="jumlah-ditolak">{{ $ditolak }}</td>
            </tr>
        </tbody>
    </x-tabel>
</section>

<br>

{{-- ── Lima pendaftar terbaru ───────────────────────────────────────────── --}}
<section>
    <h2>Lima Pendaftar Terbaru</h2>
    @if ($pendaftarTerbaru->isEmpty())
        <p>Belum ada data pendaftar.</p>
    @else
        <x-tabel>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Lengkap</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendaftarTerbaru as $p)
                    <tr>
                        <td>{{ $p->id_pendaftar }}</td>
                        <td>{{ $p->nama_lengkap }}</td>
                        <td><x-badge-status :status="$p->status_daftar" :label="strtoupper($p->status_daftar)" /></td>
                    </tr>
                @endforeach
            </tbody>
        </x-tabel>
    @endif
    <x-tombol href="{{ route('admin.pendaftar.index') }}" class="mt-4">Lihat Semua Pendaftar</x-tombol>
</section>

<br>

{{-- ── Lima pembayaran terbaru ──────────────────────────────────────────── --}}
<section>
    <h2>Lima Pembayaran Terbaru</h2>
    @if ($pembayaranTerbaru->isEmpty())
        <p>Belum ada data pembayaran.</p>
    @else
        <x-tabel>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Pendaftar</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pembayaranTerbaru as $bayar)
                    <tr>
                        <td>{{ $bayar->id_pembayaran }}</td>
                        <td>{{ $bayar->tagihan?->pendaftar?->nama_lengkap ?? '-' }}</td>
                        <td>Rp {{ number_format($bayar->jumlah_bayar, 0, ',', '.') }}</td>
                        <td><x-badge-status :status="$bayar->status_bayar" :label="strtoupper($bayar->status_bayar)" /></td>
                    </tr>
                @endforeach
            </tbody>
        </x-tabel>
    @endif
    <x-tombol href="{{ route('admin.pembayaran.index') }}" class="mt-4">Lihat Semua Pembayaran</x-tombol>
</section>

@endsection
