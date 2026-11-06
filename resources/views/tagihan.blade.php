<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan Santri - {{ config('app.name', 'An-Nadhira') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F8F5EE] text-[#1E1E1E] font-sans min-h-screen">
    <!-- Navbar Pengguna dengan Fitur Log Out -->
    <header class="bg-[#1F5F3B] text-white shadow-md">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('beranda') }}" class="font-serif text-xl font-bold tracking-tight hover:text-[#D4AF37] transition">
                    {{ config('app.name', 'An-Nadhira') }}
                </a>
                <span class="text-xs bg-[#D4AF37] text-black font-semibold px-2 py-0.5 rounded">
                    SANTRI / WALI
                </span>
            </div>
            
            <div class="flex items-center space-x-4">
                <div class="hidden sm:block text-right">
                    <p class="text-sm font-semibold leading-tight">{{ auth()->user()->nama_tampilan }}</p>
                    <p class="text-xs text-gray-200">Santri Aktif</p>
                </div>

                <!-- Tombol Log Out -->
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#B3261E] hover:bg-red-700 text-white text-xs sm:text-sm font-semibold rounded shadow transition cursor-pointer"
                        title="Keluar dari akun santri">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Keluar (Log Out)</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Konten Utama -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-[#E3DCCB]">
            <div class="border-b border-[#E3DCCB] pb-4 mb-6">
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1F5F3B]">Tagihan Santri</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Selamat datang, <strong>{{ auth()->user()->nama_tampilan }}</strong>. Anda login dengan hak akses santri/wali santri.
                </p>
            </div>

            <div class="p-4 bg-[#F8F5EE] rounded-lg border border-[#E3DCCB] text-sm text-gray-700 space-y-2">
                <p class="font-semibold text-[#1F5F3B]">Status Fase 2:</p>
                <p>Otentikasi dan proteksi peran (middleware <code>peran:santri</code>) berhasil. Integrasi Snap Midtrans (sandbox), tagihan syahriyah, dan riwayat pembayaran akan dikembangkan di <strong>Fase 6</strong>.</p>
            </div>
        </div>
    </main>
</body>
</html>
