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
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <a href="{{ route('beranda') }}" class="flex items-center gap-3 self-start">
                <span class="flex size-12 items-center justify-center rounded-full bg-latar"><img src="{{ asset('images/logo-128.png') }}" alt="" class="size-10 object-contain"></span>
                <span class="font-serif text-lg font-bold text-primer">{{ config('pondok.nama') }}</span>
            </a>
            <nav aria-label="Menu pengguna" class="flex flex-wrap items-center gap-4 text-base font-semibold">
                <a href="{{ route('beranda') }}" class="inline-flex items-center gap-2 hover:text-sekunder"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-7h6v7"/></svg>Beranda</a>
                @if (auth()->user()->isCalonSantri())
                    <a href="{{ route('status-pendaftaran') }}" class="inline-flex items-center gap-2 hover:text-sekunder"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M8 4h8l3 3v14H5V4zM8 12l2.5 2.5L16 9M8 18h8"/></svg>Status Pendaftaran</a>
                    <a href="{{ route('pendaftaran') }}" class="inline-flex items-center gap-2 hover:text-sekunder"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3h10l4 4v14H5zM15 3v5h5M8 12h8M8 16h6"/></svg>Formulir</a>
                    @php
                        $tampilkanTes = false;
                        $_pendaftar = auth()->user()->pendaftar;
                        if ($_pendaftar && $_pendaftar->status_daftar === \App\Models\Pendaftar::STATUS_DIPROSES) {
                            $tampilkanTes = true;
                        }
                    @endphp
                    @if ($tampilkanTes)
                        <a href="{{ route('tes') }}" class="inline-flex items-center gap-2 hover:text-sekunder"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 15h-2v-2h2v2zm0-8h-2V7h2v2z"/></svg>Tes Seleksi</a>
                    @endif
                @endif
                <a href="{{ route('tagihan') }}" class="inline-flex items-center gap-2 hover:text-sekunder"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h9l3 3v15H6zM15 3v4h4M9 11h6M9 15h6M9 18h3"/></svg>Tagihan</a>
                <a href="{{ route('akun.google') }}" class="inline-flex items-center gap-2 hover:text-sekunder">Tautan Gmail</a>
                <a href="{{ route('akun.password') }}" class="inline-flex items-center gap-2 hover:text-sekunder">Ubah Password</a>
                <span title="{{ auth()->user()->nama_tampilan }}" class="max-w-40 truncate text-sm font-semibold text-teks lg:ml-2 lg:border-l lg:border-garis lg:pl-4">{{ auth()->user()->nama_tampilan }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-sm bg-primer px-4 py-2 text-white hover:bg-sekunder"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>Keluar</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="mx-auto w-full max-w-7xl px-5 py-8 lg:px-8">
        <x-notifikasi class="mb-6" />
        {{ $slot }}
    </main>
    <footer class="border-t border-garis bg-permukaan px-5 py-5 text-center text-base">
        &copy; {{ date('Y') }} {{ config('app.name', 'An-Nadhira') }}
    </footer>
    {{ $scripts ?? '' }}
    @stack('scripts')
</body>
</html>