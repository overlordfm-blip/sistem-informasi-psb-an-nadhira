@extends('layouts.admin')

@section('title', 'Data Pendaftar')

@section('content')
<section>
    <h2>Data Pendaftar Santri Baru</h2>
    <p>Kelola dan verifikasi pendaftaran calon santri baru.</p>
</section>

<!-- Formulir Pencarian dan Filter Status -->
<section>
    <form method="GET" action="{{ route('admin.pendaftar.index') }}" class="mb-6 flex flex-col gap-4 rounded-sm border border-garis bg-permukaan p-4 sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="cari">Cari Nama:</label>
            <input type="text"
                   id="cari"
                   name="cari"
                   value="{{ request('cari') }}"
                   placeholder="Ketik nama pendaftar...">
        </div>

        <div>
            <label for="status">Filter Status:</label>
            <select id="status" name="status">
                <option value="">Semua Status</option>
                <option value="{{ \App\Models\Pendaftar::STATUS_DIPROSES }}" {{ request('status') === \App\Models\Pendaftar::STATUS_DIPROSES ? 'selected' : '' }}>
                    Diproses
                </option>
                <option value="{{ \App\Models\Pendaftar::STATUS_DITERIMA }}" {{ request('status') === \App\Models\Pendaftar::STATUS_DITERIMA ? 'selected' : '' }}>
                    Diterima
                </option>
                <option value="{{ \App\Models\Pendaftar::STATUS_DITOLAK }}" {{ request('status') === \App\Models\Pendaftar::STATUS_DITOLAK ? 'selected' : '' }}>
                    Ditolak
                </option>
            </select>
        </div>
        <div>
            <label for="tes">Filter Tes:</label>
            <select id="tes" name="tes">
                <option value="">Semua Tahap Tes</option>
                <option value="belum_memilih" {{ request('tes') === 'belum_memilih' ? 'selected' : '' }}>Belum Memilih Mode</option>
                <option value="menunggu" {{ request('tes') === 'menunggu' ? 'selected' : '' }}>Menunggu Pelaksanaan</option>
                <option value="lulus" {{ request('tes') === 'lulus' ? 'selected' : '' }}>Lulus</option>
                <option value="tidak_lulus" {{ request('tes') === 'tidak_lulus' ? 'selected' : '' }}>Tidak Lulus</option>
            </select>
        </div>
        <x-tombol type="submit">Cari / Filter</x-tombol>
        @if (request()->hasAny(['cari', 'status']))
            <x-tombol varian="sekunder" href="{{ route('admin.pendaftar.index') }}">Reset</x-tombol>
        @endif
    </form>
</section>

<br>

<!-- Tabel Data Pendaftar -->
<section>
    <h3>Daftar Calon Santri</h3>

    @if ($pendaftarList->isEmpty())
        <p>Belum ada data pendaftar yang sesuai.</p>
    @else
        <x-tabel>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Nama Wali</th>
                    <th>No. Telepon</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th>Tes</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pendaftarList as $index => $item)
                    <tr>
                        <td>{{ $pendaftarList->firstItem() + $index }}</td>
                        <td>{{ $item->nama_lengkap }}</td>
                        <td>{{ $item->nama_wali }}</td>
                        <td>{{ $item->no_telepon }}</td>
                        <td>{{ $item->periode?->nama_periode ?? '-' }}</td>
                        <td><x-badge-status :status="$item->status_daftar" :label="strtoupper($item->status_daftar)" /></td>
                        <td>
                            @php
                                $tes = $item->tes_seleksi ?? [];
                                $mode = $tes['mode'] ?? null;
                                $hasil = $tes['hasil'] ?? 'belum';
                            @endphp
                            @if ($hasil === 'lulus')
                                <span class="text-sekunder font-semibold">Lulus{{ $mode === 'online' ? ' (Online)' : ($mode === 'offline' ? ' (Offline)' : '') }}</span>
                            @elseif ($hasil === 'tidak_lulus')
                                <span class="text-bahaya font-semibold">Tidak Lulus{{ $mode === 'online' ? ' (Online)' : ($mode === 'offline' ? ' (Offline)' : '') }}</span>
                            @elseif ($mode)
                                <span class="text-teks font-semibold">Menunggu ({{ $mode === 'online' ? 'Online' : 'Offline' }})</span>
                            @else
                                <span class="text-teks/60 font-semibold">Belum Memilih</span>
                            @endif
                        </td>
                        <td>
                            <x-tombol varian="sekunder" href="{{ route('admin.pendaftar.show', $item->id_pendaftar) }}" class="px-3 py-1 text-sm">Lihat</x-tombol>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-tabel>

        <br>

        <!-- Navigasi Paginasi -->
        <nav aria-label="Navigasi Halaman Data Pendaftar">
            {{ $pendaftarList->links() }}
        </nav>
    @endif
</section>
@endsection
