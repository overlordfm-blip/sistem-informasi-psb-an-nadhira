@extends('layouts.admin')

@section('title', 'Detail Verifikasi Pendaftar')

@section('content')
<section>
    <h2>Detail Verifikasi Pendaftar</h2>
    <p>Periksa data calon santri dan tentukan persetujuan penerimaan.</p>
</section>

<!-- Rincian Data Pendaftar -->
<section>
    <article>
        <h3>Data Calon Santri</h3>

        <x-tabel>
            <tbody>
                <tr>
                    <th align="left">Nama Lengkap</th>
                    <td>{{ $pendaftar->nama_lengkap }}</td>
                </tr>
                <tr>
                    <th align="left">Tanggal Lahir</th>
                    <td>{{ $pendaftar->tanggal_lahir ? $pendaftar->tanggal_lahir->translatedFormat('d F Y') : '-' }}</td>
                </tr>
                <tr>
                    <th align="left">Alamat Tempat Tinggal</th>
                    <td>{{ $pendaftar->alamat }}</td>
                </tr>
                <tr>
                    <th align="left">Nama Orang Tua / Wali</th>
                    <td>{{ $pendaftar->nama_wali }}</td>
                </tr>
                <tr>
                    <th align="left">Nomor Telepon</th>
                    <td>{{ $pendaftar->no_telepon }}</td>
                </tr>
                <tr>
                    <th align="left">Gelombang Periode</th>
                    <td>{{ $pendaftar->periode?->nama_periode ?? '-' }}</td>
                </tr>
                <tr>
                    <th align="left">Status Pendaftaran</th>
                    <td>
                        <x-badge-status :status="$pendaftar->status_daftar" :label="strtoupper($pendaftar->status_daftar)" />
                    </td>
                </tr>
            </tbody>
        </x-tabel>
    </article>
</section>

<!-- Kartu Tes Seleksi -->
<x-kartu aria-label="Tes Seleksi Pendaftar" class="mb-6">
    <h3 class="font-serif text-xl font-bold text-primer">Tes Seleksi</h3>
    <div class="mt-3 flex flex-wrap items-center gap-4">
        <span class="font-semibold">Mode:</span>
        <span>{{ $tesSeleksi['mode'] ?? '-' }}</span>
        <span class="font-semibold">Hasil:</span>
        <span class="{{ ($tesSeleksi['hasil'] ?? 'belum') === 'lulus' ? 'text-sekunder font-semibold' : (($tesSeleksi['hasil'] ?? 'belum') === 'tidak_lulus' ? 'text-bahaya font-semibold' : 'text-teks font-semibold') }}">{{ $tesSeleksi['hasil'] ?? 'Belum ada hasil' }}</span>
        @if (($tesSeleksi['nilai'] ?? null) !== null)
            <span class="font-semibold">Nilai:</span>
            <span>{{ $tesSeleksi['nilai'] }}</span>
        @endif
    </div>
    @if (isset($tesSeleksi['catatan']) && $tesSeleksi['catatan'] !== null)
        <p class="mt-3 whitespace-pre-line text-base leading-7">{{ $tesSeleksi['catatan'] }}</p>
    @endif
    @if (isset($tesSeleksi['dinilai_pada']) && $tesSeleksi['dinilai_pada'])
        <p class="mt-2 text-sm text-teks/60">Dinilai pada {{ \Carbon\Carbon::parse($tesSeleksi['dinilai_pada'])->translatedFormat('d F Y H:i') }} WIB</p>
    @endif

    @if ($pendaftar->status_daftar === \App\Models\Pendaftar::STATUS_DIPROSES)
        <form action="{{ route('admin.pendaftar.tes', $pendaftar->id_pendaftar) }}" method="POST" class="mt-5 space-y-3">
            @csrf
            <div>
                <label for="hasil" class="mb-1 block text-base font-semibold">Hasil Tes Seleksi</label>
                <select id="hasil" name="hasil" class="min-h-11 w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks focus:border-primer focus:outline-2 focus:outline-primer">
                    <option value="belum" {{ old('hasil', $tesSeleksi['hasil'] ?? 'belum') === 'belum' ? 'selected' : '' }}>Belum Dinilai</option>
                    <option value="lulus" {{ old('hasil', $tesSeleksi['hasil'] ?? 'belum') === 'lulus' ? 'selected' : '' }}>Lulus</option>
                    <option value="tidak_lulus" {{ old('hasil', $tesSeleksi['hasil'] ?? 'belum') === 'tidak_lulus' ? 'selected' : '' }}>Tidak Lulus</option>
                </select>
                @error('hasil')
                    <p id="hasil-error" class="mt-1 text-base text-bahaya">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="nilai" class="mb-1 block text-base font-semibold">Nilai (0–100, opsional)</label>
                <input id="nilai" name="nilai" type="number" min="0" max="100" value="{{ old('nilai', $tesSeleksi['nilai'] ?? null) }}" class="min-h-11 w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks focus:border-primer focus:outline-2 focus:outline-primer">
                @error('nilai')
                    <p id="nilai-error" class="mt-1 text-base text-bahaya">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="catatan_tes" class="mb-1 block text-base font-semibold">Catatan (opsional)</label>
                <textarea id="catatan_tes" name="catatan_tes" rows="3" class="w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks placeholder:text-teks/60 focus:border-primer focus:outline-2 focus:outline-primer">{{ old('catatan_tes', $tesSeleksi['catatan'] ?? null) }}</textarea>
                @error('catatan_tes')
                    <p id="catatan_tes-error" class="mt-1 text-base text-bahaya">{{ $message }}</p>
                @enderror
            </div>
            <x-tombol type="submit">Simpan Hasil Tes</x-tombol>
        </form>
    @else
        <p class="mt-3 text-base"><em>Hasil tes hanya dapat diubah saat pendaftar masih berstatus diproses.</em></p>
    @endif
