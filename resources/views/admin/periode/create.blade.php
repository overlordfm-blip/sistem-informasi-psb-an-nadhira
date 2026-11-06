@extends('layouts.admin')

@section('title', 'Tambah Periode Pendaftaran')

@section('content')
<section>
    <h2>Tambah Periode Pendaftaran</h2>
    <p>Masukkan informasi periode gelombang pendaftaran baru.</p>

    <x-kartu class="max-w-3xl">
    <form action="{{ route('admin.periode.store') }}" method="POST" class="space-y-6">
        @csrf

        @include('admin.periode.partials.form-fields')

        <div class="flex flex-wrap gap-3 border-t border-garis pt-5">
            <x-tombol type="submit">Simpan Periode</x-tombol>
            <x-tombol varian="sekunder" href="{{ route('admin.periode.index') }}">Batal</x-tombol>
        </div>
    </form>
    </x-kartu>
</section>
@endsection
