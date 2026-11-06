@extends('layouts.admin')

@section('title', 'Tambah Informasi Pondok')

@section('content')
<section>
    <h2>Tambah Informasi Pondok</h2>
    <p>Buat artikel, profil, atau pengumuman informasi pondok pesantren baru.</p>

    <x-kartu class="max-w-3xl">
    <form action="{{ route('admin.informasi.store') }}" method="POST" class="space-y-6">
        @csrf

        @include('admin.informasi.partials.form-fields')

        <div class="flex flex-wrap gap-3 border-t border-garis pt-5">
            <x-tombol type="submit">Simpan Informasi</x-tombol>
            <x-tombol varian="sekunder" href="{{ route('admin.informasi.index') }}">Batal</x-tombol>
        </div>
    </form>
    </x-kartu>
</section>
@endsection