</x-kartu>

<br>

@if ($catatan)
    <x-kartu aria-label="Catatan Penolakan" class="mb-6 border-l-4 border-l-bahaya">
        <h3 class="font-serif text-xl font-bold text-bahaya">Catatan dari admin</h3>
        <p class="mt-3 whitespace-pre-line text-base leading-7">{{ $catatan }}</p>
    </x-kartu>
@endif

<br>

<!-- Aksi Verifikasi Penerimaan / Penolakan -->
<section>
    @if ($pendaftar->status_daftar === \App\Models\Pendaftar::STATUS_DIPROSES)
        <div class="flex flex-col gap-5">
            <form action="{{ route('admin.pendaftar.terima', $pendaftar->id_pendaftar) }}"
                  method="POST"
                  style="display:inline;"
                  onsubmit="return confirm('Apakah Anda yakin ingin MENERIMA pendaftar ini? Tagihan pendaftaran akan otomatis diterbitkan.');">
                @csrf
                <x-tombol type="submit">Terima Pendaftar</x-tombol>
            </form>

            <form action="{{ route('admin.pendaftar.tolak', $pendaftar->id_pendaftar) }}"
                  method="POST"
                  style="display:inline;"
                                    onsubmit="return confirm('Apakah Anda yakin ingin MENOLAK pendaftar ini dengan catatan tersebut?');">
                @csrf
                        <x-textarea name="catatan" label="Catatan penolakan (5–500 karakter)" rows="4" minlength="5" maxlength="500" required />
                        <x-tombol varian="bahaya" type="submit">Tolak Pendaftar</x-tombol>
            </form>
        </div>
    @else
        <p>
            <em>Pendaftar ini telah diverifikasi dengan status <strong>{{ strtoupper($pendaftar->status_daftar) }}</strong>. Tindakan terima atau tolak tidak lagi tersedia.</em>
        </p>
    @endif

    <x-tombol varian="sekunder" href="{{ route('admin.pendaftar.index') }}" class="mt-5">Kembali ke Daftar Pendaftar</x-tombol>
</section>
@endsection
