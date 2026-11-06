<section class="mx-auto max-w-2xl space-y-6">
    <header>
        <h1 class="font-serif text-3xl font-bold text-primer">Ubah Password</h1>
        <p class="mt-2">Masukkan password lama, lalu tentukan password baru minimal 8 karakter.</p>
    </header>

    <form action="{{ route('akun.password.update') }}" method="POST" class="space-y-5">
        @csrf
        <x-input name="password_lama" label="Password lama" type="password" autocomplete="current-password" required />
        <x-input name="password_baru" label="Password baru" type="password" autocomplete="new-password" required />
        <x-input name="password_baru_confirmation" label="Konfirmasi password baru" type="password" autocomplete="new-password" required />
        <x-tombol type="submit">Simpan Password Baru</x-tombol>
    </form>
</section>