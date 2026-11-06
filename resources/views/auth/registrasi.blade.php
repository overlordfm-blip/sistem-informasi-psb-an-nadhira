<x-layout.publik title="Registrasi Akun">
    <section class="mx-auto grid min-h-[calc(100vh-15rem)] w-full max-w-6xl items-center gap-8 px-5 py-10 sm:py-14 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
        <div class="hidden min-h-[38rem] flex-col justify-between rounded-sm bg-primer p-8 text-white lg:flex">
            <div class="flex size-20 items-center justify-center rounded-full bg-permukaan">
                <img src="{{ asset('images/logo-128.png') }}" alt="" class="size-16 object-contain">
            </div>
            <div>
                <p class="text-sm font-bold uppercase text-white/75">Pondok Pesantren An-Nadhira</p>
                <h1 class="mt-3 font-serif text-4xl font-bold">Mulai langkah Anda</h1>
                <p class="mt-4 text-base leading-7 text-white/85">Buat akun calon santri untuk mengakses proses penerimaan santri baru.</p>
            </div>
            <p class="text-base">Kalibeber, Mojotengah, Wonosobo</p>
        </div>

        <div class="mx-auto w-full max-w-xl rounded-sm border border-garis bg-permukaan p-6 shadow-sm sm:p-9">
            <div class="mb-7">
                <p class="text-sm font-bold uppercase text-sekunder">Akun An-Nadhira</p>
                <h2 class="mt-2 font-serif text-3xl font-bold text-primer">REGISTRASI AKUN</h2>
                <p class="mt-2 text-base">Isi data akun untuk memulai pendaftaran.</p>
            </div>

            <x-notifikasi class="mb-6" />

            <p class="mb-5 text-sm leading-6">Gmail tidak wajib untuk membuat akun. Setelah masuk, Anda dapat menautkan Gmail dari menu akun untuk login dan pemulihan password.</p>

            <form action="{{ route('registrasi.proses') }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="form_started_at" value="{{ $formStartedAt }}">
                <input type="hidden" name="form_nonce" value="{{ $formNonce }}">
                <input type="hidden" name="form_signature" value="{{ $formSignature }}">

                <div class="hidden" aria-hidden="true">
                    <label for="website">Biarkan kolom ini kosong</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <x-input
                    name="nama_user"
                    label="Nama User"
                    type="text"
                    autocomplete="username"
                    placeholder="Contoh: ahmad_zaidan"
                    required
                />
                <p class="-mt-4 text-sm text-teks/75">Maks. 50 karakter (huruf, angka, titik, atau garis bawah).</p>

                <x-input
                    name="password"
                    label="Password"
                    type="password"
                    autocomplete="new-password"
                    placeholder="Minimal 8 karakter"
                    required
                />

                <div>
                    <label for="captcha_answer" class="mb-1 block text-base font-semibold">Verifikasi: {{ $captchaSoal }} = ?</label>
                    <input id="captcha_answer" name="captcha_answer" type="number" inputmode="numeric" required
                        @if ($errors->has('captcha_answer')) aria-invalid="true" aria-describedby="captcha-answer-error" @endif
                        class="min-h-11 w-full rounded-sm border border-garis bg-permukaan px-3 py-2 text-base text-teks focus:border-primer focus:outline-2 focus:outline-primer">
                    @error('captcha_answer')
                        <p id="captcha-answer-error" class="mt-1 text-base text-bahaya">{{ $message }}</p>
                    @enderror
                </div>

                <x-tombol type="submit" class="w-full gap-2">DAFTAR AKUN</x-tombol>
            </form>

            <p class="mt-6 border-t border-garis pt-5 text-center text-base">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-primer underline underline-offset-4 hover:text-sekunder">Masuk</a>
            </p>
        </div>
    </section>
</x-layout.publik>
