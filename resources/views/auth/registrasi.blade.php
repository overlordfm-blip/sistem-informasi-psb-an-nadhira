<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun - {{ config('app.name', 'An-Nadhira') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F8F5EE] text-[#1E1E1E] font-sans min-h-screen flex flex-col justify-between py-8 sm:px-6 lg:px-8">
    <!-- Top Nav / Back Link -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4 flex justify-between items-center text-sm">
        <a href="{{ route('beranda') }}" class="inline-flex items-center text-[#1F5F3B] hover:text-[#2E7D32] font-semibold transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Beranda
        </a>
        <a href="{{ route('login') }}" class="text-[#1F5F3B] hover:underline font-semibold">
            Sudah Punya Akun? Masuk
        </a>
    </div>

    <!-- Main Registration Card -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md my-auto">
        <div class="text-center mb-6">
            <h2 class="font-serif text-3xl font-bold tracking-tight text-[#1F5F3B]">
                Pondok Pesantren An-Nadhira
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Sistem Informasi Penerimaan Santri Baru
            </p>
        </div>

        <div class="bg-white py-8 px-6 shadow-md rounded-xl sm:px-10 border border-[#E3DCCB]">
            <div class="text-center mb-6">
                <h1 class="font-serif text-2xl font-bold text-[#1F5F3B] tracking-wide">REGISTRASI AKUN</h1>
                <div class="w-16 h-1 bg-[#D4AF37] mx-auto mt-2 rounded"></div>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-[#B3261E] text-sm rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('registrasi.proses') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="nama_user" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                        Nama User
                    </label>
                    <div class="mt-1">
                        <input id="nama_user" name="nama_user" type="text" autocomplete="username" required
                            value="{{ old('nama_user') }}"
                            placeholder="Contoh: ahmad_zaidan"
                            class="block w-full rounded border border-[#E3DCCB] px-3.5 py-2.5 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-[#1F5F3B] focus:outline-none focus:ring-1 focus:ring-[#1F5F3B] sm:text-sm">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Maks. 50 karakter (huruf, angka, titik, atau garis bawah).</p>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                        Password
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="new-password" required
                            placeholder="Minimal 8 karakter"
                            class="block w-full rounded border border-[#E3DCCB] px-3.5 py-2.5 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-[#1F5F3B] focus:outline-none focus:ring-1 focus:ring-[#1F5F3B] sm:text-sm">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded bg-[#1F5F3B] px-4 py-3 text-base font-bold text-white shadow hover:bg-[#2E7D32] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1F5F3B] transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        DAFTAR AKUN BARU
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-[#E3DCCB] pt-4 text-center">
                <a href="{{ route('login') }}" class="text-xs font-bold text-[#1F5F3B] hover:text-[#2E7D32] hover:underline uppercase tracking-wide">
                    Sudah punya akun? Masuk
                </a>
            </div>
        </div>
    </div>

    <!-- Footer note -->
    <div class="text-center text-xs text-gray-500 py-4">
        &copy; {{ date('Y') }} {{ config('app.name', 'An-Nadhira') }}. Kalibeber, Mojotengah, Wonosobo.
    </div>
</body>
</html>
