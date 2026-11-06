<x-layout.pengguna title="Tagihan dan Pembayaran">
    <div class="mb-7">
        <p class="text-sm font-bold uppercase text-sekunder">Keuangan santri</p>
        <h1 class="mt-2 font-serif text-3xl font-bold text-primer">Tagihan dan Pembayaran</h1>
        <p class="mt-2 text-base">Berikut daftar tagihan yang terkait dengan akun Anda.</p>
    </div>

    @if (! $googleTertaut)
        <div class="mb-6 rounded-sm border-l-4 border-peringatan bg-peringatan-latar p-4">
            <p class="font-semibold">Gmail belum tertaut</p>
            <p class="mt-1 text-base">Tautkan Gmail terverifikasi agar akun lebih mudah dipulihkan.</p>
            <a href="{{ route('akun.google') }}" class="mt-2 inline-block font-semibold text-primer underline underline-offset-4">Tautkan Gmail</a>
        </div>
    @endif

    @if ($tagihan->isEmpty())
        <x-kartu class="mb-8">
            <p class="text-sm font-bold uppercase text-sekunder">Tagihan</p>
            <h2 class="mt-2 font-serif text-xl font-bold text-primer">Belum ada tagihan.</h2>
            @if ($pendaftar?->status_daftar === \App\Models\Pendaftar::STATUS_DITOLAK)
                <p class="mt-2 text-base">Tidak ada tagihan karena pendaftaran tidak diterima.</p>
            @else
                <p class="mt-2 text-base">Tagihan akan muncul setelah pendaftaran Anda diverifikasi oleh admin.</p>
            @endif
        </x-kartu>
    @else
        <section class="mb-10">
            <h2 class="mb-4 font-serif text-xl font-bold text-primer">Daftar Tagihan</h2>
            <x-tabel>
                <thead class="border-b border-garis bg-latar">
                    <tr>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">No</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Jenis Tagihan</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Nominal</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Status</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Tanggal Bayar</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-garis">
                    @foreach ($tagihan as $item)
                        @php
                            $bayar = $item->pembayaran;
                            $transaksi = $bayar?->transaksi?->sortByDesc('id_transaksi')->first();
                        @endphp
                        <tr>
                            <td class="px-4 py-3">{{ $loop->iteration }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ ucfirst($item->jenis_tagihan) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                            <td class="px-4 py-3"><x-badge-status :status="$item->status_tagihan" :label="strtoupper($item->status_tagihan)" /></td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $bayar?->tanggal_bayar?->translatedFormat('d F Y') ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex min-w-36 flex-wrap gap-2">
                                    @if ($item->status_tagihan === \App\Models\Tagihan::STATUS_BELUM_DIBAYAR)
                                        <x-tombol
                                            id="btn-bayar-{{ $item->id_tagihan }}"
                                            data-jenis-tagihan="{{ $item->jenis_tagihan }}"
                                            type="button"
                                            onclick="bayarTagihan({{ $item->id_tagihan }})"
                                            class="px-3 py-2 text-sm"
                                        >Bayar Sekarang</x-tombol>
                                        @if ($transaksi && $transaksi->status_transaksi === \App\Models\Transaksi::STATUS_MENUNGGU)
                                            <x-tombol
                                                id="btn-bayar-baru-{{ $item->id_tagihan }}"
                                                type="button"
                                                onclick="bayarTagihan({{ $item->id_tagihan }}, true)"
                                                title="Buat transaksi baru jika pembayaran sebelumnya kedaluwarsa atau ingin memilih metode lain"
                                                class="px-3 py-2 text-sm"
                                            >Transaksi Baru</x-tombol>
                                        @endif
                                    @else
                                        <x-badge-status status="lunas" />
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if ($bayar && $bayar->transaksi->isNotEmpty())
                            <tr>
                                <td colspan="6" class="bg-latar/60 px-4 py-3">
                                    <details>
                                        <summary class="cursor-pointer font-semibold text-primer">Riwayat Transaksi</summary>
                                        <div class="mt-3">
                                            <x-tabel>
                                                <thead class="border-b border-garis bg-permukaan">
                                                    <tr>
                                                        <th scope="col" class="px-3 py-2 font-semibold">Order ID</th>
                                                        <th scope="col" class="px-3 py-2 font-semibold">Metode</th>
                                                        <th scope="col" class="px-3 py-2 font-semibold">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-garis">
                                                    @foreach ($bayar->transaksi->sortByDesc('id_transaksi') as $trx)
                                                        <tr>
                                                            <td class="px-3 py-2">{{ $trx->order_id }}</td>
                                                            <td class="px-3 py-2">{{ $trx->metode ?? '-' }}</td>
                                                            <td class="px-3 py-2"><x-badge-status :status="$trx->status_transaksi" :label="strtoupper($trx->status_transaksi)" /></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </x-tabel>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </x-tabel>
        </section>
    @endif

    <section>
        <div class="mb-4">
            <p class="text-sm font-bold uppercase text-sekunder">Transaksi akun Anda</p>
            <h2 class="mt-2 font-serif text-xl font-bold text-primer">Riwayat Pembayaran</h2>
        </div>
        @if ($riwayatPembayaran->isEmpty())
            <x-kartu>
                <p class="text-base">Belum ada riwayat pembayaran.</p>
            </x-kartu>
        @else
            <x-tabel>
                <thead class="border-b border-garis bg-latar">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Jenis Tagihan</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Nominal</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-garis">
                    @foreach ($riwayatPembayaran as $bayar)
                        @php
                            $tanggalTampil = $bayar->tanggal_riwayat
                                ? $bayar->tanggal_riwayat->translatedFormat('d F Y')
                                : '-';
                        @endphp
                        <tr>
                            @if ($tanggalTampil === '-')
                                <td>-</td>
                            @else
                                <td class="whitespace-nowrap px-4 py-3">{{ $tanggalTampil }}</td>
                            @endif
                            <td class="px-4 py-3">{{ ucfirst($bayar->tagihan?->jenis_tagihan ?? '-') }}</td>
                            <td class="whitespace-nowrap px-4 py-3">Rp {{ number_format($bayar->jumlah_bayar, 0, ',', '.') }}</td>
                            <td class="px-4 py-3"><x-badge-status :status="$bayar->status_bayar" :label="strtoupper($bayar->status_bayar)" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-tabel>
        @endif
    </section>
<x-slot:scripts>
{{-- Snap.js Midtrans (sandbox) --}}
<script src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('services.midtrans.client_key') }}"></script>

