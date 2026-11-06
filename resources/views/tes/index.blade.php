<x-layout.pengguna title="Tes Seleksi">
    <section class="mx-auto max-w-3xl space-y-6">
        <header>
            <p class="text-sm font-bold uppercase text-sekunder">Tahap Seleksi</p>
            <h1 class="mt-2 font-serif text-3xl font-bold text-primer">Tes Seleksi</h1>
            <p class="mt-2">Pilih salah satu mode tes yang diaktifkan oleh pondok.</p>
        </header>

        <x-notifikasi class="mb-4" />

        @if (session('sukses'))
            <p role="status" class="border-l-4 border-sekunder bg-permukaan px-4 py-3">{{ session('sukses') }}</p>
        @endif

        @if ($tes['hasil'] !== \App\Services\TesSeleksiService::HASIL_BELUM)
            <div class="border-y border-garis py-5">
                <p>Hasil tes: <strong>{{ $tes['hasil'] === 'lulus' ? 'Lulus' : 'Tidak lulus' }}</strong></p>
                @if ($tes['nilai'] !== null)<p class="mt-2">Nilai: {{ $tes['nilai'] }}</p>@endif
                @if ($tes['catatan'])<p class="mt-2 whitespace-pre-line">{{ $tes['catatan'] }}</p>@endif
                <p class="mt-3 text-sm">Mode tidak dapat diubah setelah hasil tes dinilai.</p>
            </div>
        @else
            @if (is_array($tes['jadwal_online'] ?? null))
                <p class="border-l-4 border-sekunder bg-permukaan px-4 py-3">Jadwal tes online Anda telah ditetapkan oleh admin.</p>
            @elseif ($tes['mode'])
                <p class="border-l-4 border-sekunder bg-permukaan px-4 py-3">Status tes: Menunggu pelaksanaan ({{ $tes['mode'] === 'online' ? 'Online' : 'Offline' }}).</p>
            @else
                <p class="border-l-4 border-garis bg-permukaan px-4 py-3">Status tes: Belum memilih mode.</p>
            @endif

            @if (is_array($tes['jadwal_online'] ?? null))
                <p class="text-sm">Mode tes dikunci setelah jadwal online diberikan. Hubungi admin bila perlu perubahan.</p>
            @elseif ($pengaturan['online']['aktif'] || $pengaturan['offline']['aktif'])
                <form action="{{ route('tes.pilih') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="mode" class="mb-1 block text-sm font-semibold">Mode tes</label>
                        <select id="mode" name="mode" required class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                            <option value="">Pilih mode tes</option>
                            @if ($pengaturan['online']['aktif'])
                                <option value="online" @selected(old('mode', $tes['mode']) === 'online')>Online</option>
                            @endif
                            @if ($pengaturan['offline']['aktif'])
                                <option value="offline" @selected(old('mode', $tes['mode']) === 'offline')>Offline</option>
                            @endif
                        </select>
                        @error('mode')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white hover:bg-sekunder">{{ $tes['mode'] ? 'Ubah Mode Tes' : 'Pilih Mode Tes' }}</button>
                </form>
            @else
                <p>Tes seleksi belum diaktifkan. Silakan hubungi admin pondok.</p>
            @endif
        @endif

        @if ($tes['mode'] === 'online')
            <div class="border-y border-garis py-5">
                <h2 class="font-serif text-xl font-bold text-primer">Tes Online</h2>
                @if (is_array($tes['jadwal_online'] ?? null))
                    <p class="mt-3"><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($tes['jadwal_online']['tanggal'])->translatedFormat('d F Y') }}</p>
                    <p class="mt-2"><strong>Jam:</strong> {{ $tes['jadwal_online']['jam'] }} WIB</p>
                    @if ($pengaturan['online']['tautan'])
                        <p class="mt-3"><a href="{{ $pengaturan['online']['tautan'] }}" class="font-semibold text-primer underline underline-offset-4">Buka tautan tes online</a></p>
                    @endif
                @else
                    <p class="mt-3">Jadwal tes online akan ditampilkan setelah ditetapkan oleh admin.</p>
                @endif
                @if ($pengaturan['online']['petunjuk'])<p class="mt-3 whitespace-pre-line">{{ $pengaturan['online']['petunjuk'] }}</p>@endif
            </div>
        @elseif ($tes['mode'] === 'offline')
            <div class="border-y border-garis py-5">
                <h2 class="font-serif text-xl font-bold text-primer">Tes Offline</h2>
                <p class="mt-3"><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($pengaturan['offline']['tanggal'])->translatedFormat('d F Y') }}</p>
                <p class="mt-2"><strong>Jam:</strong> {{ $pengaturan['offline']['jam'] }} WIB</p>
                <p class="mt-2"><strong>Lokasi:</strong> {{ $pengaturan['offline']['lokasi'] }}</p>
                @if ($pengaturan['offline']['petunjuk'])<p class="mt-3 whitespace-pre-line">{{ $pengaturan['offline']['petunjuk'] }}</p>@endif
            </div>
        @endif
    </section>
</x-layout.pengguna>