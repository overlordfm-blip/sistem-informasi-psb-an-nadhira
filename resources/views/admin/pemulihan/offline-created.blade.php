@extends('layouts.admin')

@section('title', 'Akses Pemulihan Offline')

@section('content')
<section class="mx-auto max-w-2xl space-y-6">
    <header>
        <h1 class="font-serif text-3xl font-bold text-primer">Akses Pemulihan Dibuka</h1>
        <p class="mt-2">Berikan kode berikut langsung kepada {{ $akun->nama_user }}. Kode berlaku selama 30 menit dan hanya dapat digunakan sekali.</p>
    </header>
    <div class="border-y border-garis py-5">
        <p class="text-sm font-semibold">Kode pemulihan</p>
        <p class="mt-2 font-mono text-3xl font-bold tracking-[0.2em]" data-kode-pemulihan>{{ $permintaan['kode'] }}</p>
    </div>
    <a href="{{ route('admin.akun.index') }}" class="font-semibold text-primer underline underline-offset-4">Kembali ke daftar akun</a>
</section>
@endsection