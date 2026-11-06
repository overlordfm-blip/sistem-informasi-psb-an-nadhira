<x-layout.pengguna title="Status Pendaftaran">
    <div class="mb-7">
        <p class="text-sm font-bold uppercase text-sekunder">Penerimaan Santri Baru</p>
        <h1 class="mt-2 font-serif text-3xl font-bold text-primer">Status Pendaftaran</h1>
        <p class="mt-2 text-base">Pantau kemajuan proses pendaftaran dan verifikasi data Anda.</p>
    </div>

    @if (! $googleTertaut)
        <x-kartu class="mb-6 border-l-4 border-l-peringatan">
            <p class="font-semibold">Gmail belum tertaut</p>
            <p class="mt-1 text-base">Tautkan Gmail terverifikasi agar akun lebih mudah dipulihkan.</p>
            <a href="{{ route('akun.google') }}" class="mt-3 inline-block font-semibold text-primer underline underline-offset-4">Tautkan Gmail</a>
        </x-kartu>
    @endif

    @if (! $pendaftar)
        <x-kartu>
            <p class="text-sm font-bold uppercase text-sekunder">Belum terdaftar</p>
            <h2 class="mt-2 font-serif text-2xl font-bold text-primer">Anda belum memiliki data pendaftaran.</h2>
            <p class="mt-3 text-base leading-7">Silakan isi formulir pendaftaran untuk memulai proses penerimaan santri baru.</p>
            @if ($periodeTersedia)
                <x-tombol href="{{ route('pendaftaran') }}" class="mt-6">Daftar Sekarang</x-tombol>
            @else
                <p class="mt-5 rounded-sm border-l-4 border-l-peringatan bg-peringatan-latar p-4 text-base text-peringatan">Pendaftaran saat ini sedang ditutup.</p>
            @endif
        </x-kartu>
    @else
        @php
            $tagihanPendaftaran = $pendaftar->tagihan->firstWhere('jenis_tagihan', 'pendaftaran');
            $currentStage = match ($pendaftar->status_daftar) {
                \App\Models\Pendaftar::STATUS_DIPROSES => 3,
                \App\Models\Pendaftar::STATUS_DITOLAK => 3,
                \App\Models\Pendaftar::STATUS_DITERIMA => $tagihanPendaftaran?->status_tagihan === \App\Models\Tagihan::STATUS_LUNAS ? 5 : 4,
                default => 1,
            };
            $steps = ['Registrasi', 'Pendaftaran', 'Tes', 'Verifikasi', 'Pembayaran'];
        @endphp

        <x-kartu class="mb-6">
            <h2 class="mb-5 font-serif text-xl font-bold text-primer">Tahapan Pendaftaran</h2>
            <x-tahapan :steps="$steps" :current="$currentStage" />
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="font-semibold">Verifikasi Data:</span>
                <x-badge-status :status="$pendaftar->status_daftar" :label="strtoupper($pendaftar->status_daftar)" />
                @if ($pendaftar->status_daftar === \App\Models\Pendaftar::STATUS_DIPROSES)
                    <span class="text-base">Sedang diproses (menunggu verifikasi admin)</span>
                @endif
            </div>

            <!-- Tes Seleksi -->
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="font-semibold">Tes Seleksi:</span>
                @php
                    $tesMode = null;
                    $tesHasil = 'belum';
                    $tesNilai = null;
                    $tesCatatan = null;
                    $tesPath = storage_path('app/private/tes/pendaftar-'.$pendaftar->id_pendaftar.'.json');
                    if (is_file($tesPath)) {
                        $tesData = json_decode((string) file_get_contents($tesPath), true);
                        if (is_array($tesData)) {
                            $tesMode = $tesData['mode'] ?? null;
                            $tesHasil = $tesData['hasil'] ?? 'belum';
                            $tesNilai = $tesData['nilai'] ?? null;
                            $tesCatatan = $tesData['catatan'] ?? null;
                        }
                    }
                @endphp
                @if ($tesHasil === 'lulus')
                    <span class="text-sekunder font-semibold">Lulus</span>
                    @if ($tesMode === 'online') <span class="text-teks/60">(Online)</span> @elseif ($tesMode === 'offline') <span class="text-teks/60">(Offline)</span> @endif
                    @if ($tesNilai !== null) <span class="text-teks/60 font-semibold">Nilai: {{ $tesNilai }}</span> @endif
                @elseif ($tesHasil === 'tidak_lulus')
                    <span class="text-bahaya font-semibold">Tidak Lulus</span>
                    @if ($tesMode === 'online') <span class="text-teks/60">(Online)</span> @elseif ($tesMode === 'offline') <span class="text-teks/60">(Offline)</span> @endif
                    @if ($tesCatatan) <span class="text-teks/60 font-semibold">{{ $tesCatatan }}</span> @endif
                @elseif ($tesMode)
                    <span class="text-teks font-semibold">Menunggu pelaksanaan ({{ $tesMode === 'online' ? 'Online' : 'Offline' }})</span>
                @else
                    <span class="text-teks/60 font-semibold">Belum memilih mode tes</span>
                    <a href="{{ route('tes') }}" class="text-primer font-semibold underline underline-offset-4 hover:text-sekunder">Pilih Mode Tes</a>
                @endif
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <span class="font-semibold">Pembayaran Tagihan:</span>
                @if ($pendaftar->status_daftar === \App\Models\Pendaftar::STATUS_DIPROSES)
                    <x-badge-status status="menunggu" label="Menunggu verifikasi" />
                @elseif ($pendaftar->status_daftar === \App\Models\Pendaftar::STATUS_DITOLAK)
                    <span class="text-base text-teks/75">Tidak berlaku</span>
                @elseif ($tagihanPendaftaran)
                    @if ($tagihanPendaftaran->status_tagihan === \App\Models\Tagihan::STATUS_BELUM_DIBAYAR)
                        <x-badge-status status="belum_dibayar" />
                        <a href="{{ route('tagihan') }}" class="font-semibold text-primer underline underline-offset-4 hover:text-sekunder">Lihat Tagihan &amp; Bayar Sekarang</a>
                    @else
                        <x-badge-status status="lunas" />
                    @endif
                @else
                    <span class="text-base">Menunggu tagihan diterbitkan</span>
                @endif
            </div>
        </x-kartu>

        <x-kartu class="mb-6">
            <h2 class="mb-4 font-serif text-xl font-bold text-primer">Data Pendaftaran Anda</h2>
            <x-tabel>
                <tbody class="divide-y divide-garis">
                    <tr><th scope="row" class="w-1/3 px-4 py-3 font-semibold">Nama Lengkap</th><td class="px-4 py-3">{{ $pendaftar->nama_lengkap }}</td></tr>
                    <tr><th scope="row" class="px-4 py-3 font-semibold">Tanggal Lahir</th><td class="px-4 py-3">{{ $pendaftar->tanggal_lahir ? $pendaftar->tanggal_lahir->translatedFormat('d F Y') : '-' }}</td></tr>
                    <tr><th scope="row" class="px-4 py-3 font-semibold">Alamat Tempat Tinggal</th><td class="px-4 py-3">{{ $pendaftar->alamat }}</td></tr>
                    <tr><th scope="row" class="px-4 py-3 font-semibold">Nama Orang Tua / Wali</th><td class="px-4 py-3">{{ $pendaftar->nama_wali }}</td></tr>
                    <tr><th scope="row" class="px-4 py-3 font-semibold">Nomor Telepon</th><td class="px-4 py-3">{{ $pendaftar->no_telepon }}</td></tr>
                    <tr><th scope="row" class="px-4 py-3 font-semibold">Periode</th><td class="px-4 py-3">{{ $pendaftar->periode?->nama_periode ?? '-' }}</td></tr>
                    <tr><th scope="row" class="px-4 py-3 font-semibold">Status</th><td class="px-4 py-3"><x-badge-status :status="$pendaftar->status_daftar" :label="strtoupper($pendaftar->status_daftar)" /></td></tr>
                </tbody>
            </x-tabel>
        </x-kartu>

        @if ($pendaftar->status_daftar === \App\Models\Pendaftar::STATUS_DITERIMA && $tagihanPendaftaran?->status_tagihan === \App\Models\Tagihan::STATUS_BELUM_DIBAYAR)
            <x-kartu class="mb-6 border-l-4 border-l-sekunder">
                <h2 class="font-serif text-xl font-bold text-primer">Informasi Pembayaran</h2>
                <p class="mt-3 text-base">Selamat! Pendaftaran Anda telah diterima oleh panitia penerimaan santri baru.</p>
                <p class="mt-2 text-base">Nominal tagihan pendaftaran: <strong>Rp {{ number_format($tagihanPendaftaran->nominal, 0, ',', '.') }}</strong></p>
                <p class="mt-2 text-base">Status tagihan: <x-badge-status status="belum_dibayar" /></p>
                <x-tombol href="{{ route('tagihan') }}" class="mt-5">Lihat Tagihan</x-tombol>
            </x-kartu>
        @endif

        @if ($pendaftar->status_daftar === \App\Models\Pendaftar::STATUS_DITOLAK)
            <x-kartu class="border-l-4 border-l-bahaya bg-bahaya/5" aria-label="Informasi Pendaftaran Ditolak">
                <p class="text-sm font-bold uppercase text-bahaya">Informasi Pendaftaran</p>
                <h2 class="mt-2 font-serif text-xl font-bold text-bahaya">Pendaftaran belum dapat diterima</h2>
                @if ($catatan)
                    <div class="mt-4 rounded-sm border border-bahaya/20 bg-permukaan p-4">
                        <p class="font-semibold text-teks">Catatan dari admin:</p>
                        <p class="mt-2 whitespace-pre-line text-base leading-7 text-teks">{{ $catatan }}</p>
                    </div>
                @endif

                @if ($periodeTersedia && $periodeTersedia->id_periode !== $pendaftar->id_periode)
                    <p class="mt-4 text-base">Anda dapat mengajukan pendaftaran pada periode yang berbeda.</p>
                    <x-tombol href="{{ route('pendaftaran') }}" class="mt-4">Daftar kembali pada {{ $periodeTersedia->nama_periode }}</x-tombol>
                @else
                    <p class="mt-4 text-base leading-7">{{ __('pendaftaran.ditolak') }}</p>
                @endif
            </x-kartu>
        @endif
    @endif
</x-layout.pengguna>
