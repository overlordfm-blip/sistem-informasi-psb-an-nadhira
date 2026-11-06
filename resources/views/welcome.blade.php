<x-layout.publik title="Beranda">
    <section class="relative isolate overflow-hidden bg-primer text-white">
        <div class="absolute inset-y-0 right-0 -z-10 hidden w-2/5 border-l border-white/15 lg:block"></div>
        <div class="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:py-20 lg:grid-cols-[minmax(0,1.3fr)_minmax(18rem,0.7fr)] lg:items-center lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                @if (auth()->check() && auth()->user()->isSantri())
                    <p class="text-sm font-bold uppercase text-white/75">Area Santri</p>
                    <h1 class="mt-3 text-4xl font-bold leading-tight sm:text-5xl">Selamat Datang, {{ auth()->user()->nama_tampilan }}</h1>
                    <p class="mt-5 max-w-2xl text-lg leading-relaxed text-white/90">
                        Kelola tagihan dan akses informasi pondok melalui akun santri Anda.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <x-tombol varian="sekunder" href="{{ route('tagihan') }}">Lihat Tagihan</x-tombol>
                    </div>
                @else
                    <x-badge-status
                        :status="$periodeAktif || $periodeTerjadwal ? 'dibuka' : 'ditutup'"
                        :label="$periodeAktif
                            ? 'Pendaftaran dibuka sampai '.$periodeAktif->tanggal_tutup->translatedFormat('d F Y')
                            : ($periodeTerjadwal
                                ? 'Pendaftaran akan dibuka pada '.$periodeTerjadwal->tanggal_buka->translatedFormat('d F Y')
                                : 'Pendaftaran ditutup')"
                        class="border border-white/20 bg-white text-primer"
                    />
                    <p class="mt-8 text-base font-semibold uppercase">{{ config('pondok.nama') }}</p>
                    <h1 class="mt-3 text-4xl font-bold leading-tight sm:text-5xl">Penerimaan Santri Baru</h1>
                    <p class="mt-5 max-w-2xl text-lg leading-relaxed text-white/90">
                        Mulai langkah pendidikan dan pembinaan bersama Pondok Pesantren An-Nadhira, Kalibeber, Wonosobo.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @if (auth()->guest())
                            <x-tombol varian="sekunder" href="{{ route('registrasi') }}">Daftar Sekarang</x-tombol>
                        @elseif (auth()->user()->isCalonSantri())
                            <x-tombol varian="sekunder" href="{{ route('pendaftaran') }}">Daftar Sekarang</x-tombol>
                        @else
                            <x-tombol varian="sekunder" href="{{ route('admin.dashboard') }}">Dashboard Admin</x-tombol>
                        @endif
                        <x-tombol href="#alur" class="border border-white bg-transparent text-white hover:bg-white/10">Lihat Alur</x-tombol>
                    </div>
                @endif
            </div>
            <div class="flex items-center justify-center lg:justify-end">
                <div class="flex aspect-square w-52 items-center justify-center rounded-full bg-permukaan p-5 sm:w-64">
                    <img src="{{ asset('images/logo-512.png') }}" alt="Lambang Pondok Pesantren An-Nadhira" class="size-full object-contain">
                </div>
            </div>
        </div>
    </section>

    <section id="tentang" class="scroll-mt-8 border-b border-garis bg-permukaan">
        <div class="mx-auto max-w-7xl px-5 py-14 lg:px-8 lg:py-16">
            <div class="mb-8 max-w-2xl">
                <p class="text-sm font-bold uppercase text-sekunder">Mengenal kami</p>
                <h2 class="mt-2 text-3xl font-bold text-primer">Tentang Pondok</h2>
            </div>
            <div class="grid gap-8 md:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)] md:items-center">
                <div class="flex min-h-56 items-center justify-center rounded-sm bg-latar p-8">
                    <img src="{{ asset('images/logo-512.png') }}" alt="Lambang Pondok Pesantren An-Nadhira" class="size-44 object-contain">
                </div>
                <div class="max-w-3xl">
                    <h3 class="font-serif text-2xl font-bold text-teks">{{ config('pondok.nama') }}</h3>
                    <p class="mt-4 text-base leading-7">{{ config('pondok.alamat') }}</p>
                    @if (! auth()->check() || ! auth()->user()->isSantri())
                        <p class="mt-3 text-base leading-7">Informasi penerimaan santri baru tersedia melalui halaman ini. Silakan ikuti tahapan pendaftaran untuk memulai proses.</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section id="informasi" class="scroll-mt-8 border-b border-garis bg-latar">
        <div class="mx-auto max-w-7xl px-5 py-14 lg:px-8 lg:py-16">
            <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-bold uppercase text-sekunder">Kabar terbaru</p>
                    <h2 class="mt-2 text-3xl font-bold text-primer">Informasi Pondok</h2>
                </div>
                @auth
                    @if (auth()->user()->peran === \App\Models\User::PERAN_ADMIN)
                        <a href="{{ route('admin.informasi.index') }}" class="font-semibold text-sekunder underline underline-offset-4">Kelola informasi</a>
                    @endif
                @endauth
            </div>
            @if ($informasiList->isNotEmpty())
                <div role="region" aria-label="Informasi Pondok" aria-roledescription="karusel" tabindex="0" class="flex snap-x snap-mandatory gap-4 overflow-x-auto overscroll-x-contain pb-5 [scrollbar-color:var(--color-primer)_var(--color-latar)] [scrollbar-width:thin]">
                    @foreach ($informasiList as $informasi)
                        <x-kartu class="flex min-h-44 basis-[85%] shrink-0 snap-start flex-col sm:basis-[calc(50%-0.5rem)] lg:basis-[calc(33.333%-0.75rem)]">
                            <p class="text-sm font-semibold text-sekunder">{{ $informasi->diperbarui->translatedFormat('d F Y') }}</p>
                            <h3 class="mt-3 font-serif text-xl font-bold text-primer">{{ $informasi->judul }}</h3>
                            <p class="mt-3 whitespace-pre-line text-base leading-7">{{ $informasi->isi }}</p>
                        </x-kartu>
                    @endforeach
                </div>
            @else
                <div class="rounded-sm border border-dashed border-garis bg-permukaan px-5 py-10 text-center">
                    <p class="font-semibold">Belum ada informasi pondok.</p>
                    <p class="mt-1 text-base">Informasi terbaru akan ditampilkan di bagian ini.</p>
                </div>
            @endif
        </div>
    </section>

    @if (! auth()->check() || ! auth()->user()->isSantri())
        <section id="alur" class="scroll-mt-8 border-b border-garis bg-permukaan">
            <div class="mx-auto max-w-7xl px-5 py-14 lg:px-8 lg:py-16">
                <div class="mb-8 max-w-2xl">
                    <p class="text-sm font-bold uppercase text-sekunder">Lima tahap</p>
                    <h2 class="mt-2 text-3xl font-bold text-primer">Alur Pendaftaran</h2>
                </div>
                @php($steps = ['Registrasi akun', 'Isi formulir pendaftaran', 'Tes seleksi', 'Verifikasi oleh pondok', 'Pembayaran tagihan'])
                <x-tahapan :steps="$steps" />
            </div>
        </section>
    @endif

    <section id="kontak" class="scroll-mt-8 bg-primer text-white">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 py-14 md:grid-cols-2 lg:px-8 lg:py-16">
            <div>
                <p class="text-sm font-bold uppercase text-white/75">Hubungi kami</p>
                <h2 class="mt-2 font-serif text-3xl font-bold">Kontak</h2>
                <address class="mt-6 space-y-3 text-base not-italic leading-7">
                    <p>{{ config('pondok.nama') }}</p>
                    <p>{{ config('pondok.alamat') }}</p>
                    <p>Telepon: <a class="font-semibold underline underline-offset-4" href="tel:{{ preg_replace('/[^0-9+]/', '', config('pondok.telepon')) }}">{{ config('pondok.telepon') }}</a></p>
                    <p>Email: <a class="font-semibold underline underline-offset-4" href="mailto:{{ config('pondok.email') }}">{{ config('pondok.email') }}</a></p>
                </address>
            </div>
            <div class="flex min-h-48 items-center justify-center border border-white/20 bg-white/5 p-8 text-center">
                @if (auth()->check() && auth()->user()->isSantri())
                    <p class="max-w-sm text-base leading-7">Untuk pertanyaan atau bantuan, silakan hubungi pondok melalui telepon atau email.</p>
                @else
                    <p class="max-w-sm text-base leading-7">Untuk informasi penerimaan santri baru, silakan hubungi pondok melalui telepon atau email.</p>
                @endif
            </div>
        </div>
    </section>
</x-layout.publik>