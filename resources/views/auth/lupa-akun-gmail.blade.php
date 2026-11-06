<x-layout.publik title="Pulihkan Akun dengan Gmail">
    <section class="mx-auto w-full max-w-xl px-5 py-10 sm:py-14">
        <div class="rounded-sm border border-garis bg-permukaan p-6 sm:p-8">
            <p class="text-sm font-bold uppercase text-sekunder">Pemulihan akun</p>
            <h1 class="mt-2 font-serif text-3xl font-bold text-primer">Buat Password Baru</h1>
            <p class="mt-3">Akun yang dipulihkan: <strong>{{ $namaUser }}</strong></p>
            <x-notifikasi class="mt-5" />

            <form action="{{ route('lupa-akun.gmail.ubah') }}" method="POST" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-input name="password_baru" label="Password baru" type="password" autocomplete="new-password" required />
                <x-input name="password_baru_confirmation" label="Konfirmasi password baru" type="password" autocomplete="new-password" required />
                <x-tombol type="submit" class="w-full">Simpan Password Baru</x-tombol>
            </form>
        </div>
    </section>
</x-layout.publik>