@extends('layouts.admin')

@section('title', 'Verifikasi Pemulihan')

@section('content')
<section class="mx-auto max-w-4xl space-y-6">
    <header>
        <a href="{{ route('admin.pemulihan.index') }}" class="font-semibold text-primer underline underline-offset-4">Kembali ke Permintaan Pemulihan</a>
        <h1 class="mt-4 font-serif text-3xl font-bold text-primer">Verifikasi Identitas</h1>
    </header>

    <dl class="grid gap-3 border-y border-garis py-5 sm:grid-cols-2">
        <div><dt class="text-sm font-semibold">Kode</dt><dd class="mt-1 font-mono font-bold tracking-wider">{{ $permintaan['kode'] }}</dd></div>
        <div><dt class="text-sm font-semibold">Nama user diingat</dt><dd class="mt-1">{{ $permintaan['nama_user_input'] ?: '-' }}</dd></div>
        <div><dt class="text-sm font-semibold">Nama lengkap</dt><dd class="mt-1">{{ $permintaan['nama_lengkap'] }}</dd></div>
        <div><dt class="text-sm font-semibold">Nama wali</dt><dd class="mt-1">{{ $permintaan['nama_wali'] }}</dd></div>
    </dl>

    @if ($permintaan['nama_user_input'])
        <p class="text-sm">Jika nama user yang diingat tidak cocok, <a href="{{ route('admin.akun.index', ['cari' => $permintaan['nama_user_input']]) }}" class="font-semibold text-primer underline underline-offset-4">cari akun melalui daftar akun</a> atau gunakan pencarian di bawah.</p>
    @endif

    <form method="GET" action="{{ route('admin.pemulihan.show', $permintaan['kode']) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
            <label for="cari_akun" class="mb-1 block text-sm font-semibold">Cari akun untuk dicocokkan</label>
            <input id="cari_akun" name="cari_akun" value="{{ request('cari_akun') }}" type="search" class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
        </div>
        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white">Cari Akun</button>
    </form>

    <form action="{{ route('admin.pemulihan.buka', $permintaan['kode']) }}" method="POST" class="space-y-5">
        @csrf
        <div>
            <label for="id_user" class="mb-1 block text-sm font-semibold">Pilih akun</label>
            <select id="id_user" name="id_user" required data-pilihan-akun class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                <option value="">Pilih akun calon santri atau santri</option>
                @if ($akunTerpilih)
                    <option value="{{ $akunTerpilih->id_user }}" selected>{{ $akunTerpilih->nama_user }}{{ $akunTerpilih->pendaftar ? ' - '.$akunTerpilih->pendaftar->nama_lengkap : '' }}</option>
                @endif
                @foreach ($akunList as $akunPilihan)
                    @if (! $akunTerpilih || $akunPilihan->id_user !== $akunTerpilih->id_user)
                        <option value="{{ $akunPilihan->id_user }}">{{ $akunPilihan->nama_user }}{{ $akunPilihan->pendaftar ? ' - '.$akunPilihan->pendaftar->nama_lengkap : '' }}</option>
                    @endif
                @endforeach
            </select>
            @error('id_user')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
        </div>

        @if ($akunTerpilih)
            <dl class="grid gap-3 border-y border-garis py-4 sm:grid-cols-2">
                <div><dt class="text-sm font-semibold">Nama user</dt><dd class="mt-1">{{ $akunTerpilih->nama_user }}</dd></div>
                <div><dt class="text-sm font-semibold">Peran</dt><dd class="mt-1">{{ $akunTerpilih->peran === 'santri' ? 'Santri' : 'Calon santri' }}</dd></div>
                <div><dt class="text-sm font-semibold">Nama lengkap</dt><dd class="mt-1">{{ $akunTerpilih->pendaftar?->nama_lengkap ?? '-' }}</dd></div>
                <div><dt class="text-sm font-semibold">Nama wali</dt><dd class="mt-1">{{ $akunTerpilih->pendaftar?->nama_wali ?? '-' }}</dd></div>
                <div><dt class="text-sm font-semibold">No. telepon terdaftar</dt><dd class="mt-1" data-nomor-terdaftar="{{ $nomorTelepon ?? '' }}">{{ $akunTerpilih->pendaftar?->no_telepon ?? '-' }}</dd></div>
            </dl>
            <div>
                <label for="nomor_whatsapp" class="mb-1 block text-sm font-semibold">Nomor WhatsApp pengirim (opsional)</label>
                <input id="nomor_whatsapp" name="nomor_whatsapp" type="tel" value="{{ old('nomor_whatsapp') }}" autocomplete="off" data-nomor-pengirim class="w-full rounded-sm border border-garis bg-white px-3 py-2 text-base">
                <p class="mt-2 text-sm" data-status-nomor aria-live="polite">Masukkan nomor untuk memeriksa kecocokan.</p>
                @error('nomor_whatsapp')<p class="mt-1 text-sm text-bahaya">{{ $message }}</p>@enderror
            </div>
        @endif

        <label class="flex items-start gap-3 text-base">
            <input type="checkbox" name="identitas_terverifikasi" value="1" required class="mt-1 size-4 accent-primer">
            <span>Identitas pengguna sudah saya verifikasi</span>
        </label>
        @error('identitas_terverifikasi')<p class="text-sm text-bahaya">{{ $message }}</p>@enderror

        <button type="submit" @disabled(! $akunTerpilih) class="inline-flex min-h-11 items-center justify-center rounded-sm bg-primer px-5 py-2 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">Buka Akses Pemulihan 30 Menit</button>
    </form>

    @if ($akunList->hasPages())
        <nav aria-label="Hasil pencarian akun">{{ $akunList->links() }}</nav>
    @endif

    <form action="{{ route('admin.pemulihan.tolak', $permintaan['kode']) }}" method="POST">
        @csrf
        <button type="submit" class="font-semibold text-bahaya underline underline-offset-4">Tolak Permintaan</button>
    </form>
</section>

@push('scripts')
    <script>
        const pilihanAkun = document.querySelector('[data-pilihan-akun]');
        pilihanAkun?.addEventListener('change', () => {
            const url = new URL(window.location.href);
            if (pilihanAkun.value) {
                url.searchParams.set('id_user', pilihanAkun.value);
            } else {
                url.searchParams.delete('id_user');
            }
            window.location.assign(url);
        });

        @if ($akunTerpilih)
            const nomorPendaftar = document.querySelector('[data-nomor-terdaftar]').dataset.nomorTerdaftar;
            const inputNomorPengirim = document.querySelector('[data-nomor-pengirim]');
            const statusNomor = document.querySelector('[data-status-nomor]');
            const normalisasiNomor = (nomor) => {
                let hasil = nomor.replace(/[\s().-]/g, '');
                if (hasil.startsWith('+62')) hasil = hasil.slice(1);
                else if (hasil.startsWith('0')) hasil = `62${hasil.slice(1)}`;
                return /^\d{10,15}$/.test(hasil) ? hasil : null;
            };
            const periksaNomorPengirim = () => {
                if (!inputNomorPengirim.value.trim()) {
                    statusNomor.textContent = 'Masukkan nomor untuk memeriksa kecocokan.';
                    return;
                }
                const nomorNormal = normalisasiNomor(inputNomorPengirim.value);
                statusNomor.textContent = nomorNormal !== null && nomorNormal === nomorPendaftar ? 'Cocok' : 'Tidak cocok';
            };
            inputNomorPengirim.addEventListener('input', periksaNomorPengirim);
            periksaNomorPengirim();
        @endif
    </script>
@endpush
@endsection