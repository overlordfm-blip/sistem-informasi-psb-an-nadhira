@extends('layouts.admin')

@section('title', 'Ubah Periode Pendaftaran')

@section('content')
<section>
    <h2>Ubah Periode Pendaftaran</h2>
    <p>Perbarui informasi periode: <strong>{{ $periode->nama_periode }}</strong></p>

    <x-kartu class="max-w-3xl">
    <form action="{{ route('admin.periode.update', $periode->id_periode) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        @include('admin.periode.partials.form-fields')

        <div class="flex flex-wrap gap-3 border-t border-garis pt-5">
            <x-tombol type="submit">Simpan Perubahan</x-tombol>
            <x-tombol varian="sekunder" href="{{ route('admin.periode.index') }}">Batal</x-tombol>
        </div>
    </form>
    </x-kartu>
</section>
@endsection
