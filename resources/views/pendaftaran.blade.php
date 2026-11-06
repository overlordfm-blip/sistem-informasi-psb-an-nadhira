<x-layout.pengguna title="Formulir Pendaftaran">
    <div class="mb-7">
        <p class="text-sm font-bold uppercase text-sekunder">Penerimaan Santri Baru</p>
        <h1 class="mt-2 font-serif text-3xl font-bold text-primer">Formulir Pendaftaran</h1>
        <p class="mt-2 text-base">Silakan lengkapi data calon santri dan orang tua atau wali dengan benar.</p>
    </div>

    @if (! $periode)
        <x-kartu class="border-l-4 border-l-peringatan">
            <p class="text-sm font-bold uppercase text-peringatan">Informasi Periode</p>
            <h2 class="mt-2 font-serif text-2xl font-bold text-primer">Pendaftaran sedang ditutup</h2>
            <p class="mt-3 text-base leading-7">Mohon maaf, saat ini pendaftaran santri baru sedang ditutup atau belum ada gelombang pendaftaran yang aktif. {{ __('pendaftaran.menunggu_gelombang_berikutnya') }}</p>
            <x-tombol href="{{ route('beranda') }}" class="mt-6">Kembali ke Beranda</x-tombol>
        </x-kartu>
    @elseif ($kuotaPenuh ?? false)
        <x-kartu class="border-l-4 border-l-bahaya">
            <p class="text-sm font-bold uppercase text-bahaya">Pendaftaran tidak tersedia</p>
            <h2 class="mt-2 font-serif text-2xl font-bold text-primer">Kuota pendaftaran periode ini telah penuh</h2>
            <p class="mt-3 text-base leading-7">Kuota pendaftaran periode ini telah penuh. Silakan kembali ke beranda untuk melihat informasi terbaru.</p>
            <x-tombol href="{{ route('beranda') }}" class="mt-6">Kembali ke Beranda</x-tombol>
        </x-kartu>
    @else
        <x-kartu class="mb-6 border-l-4 border-l-aksen">
            <p class="text-sm font-bold uppercase text-sekunder">Gelombang aktif</p>
            <h2 class="mt-2 font-serif text-xl font-bold text-primer">{{ $periode->nama_periode }}</h2>
            <p class="mt-2 text-base">Ditutup: <strong>{{ $periode->tanggal_tutup ? $periode->tanggal_tutup->translatedFormat('d F Y') : '-' }}</strong></p>
        </x-kartu>

        <x-kartu>
            <x-notifikasi class="mb-6" />
            <form action="{{ route('pendaftaran.proses') }}" method="POST" class="space-y-6">
                @csrf

                @include('partials.form-pendaftaran')

                <div class="flex flex-col-reverse gap-3 border-t border-garis pt-5 sm:flex-row sm:justify-end">
                    <x-tombol varian="sekunder" href="{{ route('beranda') }}">Batal</x-tombol>
                    <x-tombol type="submit">Kirim Pendaftaran</x-tombol>
                </div>
            </form>
        </x-kartu>
    @endif
</x-layout.pengguna>
