@props(['title' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' - ' : '' }}{{ config('app.name', 'An-Nadhira') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-latar text-teks antialiased">
    <header class="border-b border-garis bg-permukaan">
        <nav class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-8" aria-label="Navigasi utama">
            <a href="{{ route('beranda') }}" class="flex items-center gap-3 self-start rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primer">
                <span class="flex size-12 items-center justify-center rounded-full bg-latar">
                    <img src="{{ asset('images/logo-128.png') }}" alt="" class="size-10 object-contain">
                </span>
                <span class="font-serif text-lg font-bold text-primer">{{ config('pondok.nama') }}</span>
            </a>
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-base font-semibold">
                <a href="{{ route('beranda') }}" class="text-teks underline-offset-4 hover:text-sekunder hover:underline">Beranda</a>
                <a href="{{ route('beranda') }}#tentang" class="text-teks underline-offset-4 hover:text-sekunder hover:underline">Tentang</a>
                @if (! auth()->check() || ! auth()->user()->isSantri())
                    <a href="{{ route('beranda') }}#alur" class="text-teks underline-offset-4 hover:text-sekunder hover:underline">Alur</a>
                @endif
                <a href="{{ route('beranda') }}#kontak" class="text-teks underline-offset-4 hover:text-sekunder hover:underline">Kontak</a>
                @guest
                    <a href="{{ route('login') }}" class="rounded-sm bg-primer px-4 py-2 text-white hover:bg-sekunder">Masuk</a>
                @else
                    @if (auth()->user()->isSantri())
                        <a href="{{ route('tagihan') }}" class="rounded-sm bg-primer px-4 py-2 text-white hover:bg-sekunder">Halaman Saya</a>
                    @elseif (auth()->user()->isCalonSantri())
                        <a href="{{ route('status-pendaftaran') }}" class="rounded-sm bg-primer px-4 py-2 text-white hover:bg-sekunder">Halaman Saya</a>
                    @else
                        <a href="{{ route('admin.dashboard') }}" class="rounded-sm bg-primer px-4 py-2 text-white hover:bg-sekunder">Halaman Saya</a>
                    @endif
                @endguest
            </div>
        </nav>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-garis bg-permukaan">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-6 text-base sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <p class="font-semibold">{{ config('pondok.nama') }}</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'An-Nadhira') }}</p>
        </div>
    </footer>
</body>
</html>