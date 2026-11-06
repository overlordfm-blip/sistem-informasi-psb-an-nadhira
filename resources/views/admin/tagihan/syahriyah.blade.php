@extends('layouts.admin')

@section('title', 'Buat Tagihan Syahriyah')

@section('content')
<section>
    <h2>Buat Tagihan Syahriyah</h2>

    @if ($hasil = session('hasilBatch'))
        <div role="status">
            <p>Tagihan dibuat {{ $hasil['dibuat'] }}, dilewati {{ $hasil['dilewati'] }}.</p>
            @if ($hasil['alasan'])
                <h3>Santri yang dilewati</h3>
                <ul>
                    @foreach ($hasil['alasan'] as $item)
                        <li><strong>{{ $item['nama'] }}:</strong> {{ $item['alasan'] }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <x-kartu class="max-w-3xl">
    <form action="{{ route('admin.tagihan.syahriyah.store') }}" method="POST" class="space-y-5">
        @csrf

        <x-input name="nominal" label="Nominal (rupiah)" type="number" min="1000" step="1" :value="config('pondok.syahriyah')" required />

        <div>
            <label for="sasaran" class="mb-1 block text-base font-semibold">Sasaran</label>
            <select id="sasaran" name="sasaran" required class="min-h-11 w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks focus:border-primer focus:outline-2 focus:outline-primer">
                <option value="semua" @selected(old('sasaran', 'semua') === 'semua')>Semua santri aktif</option>
                <option value="satu" @selected(old('sasaran') === 'satu')>Satu santri</option>
            </select>
            @error('sasaran')<p role="alert" class="mt-1 text-base text-bahaya">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="id_santri" class="mb-1 block text-base font-semibold">Santri</label>
            <select id="id_santri" name="id_santri" class="min-h-11 w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks focus:border-primer focus:outline-2 focus:outline-primer">
                <option value="">Pilih santri aktif</option>
                @foreach ($santriAktif as $santri)
                    <option value="{{ $santri->id_santri }}" @selected((string) old('id_santri') === (string) $santri->id_santri)>
                        {{ $santri->pendaftar?->nama_lengkap ?? 'Santri #' . $santri->id_santri }}
                    </option>
                @endforeach
            </select>
            @error('id_santri')<p role="alert" class="mt-1 text-base text-bahaya">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-start gap-3">
            <input type="checkbox" id="paksa" name="paksa" value="1" @checked(old('paksa')) class="mt-1 size-4 accent-primer">
            <label for="paksa" class="text-base">Tetap buat meski syahriyah bulan ini sudah dibayar</label>
            @error('paksa')<p role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap gap-3 border-t border-garis pt-5">
            <x-tombol type="submit">Buat Tagihan</x-tombol>
            <x-tombol varian="sekunder" href="{{ route('admin.tagihan.index') }}">Kembali</x-tombol>
        </div>
    </form>
    </x-kartu>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sasaran = document.getElementById('sasaran');
        var santri = document.getElementById('id_santri');

        function perbaruiSantriWajib() {
            santri.required = sasaran.value === 'satu';
            santri.disabled = sasaran.value !== 'satu';
        }

        sasaran.addEventListener('change', perbaruiSantriWajib);
        perbaruiSantriWajib();
    });
</script>
@endsection