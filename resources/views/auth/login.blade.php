<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - {{ config('app.name', 'An-Nadhira') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Lora:wght@600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F8F5EE] text-[#1E1E1E] font-sans min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <h2 class="text-center font-serif text-3xl font-bold tracking-tight text-[#1F5F3B]">
            Pondok Pesantren An-Nadhira
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Sistem Informasi Penerimaan Santri Baru
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-sm rounded-lg sm:px-10 border border-[#E3DCCB]">
            <div class="text-center mb-6">
                <h1 class="font-serif text-2xl font-bold text-[#1F5F3B]">MASUK</h1>
                <div class="w-16 h-1 bg-[#D4AF37] mx-auto mt-2 rounded"></div>
            </div>

            @if (session('sukses'))
                <div class="mb-4 p-3 bg-green-50 border border-green-200 text-[#1F5F3B] text-sm rounded">
                    {{ session('sukses') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-[#B3261E] text-sm rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.proses') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label for="nama_user" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                        Nama User
                    </label>
                    <div class="mt-1">
                        <input id="nama_user" name="nama_user" type="text" autocomplete="username" required
                            value="{{ old('nama_user') }}"
                            class="block w-full rounded border border-[#E3DCCB] px-3 py-2 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-[#1F5F3B] focus:outline-none focus:ring-1 focus:ring-[#1F5F3B] sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-700">
                        Password
                    </label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="block w-full rounded border border-[#E3DCCB] px-3 py-2 text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-[#1F5F3B] focus:outline-none focus:ring-1 focus:ring-[#1F5F3B] sm:text-sm">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="flex w-full justify-center rounded bg-[#1F5F3B] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#2E7D32] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1F5F3B] transition">
                        MASUK
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-[#E3DCCB] pt-4 text-center">
                <a href="{{ route('registrasi') }}" class="text-xs font-bold text-[#1F5F3B] hover:text-[#2E7D32] hover:underline uppercase tracking-wide">
                    Belum punya akun? Daftar
                </a>
            </div>
        </div>
    </div>
</body>
</html>