<script>
    var pollingIntervals = {};

    function startPollingStatus(idTagihan) {
        if (pollingIntervals[idTagihan]) {
            clearInterval(pollingIntervals[idTagihan]);
        }

        pollingIntervals[idTagihan] = setInterval(function() {
            fetch('/tagihan/' + idTagihan + '/status', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.lunas) {
                    clearInterval(pollingIntervals[idTagihan]);
                    simpanJenisPembayaranBerhasil(idTagihan);
                    window.location.reload();
                }
            })
            .catch(function() {});
        }, 2500);
    }

    function periksaDanReloadJikaLunas(idTagihan) {
        fetch('/tagihan/' + idTagihan + '/status', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.lunas) {
                simpanJenisPembayaranBerhasil(idTagihan);
                window.location.reload();
            }
        })
        .catch(function() {});
    }

    function simpanJenisPembayaranBerhasil(idTagihan) {
        var tombol = document.getElementById('btn-bayar-' + idTagihan);
        if (tombol && tombol.dataset.jenisTagihan) {
            sessionStorage.setItem('pembayaran_sukses', tombol.dataset.jenisTagihan);
        }
    }

    function bayarTagihan(idTagihan, forceNew) {
        forceNew = forceNew || false;
        var btn = document.getElementById('btn-bayar-' + idTagihan);
        if (btn) { btn.disabled = true; btn.textContent = 'Memproses...'; }
        var btnBaru = document.getElementById('btn-bayar-baru-' + idTagihan);
        if (btnBaru) { btnBaru.disabled = true; }

        fetch('/tagihan/' + idTagihan + '/bayar', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                    ? document.querySelector('meta[name="csrf-token"]').content
                    : '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ baru: forceNew }),
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (! data.snap_token) {
                alert(data.message || 'Gagal mendapatkan token pembayaran.');
                if (btn) { btn.disabled = false; btn.textContent = 'Bayar Sekarang'; }
                if (btnBaru) { btnBaru.disabled = false; }
                return;
            }

            // Mulai polling otomatis di latar belakang saat sesi pembayaran dimulai
            startPollingStatus(idTagihan);

            window.snap.pay(data.snap_token, {
                onSuccess: function() {
                    periksaDanReloadJikaLunas(idTagihan);
                },
                onPending: function() {
                    periksaDanReloadJikaLunas(idTagihan);
                },
                onError: function() {
                    periksaDanReloadJikaLunas(idTagihan);
                },
                onClose: function() {
                    if (btn) { btn.disabled = false; btn.textContent = 'Bayar Sekarang'; }
                    if (btnBaru) { btnBaru.disabled = false; }
                    periksaDanReloadJikaLunas(idTagihan);
                },
            });
        })
        .catch(function(err) {
            console.error(err);
            alert('Terjadi kesalahan. Silakan coba lagi.');
            if (btn) { btn.disabled = false; btn.textContent = 'Bayar Sekarang'; }
            if (btnBaru) { btnBaru.disabled = false; }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Tampilkan pesan sukses jika baru selesai pembayaran
        var jenisPembayaranSukses = sessionStorage.getItem('pembayaran_sukses');
        if (jenisPembayaranSukses === 'pendaftaran' || jenisPembayaranSukses === 'syahriyah') {
            sessionStorage.removeItem('pembayaran_sukses');
            var alertBox = document.createElement('div');
            alertBox.setAttribute('role', 'status');
            alertBox.className = 'mb-4 rounded-sm border border-sekunder bg-sekunder/10 px-4 py-3 text-base font-semibold text-sekunder';
            alertBox.textContent = jenisPembayaranSukses === 'pendaftaran'
                ? 'Alhamdulillah! Pembayaran pendaftaran Anda telah lunas. Akun Anda kini resmi menjadi santri.'
                : 'Alhamdulillah! Pembayaran tagihan syahriyah Anda telah lunas.';
            var main = document.querySelector('main');
            if (main) main.insertBefore(alertBox, main.firstChild);
        }

        // Jika ada tagihan yang masih belum lunas dan memiliki transaksi menunggu, jalankan polling otomatis
        @foreach ($tagihan as $item)
            @if ($item->status_tagihan === \App\Models\Tagihan::STATUS_BELUM_DIBAYAR && $item->pembayaran?->transaksi?->where('status_transaksi', \App\Models\Transaksi::STATUS_MENUNGGU)->isNotEmpty())
                startPollingStatus({{ $item->id_tagihan }});
            @endif
        @endforeach
    });
</script>
</x-slot:scripts>
</x-layout.pengguna>
