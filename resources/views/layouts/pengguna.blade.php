<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pendaftaran Santri Baru') - {{ config('app.name', 'An-Nadhira') }}</title>
</head>
<body>
    <header>
        <div>
            <h1>{{ config('app.name', 'An-Nadhira') }}</h1>
            <p>Pengguna: <strong>{{ auth()->user()->nama_tampilan }}</strong>
                ({{ auth()->user()->isSantri() ? 'Santri' : 'Calon Santri' }})
            </p>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit">Keluar (Log Out)</button>
            </form>
        </div>

        <nav aria-label="Menu Pengguna">
            <ul>
                <li><a href="{{ route('beranda') }}">Beranda</a></li>
                @if (auth()->user()->isCalonSantri())
                    <li><a href="{{ route('status-pendaftaran') }}">Status Pendaftaran</a></li>
                    <li><a href="{{ route('pendaftaran') }}">Formulir Pendaftaran</a></li>
                @endif
                <li><a href="{{ route('tagihan') }}">Tagihan</a></li>
                <li><a href="{{ route('akun.password') }}">Ubah Password</a></li>
            </ul>
        </nav>
    </header>

    <hr>

    <main>
        @include('partials.flash')

        @yield('content')
    </main>

    <hr>

    <footer>
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'An-Nadhira') }}. Hak Cipta Dilindungi.</p>
    </footer>

    @stack('scripts')
</body>
</html>
