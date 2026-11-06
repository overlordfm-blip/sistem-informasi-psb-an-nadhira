@extends('layouts.admin')

@section('title', 'Ubah Informasi Pondok')

@section('content')
<section>
    <h2>Ubah Informasi Pondok</h2>
    <p>Perbarui konten informasi: <strong>{{ $informasi->judul }}</strong></p>

    <x-kartu class="max-w-3xl">
    <form action="{{ route('admin.informasi.update', $informasi->id_informasi) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.informasi.partials.form-fields')

        <div class="flex flex-wrap gap-3 border-t border-garis pt-5">
            <x-tombol type="submit">Simpan Perubahan</x-tombol>
            <x-tombol varian="sekunder" href="{{ route('admin.informasi.index') }}">Batal</x-tombol>
        </div>
    </form>
    </x-kartu>
</section>
@endsection
