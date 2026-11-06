@props(['title' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' - ' : '' }}Panel Admin - {{ config('app.name', 'An-Nadhira') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-latar text-teks antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[17rem_minmax(0,1fr)]">
        <header class="flex items-center justify-between border-b border-garis bg-permukaan px-4 py-3 lg:hidden">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-full bg-latar"><img src="{{ asset('images/logo-128.png') }}" alt="" class="size-8 object-contain"></span>
                <span class="font-serif font-bold text-primer">Panel Admin</span>
            </a>
            <button type="button" data-admin-menu-toggle aria-expanded="false" aria-controls="admin-sidebar" class="inline-flex items-center gap-2 rounded-sm border border-garis px-4 py-2 font-semibold text-primer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primer">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                Menu
            </button>
        </header>

        <button type="button" data-admin-menu-backdrop aria-label="Tutup menu admin" class="fixed inset-0 z-40 hidden bg-teks/50 lg:hidden"></button>

        <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col overflow-y-auto bg-primer px-5 py-6 text-white shadow-xl transition-transform duration-300 ease-out lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-auto lg:translate-x-0 lg:shadow-none">
            <div class="mb-7 flex items-center justify-between gap-3">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3">
                    <span class="flex size-14 items-center justify-center rounded-full bg-permukaan"><img src="{{ asset('images/logo-128.png') }}" alt="" class="size-11 object-contain"></span>
                    <span class="font-serif text-lg font-bold">Panel Admin</span>
                </a>
                <button type="button" data-admin-menu-close class="inline-flex items-center gap-2 rounded-sm px-3 py-2 font-semibold text-white hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-white lg:hidden">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" /></svg>
                    Tutup
                </button>
            </div>
            <nav aria-label="Menu admin" class="flex flex-col gap-1 text-base font-semibold">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/></svg>Dashboard</a>
                <a href="{{ route('admin.akun.index') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10">Akun Pengguna</a>
                <a href="{{ route('admin.pemulihan.index') }}" class="flex items-center justify-between gap-3 rounded-sm px-3 py-2 hover:bg-white/10">Permintaan Pemulihan @if ($jumlahPemulihanMenunggu > 0)<span class="min-w-6 rounded-full bg-aksen px-2 py-0.5 text-center text-xs text-teks" aria-label="{{ $jumlahPemulihanMenunggu }} permintaan menunggu">{{ $jumlahPemulihanMenunggu }}</span>@endif</a>
                <a href="{{ route('admin.informasi.index') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3.5h11l3 3V21H5zM16 3.5v4h4M8 11h8M8 15h8M8 18h5"/></svg>Informasi Pondok</a>
                <a href="{{ route('admin.periode.index') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18M8 14h3M8 17h3"/></svg>Periode Pendaftaran</a>
                <a href="{{ route('admin.pendaftar.index') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><circle cx="9" cy="8" r="3"/><path stroke-linecap="round" stroke-linejoin="round" d="M3.5 20v-1.5a5.5 5.5 0 0 1 11 0V20zM16 5.5a3 3 0 0 1 0 5.8M18 14a4.5 4.5 0 0 1 2.5 4v2"/></svg>Data Pendaftar</a>
                <a href="{{ route('admin.pengaturan-tes') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10">Pengaturan Tes Seleksi</a>
                <a href="{{ route('admin.pembayaran.index') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" d="M3 10h18M7 15h4"/></svg>Data Pembayaran</a>
                <a href="{{ route('admin.tagihan.index') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M6 3h9l3 3v15H6zM15 3v4h4M9 11h6M9 15h6M9 18h3"/></svg>Tagihan</a>
                <a href="{{ route('admin.akun-tanpa-pendaftaran.index') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><circle cx="9" cy="8" r="3"/><path stroke-linecap="round" d="M3.5 20v-1.5a5.5 5.5 0 0 1 11 0V20zM17 8h5"/></svg>Akun Tanpa Pendaftaran</a>
                <a href="{{ route('akun.password') }}" class="flex items-center gap-3 rounded-sm px-3 py-2 hover:bg-white/10">Ubah Password</a>
            </nav>
            <form action="{{ route('logout') }}" method="POST" class="mt-auto pt-6">
                @csrf
                <button type="submit" class="inline-flex min-h-11 w-full items-center gap-3 rounded-sm bg-aksen px-4 py-2 text-left font-semibold text-teks hover:brightness-95"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>Keluar</button>
            </form>
        </aside>

        <main data-admin-content class="admin-content min-w-0 px-5 py-8 lg:px-8">
            <x-notifikasi class="mb-6" />
            {{ $slot }}
        </main>
    </div>
    @stack('scripts')
</body>
</html>