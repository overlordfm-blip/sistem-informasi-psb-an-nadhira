<x-layout.publik title="Masuk">
    <section class="mx-auto grid min-h-[calc(100vh-15rem)] w-full max-w-6xl items-center gap-8 px-5 py-10 sm:py-14 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
        <div class="hidden min-h-[34rem] flex-col justify-between rounded-sm bg-primer p-8 text-white lg:flex">
            <div class="flex size-20 items-center justify-center rounded-full bg-permukaan">
                <img src="{{ asset('images/logo-128.png') }}" alt="" class="size-16 object-contain">
            </div>
            <div>
                <p class="text-sm font-bold uppercase text-white/75">Pondok Pesantren An-Nadhira</p>
                <h1 class="mt-3 font-serif text-4xl font-bold">Penerimaan Santri Baru</h1>
                <p class="mt-4 text-base leading-7 text-white/85">Masuk untuk melanjutkan proses pendaftaran atau mengelola tagihan Anda.</p>
            </div>
            <p class="text-base">Kalibeber, Mojotengah, Wonosobo</p>
        </div>

        <div class="mx-auto w-full max-w-xl rounded-sm border border-garis bg-permukaan p-6 shadow-sm sm:p-9">
            <div class="mb-7">
                <p class="text-sm font-bold uppercase text-sekunder">Akun An-Nadhira</p>
                <h2 class="mt-2 font-serif text-3xl font-bold text-primer">Masuk</h2>
                <p class="mt-2 text-base">Masuk dengan nama user dan password, atau gunakan Gmail yang sudah tertaut.</p>
            </div>

            <x-notifikasi class="mb-6" />

            <a href="{{ route('auth.google.login') }}" class="mb-5 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-sm border border-primer px-5 py-2 font-semibold text-primer hover:bg-latar">
                <svg aria-hidden="true" viewBox="0 0 24 24" class="size-5 shrink-0">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.09-1.92 3.28-4.74 3.28-8.09z" />
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.15v2.84A11 11 0 0 0 12 23z" />
                    <path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.15a11 11 0 0 0 0 9.88l3.69-2.84z" />
                    <path fill="#EA4335" d="M12 5.36c1.62 0 3.06.56 4.21 1.64l3.15-3.15A10.55 10.55 0 0 0 12 1a11 11 0 0 0-9.85 6.06l3.69 2.84c.87-2.6 3.3-4.54 6.16-4.54z" />
                </svg>
                Masuk dengan Gmail
            </a>
            <p class="mb-5 text-center text-sm text-teks/70">atau masuk dengan nama user dan password</p>

            <form action="{{ route('login.proses') }}" method="POST" class="space-y-5">
                @csrf

                <x-input
                    name="nama_user"
                    label="Nama User"
                    type="text"
                    autocomplete="username"
                    placeholder="Masukkan nama user Anda"
                    required
                />

                <x-input
                    name="password"
                    label="Password"
                    type="password"
                    autocomplete="current-password"
                    placeholder="Masukkan password Anda"
                    required
                />

                <x-tombol type="submit" class="w-full gap-2">MASUK KE AKUN</x-tombol>
            </form>

            <p class="mt-5 text-center text-base">
                <a href="{{ route('lupa-akun') }}" class="font-semibold text-primer underline underline-offset-4 hover:text-sekunder">Lupa akun atau password?</a>
            </p>

            <p class="mt-6 border-t border-garis pt-5 text-center text-base">
                Belum punya akun?
                <a href="{{ route('registrasi') }}" class="font-semibold text-primer underline underline-offset-4 hover:text-sekunder">Daftar sekarang</a>
            </p>
        </div>
    </section>
</x-layout.publik>
