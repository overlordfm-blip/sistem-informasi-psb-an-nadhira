<x-layout.pengguna title="Tautan Gmail">
    <section class="mx-auto w-full max-w-3xl space-y-6">
        <header>
            <p class="text-sm font-bold uppercase text-sekunder">Keamanan akun</p>
            <h1 class="mt-2 font-serif text-3xl font-bold text-primer">Tautan Gmail</h1>
            <p class="mt-2 text-base">Gmail tertaut dapat digunakan untuk masuk dan memulihkan password akun.</p>
        </header>

        <x-notifikasi />

        @if ($tautanGoogle)
            <x-kartu>
                <p class="font-semibold">Status: Gmail tertaut</p>
                <p class="mt-2">Alamat: <strong>{{ $tautanGoogle['email_samar'] }}</strong></p>
                <form action="{{ route('akun.google.destroy.self') }}" method="POST" class="mt-6 space-y-4" onsubmit="return confirm('Putuskan tautan Gmail dari akun ini?');">
                    @csrf
                    @method('DELETE')
                    <x-input name="password" label="Password akun" type="password" autocomplete="current-password" required />
                    <x-tombol type="submit" varian="bahaya">Putuskan tautan Gmail</x-tombol>
                </form>
            </x-kartu>
        @else
            <x-kartu>
                <p class="font-semibold">Status: Gmail belum tertaut</p>
                <p class="mt-2">Tautkan Gmail terverifikasi setelah akun dibuat. Anda tetap dapat masuk dengan nama user dan password.</p>
                <x-tombol href="{{ route('akun.google.mulai') }}" class="mt-5">Tautkan Gmail</x-tombol>
            </x-kartu>
        @endif

        <p class="text-sm">Untuk menghubungi admin, gunakan kontak WhatsApp pada halaman <a href="{{ route('lupa-akun') }}" class="font-semibold text-primer underline underline-offset-4">Lupa Akun</a>.</p>
    </section>
</x-layout.pengguna>