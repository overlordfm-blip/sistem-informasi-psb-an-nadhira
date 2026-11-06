<x-layout.publik title="Lupa Akun atau Password">
    <section class="mx-auto grid w-full max-w-5xl gap-8 px-5 py-10 sm:py-14 lg:grid-cols-[1.1fr_0.9fr] lg:px-8">
        <div>
            <p class="text-sm font-bold uppercase text-sekunder">Bantuan Akun</p>
            <h1 class="mt-2 font-serif text-3xl font-bold text-primer sm:text-4xl">Lupa akun atau password?</h1>
            <ol class="mt-6 list-decimal space-y-3 pl-6 text-base leading-7">
                <li>Ajukan permintaan pemulihan, lalu hubungi admin melalui WhatsApp atau datang ke kantor pondok dengan membawa identitas.</li>
                <li>Admin akan memverifikasi identitas dan membuka akses pemulihan selama 30 menit.</li>
                <li>Setelah akses dibuka, buat password baru. Anda akan diminta masuk kembali.</li>
            </ol>
            <p class="mt-5 text-sm leading-6">Permintaan selalu diterima dengan pesan yang sama. Nama user yang diingat tidak diperiksa di halaman ini.</p>
        </div>

        <div class="rounded-sm border border-garis bg-permukaan p-5 sm:p-7">
            <x-notifikasi class="mb-5" />
            <a href="{{ route('auth.google.pemulihan') }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-sm border border-primer px-5 py-2 font-semibold text-primer hover:bg-latar">
                Pulihkan dengan Gmail
            </a>
            <p class="mt-3 text-sm">Gmail hanya digunakan untuk pemulihan, bukan untuk masuk.</p>

            @if ($alamatPondok)
                <p><strong>Alamat:</strong> {{ $alamatPondok }}</p>
            @endif
            @if ($jamLayanan)
                <p class="mt-2"><strong>Jam layanan:</strong> {{ $jamLayanan }}</p>
            @endif

            <form action="{{ route('lupa-akun.permintaan') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="nama_lengkap" class="mb-1 block text-sm font-semibold">Nama lengkap</label>
                    <input id="nama_lengkap" name="nama_lengkap" type="text" autocomplete="name" value="{{ old('nama_lengkap') }}" required maxlength="100" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                    @error('nama_lengkap')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nama_wali" class="mb-1 block text-sm font-semibold">Nama wali</label>
                    <input id="nama_wali" name="nama_wali" type="text" autocomplete="off" value="{{ old('nama_wali') }}" required maxlength="100" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                    @error('nama_wali')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nama_user_input" class="mb-1 block text-sm font-semibold">Nama user jika ingat (opsional)</label>
                    <input id="nama_user_input" name="nama_user_input" type="text" autocomplete="username" value="{{ old('nama_user_input') }}" maxlength="50" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                    @error('nama_user_input')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white hover:bg-sekunder">Kirim Permintaan Pemulihan</button>
            </form>

            <p class="mt-5 border-t border-garis pt-4 text-sm">
                Sudah memiliki kode pemulihan?
                <a href="{{ route('lupa-akun.lanjut') }}" class="font-semibold text-primer underline underline-offset-4">Masukkan kode</a>
            </p>
        </div>
    </section>
</x-layout.publik>