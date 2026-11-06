<x-layout.publik title="Masukkan Kode Pemulihan">
    <section class="mx-auto w-full max-w-xl px-5 py-10 sm:py-14">
        <h1 class="font-serif text-3xl font-bold text-primer">Masukkan Kode Pemulihan</h1>
        <p class="mt-3">Gunakan kode yang diberikan admin setelah identitas Anda diverifikasi.</p>
        <form action="{{ route('lupa-akun.lanjut.proses') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="kode" class="mb-1 block text-sm font-semibold">Kode 8 karakter</label>
                <input id="kode" name="kode" type="text" value="{{ old('kode') }}" required minlength="8" maxlength="8" autocomplete="off" autocapitalize="characters" class="w-full rounded-sm border border-garis bg-white px-3 py-2 font-mono text-xl uppercase tracking-widest">
                @error('kode')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white hover:bg-sekunder">Lanjutkan</button>
        </form>
    </section>
</x-layout.publik>