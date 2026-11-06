<x-layout.publik title="Status Pemulihan Akun">
    <section class="mx-auto w-full max-w-3xl space-y-6 px-5 py-10 sm:py-14">
        <header>
            <p class="text-sm font-bold uppercase text-sekunder">Pemulihan Akun</p>
            <h1 class="mt-2 font-serif text-3xl font-bold text-primer">Status Permintaan</h1>
        </header>

        @if (session('sukses'))
            <p role="status" class="border-l-4 border-sekunder bg-permukaan px-4 py-3">{{ session('sukses') }}</p>
        @endif

        @if ($permintaan)
            <div class="border-y border-garis py-5">
                <p>Status: <strong>{{ ucfirst($status) }}</strong></p>
                <p class="mt-3 text-sm">Kode pemulihan Anda</p>
                <p class="mt-1 font-mono text-2xl font-bold tracking-widest" data-kode-pemulihan>{{ $permintaan['kode'] }}</p>
                <p class="mt-3 text-sm">Simpan kode ini. Admin tidak akan pernah meminta password Anda.</p>
            </div>

            @if ($status === \App\Services\PemulihanAkun::STATUS_MENUNGGU)
                <p>Permintaan sedang menunggu verifikasi admin. Status diperiksa otomatis.</p>
            @elseif ($status === \App\Services\PemulihanAkun::STATUS_DIBUKA)
                <p>Akses pemulihan dibuka sampai {{ \Carbon\Carbon::parse($permintaan['berlaku_sampai'])->format('d-m-Y H:i') }} WIB.</p>
                <form action="{{ route('lupa-akun.password') }}" method="POST" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label for="password_baru" class="mb-1 block text-sm font-semibold">Password baru</label>
                        <input id="password_baru" name="password_baru" type="password" autocomplete="new-password" required minlength="8" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                        @error('password_baru')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_baru_confirmation" class="mb-1 block text-sm font-semibold">Konfirmasi password baru</label>
                        <input id="password_baru_confirmation" name="password_baru_confirmation" type="password" autocomplete="new-password" required minlength="8" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                    </div>
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white hover:bg-sekunder">Simpan Password Baru</button>
                </form>
            @elseif ($status === \App\Services\PemulihanAkun::STATUS_KEDALUWARSA)
                <p>Kode sudah kedaluwarsa atau tidak tersedia. Ajukan permintaan baru atau masukkan kode lain.</p>
                <a href="{{ route('lupa-akun') }}" class="font-semibold text-primer underline underline-offset-4">Ajukan permintaan baru</a>
            @else
                <p>Permintaan ini sudah selesai.</p>
                <a href="{{ route('login') }}" class="font-semibold text-primer underline underline-offset-4">Masuk</a>
            @endif

            @if ($nomorWhatsApp && $status !== \App\Services\PemulihanAkun::STATUS_SELESAI && $status !== \App\Services\PemulihanAkun::STATUS_KEDALUWARSA)
                @php
                    $pesanWhatsApp = "Assalamu'alaikum Admin, saya mengajukan pemulihan akun.\nKode: {$permintaan['kode']}\nNama lengkap: {$permintaan['nama_lengkap']}\nNama wali: {$permintaan['nama_wali']}\nNama user: ".($permintaan['nama_user_input'] ?: '-');
                @endphp
                <a href="https://wa.me/{{ $nomorWhatsApp }}?text={{ rawurlencode($pesanWhatsApp) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-sm border border-primer px-5 py-2 font-semibold text-primer">Hubungi Admin melalui WhatsApp</a>
            @endif
        @else
            <p>Tidak ada permintaan pemulihan aktif pada sesi ini.</p>
            <a href="{{ route('lupa-akun.lanjut') }}" class="font-semibold text-primer underline underline-offset-4">Masukkan kode pemulihan</a>
        @endif
    </section>

    @if ($status === \App\Services\PemulihanAkun::STATUS_MENUNGGU)
        @push('scripts')
            <script>
                window.setInterval(async () => {
                    try {
                        const response = await fetch('{{ route('lupa-akun.status') }}', {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });
                        const data = await response.json();
                        if (data.status === 'dibuka') {
                            window.location.reload();
                        }
                    } catch {
                        return;
                    }
                }, 10000);
            </script>
        @endpush
    @endif
</x-layout.publik>