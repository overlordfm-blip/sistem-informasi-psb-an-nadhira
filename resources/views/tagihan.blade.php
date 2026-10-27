<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan Santri - An-Nadhira Hub</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F8F5EE] text-[#1E1E1E] font-sans min-h-screen p-8">
    <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-sm border border-[#E3DCCB]">
        <div class="flex justify-between items-center pb-4 border-b border-[#E3DCCB]">
            <div>
                <h1 class="font-serif text-2xl font-bold text-[#1F5F3B]">Tagihan Santri</h1>
                <p class="text-sm text-gray-600 mt-1">Selamat datang, <strong>{{ auth()->user()->nama_user }}</strong> (Peran: {{ auth()->user()->peran }})</p>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 bg-[#B3261E] text-white text-sm font-semibold rounded hover:bg-red-700 transition">
                    Keluar
                </button>
            </form>
        </div>
        <div class="mt-6 p-4 bg-[#F8F5EE] rounded border border-[#E3DCCB]">
            <p class="text-sm text-gray-700">Ini adalah halaman sementara Tagihan Santri untuk pengujian autentikasi Fase 2.</p>
        </div>
    </div>
</body>
</html>
